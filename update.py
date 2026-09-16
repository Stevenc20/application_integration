import re

with open('app/Services/ProductionService.php', 'r') as f:
    content = f.read()

content = content.replace(
    'public function finishJob($jobId, $nextJobId = null, $skipIdle = false, $finalOk = null, $finalRepair = null, $finalReject = null)',
    'public function finishJob($jobId, $nextJobId = null, $skipIdle = false, $finalOk = null, $finalRepair = null, $finalReject = null, array $skippedActions = [])'
)
content = content.replace(
    'use ($jobId, $nextJobId, $skipIdle, $finalOk, $finalRepair, $finalReject)',
    'use ($jobId, $nextJobId, $skipIdle, $finalOk, $finalRepair, $finalReject, $skippedActions)'
)

resolve_plan_code = """
    private function resolvePlanId($jobId): ?int
    {
        $job = \App\Models\JobMaster::find($jobId);
        if (!$job) return null;
        
        $parts = explode('-', $job->job_number);
        $planId = end($parts);
        
        if (is_numeric($planId)) {
            $exists = \App\Models\ProductionPlan::where('id', $planId)->exists();
            if ($exists) {
                return (int) $planId;
            }
        }

        $jobPrefix = count($parts) > 1 ? implode('-', array_slice($parts, 0, -1)) : $job->job_number;
        $plan = \App\Models\ProductionPlan::whereDate('plan_date', now()->toDateString())
            ->where('row_type', 'job')
            ->where('job_no', $jobPrefix)
            ->first();

        return $plan?->id;
    }

    private function createRecoveryItem($plan, $job, $actualOk, $recoveryQty, $status, $isUnresolvable = false)
    {
        return \App\Models\RecoveryItem::create([
            'production_plan_id' => $plan ? $plan->id : null,
            'job_no' => $job->job_number,
            'job_name' => $job->job_name,
            'line' => $job->line,
            'original_date' => now()->toDateString(),
            'original_shift_name' => $this->getShift(),
            'source_date' => now()->toDateString(),
            'source_shift' => $this->getShift(),
            'actual_qty' => $actualOk,
            'recovery_qty' => $recoveryQty,
            'queued_at' => now(),
            'status' => $status
        ]);
    }

    private function notifyPpcUsers($job, $recoveryItem)
    {
        $ppcUsers = \App\Models\User::whereHas('role', function($q) {
            $q->where('name', 'like', '%ppc%');
        })->get();
        foreach ($ppcUsers as $user) {
            $user->notify(new \App\Notifications\ItemTidakTercapaiNotification($job, $recoveryItem));
        }
    }
"""
if 'function resolvePlanId' not in content:
    content = content.replace('private function getShift()', resolve_plan_code + '\n    private function getShift()')

start_dandori_match = re.search(r'ProductionSession::firstOrCreate\([\\s\\S]*?\'status\' => \'running\',\\s*\);', content)
if start_dandori_match:
    clear_code = """
            // Clear skipped_at if this plan was previously skipped
            $planId = $this->resolvePlanId($jobId);
            if ($planId) {
                \App\Models\ProductionPlan::where('id', $planId)
                    ->whereNotNull('skipped_at')
                    ->update(['skipped_at' => null, 'status' => 'approved']);
            }
"""
    if 'skipped_at' not in content[start_dandori_match.end():start_dandori_match.end()+200]:
        content = content[:start_dandori_match.end()] + clear_code + content[start_dandori_match.end():]

finish_job_return = re.search(r'preg_match_finwaitcomment_replace_nowhaters_search_this_pattern', content) # dummy

for match in re.finditer(r'return \[[\s\m]*\'runtime\' => \$runtime,[\s\n]*\'efficiency\' => \$efficiency[\s\n]*\];', content):
    finish_job_return = match

if finish_job_return:
    auto_recovery_code = """
            // --- Auto-recovery: queue short-of-plan items ---
            $planId = $this->resolvePlanId($jobId);
            $mismatch = null;
            $autoRecoveryItem = null;
            $job = \App\Models\JobMaster::find($jobId);
            $actualQty = $finalOk ?? 0;

            if ($planId) {
                $plan = \App\Models\ProductionPlan::find($planId);
                if ($plan) {
                    $planQty = (float) ($plan->plan ?? 0);
                    if ($planQty > 0 && $actualQty < $planQty) {
                        $recoveryQty = $planQty - $actualQty;
                        $autoRecoveryItem = $this->createRecoveryItem($plan, $job, $actualQty, $recoveryQty, 'waiting_approval');
                    }
                } else {
                    // Stale plan ID
                    $parts = explode('-', $job->job_number);
                    $jobPrefix = count($parts) > 1 ? implode('-', array_slice($parts, 0, -1)) : $job->job_number;
                    $fallbackPlan = \App\Models\ProductionPlan::whereDate('plan_date', now()->toDateString())
                        ->where('row_type', 'job')
                        ->where('job_no', $jobPrefix)
                        ->first();
                    if ($fallbackPlan) {
                        $mismatch = [
                            'embedded_plan_id' => (int) end($parts),
                            'resolved_plan_id' => $fallbackPlan->id,
                        ];
                        $planQty = (float) ($fallbackPlan->plan ?? 0);
                        if ($planQty > 0 && $actualQty < $planQty) {
                            $recoveryQty = $planQty - $actualQty;
                            $autoRecoveryItem = $this->createRecoveryItem($fallbackPlan, $job, $actualQty, $recoveryQty, 'waiting_approval');
                        }
                    }
                }
            }

            if (!$planId && !$mismatch && !$autoRecoveryItem) {
                $recoveryQty = (float) ($job->target_qty ?? 0) - $actualQty;
                if ($recoveryQty < 0) $recoveryQty = 0;
                $autoRecoveryItem = $this->createRecoveryItem(null, $job, $actualQty, $recoveryQty, 'waiting_approval', true);
            }

            if ($autoRecoveryItem) {
                $this->notifyPpcUsers($job, $autoRecoveryItem);
            }

            // --- Handle skipped jobs ---
            $skippedResult = [];
            foreach ($skippedActions as $skippedJobId => $action) {
                $skippedJob = \App\Models\JobMaster::find($skippedJobId);
                if (!$skippedJob) continue;

                $skippedPlanId = $this->resolvePlanId($skippedJobId);
                $skippedPlan = $skippedPlanId ? \App\Models\ProductionPlan::find($skippedPlanId) : null;
                $status = $action === 'continue' ? 'continue' : 'waiting_approval';

                if ($skippedPlan) {
                    $planQty = (float) ($skippedPlan->plan ?? 0);
                    $recoveryQty = $planQty;
                    $recoveryItem = $this->createRecoveryItem($skippedPlan, $skippedJob, 0, $recoveryQty, $status);
                    $skippedPlan->update(['skipped_at' => now()]);
                } else {
                    $recoveryQty = (float) ($skippedJob->target_qty ?? 0);
                    $recoveryItem = $this->createRecoveryItem(null, $skippedJob, 0, $recoveryQty, $status);
                }

                $skippedResult[] = [
                    'job_id' => $skippedJobId,
                    'action' => $action,
                    'recovery_id' => $recoveryItem->id,
                ];

                $this->notifyPpcUsers($skippedJob, $recoveryItem);
            }

            return [
                'runtime' => $runtime,
                'efficiency' => $efficiency,
                'mismatch' => $mismatch,
                'skipped' => $skippedResult
            ];
"""
    content = content[:finish_job_return.start()] + auto_recovery_code + content[finwish_job_return.end():]

with open('app/Services/ProductionService.php', 'w') as f:
    f.write(content)
