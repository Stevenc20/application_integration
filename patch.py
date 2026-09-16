import re

with open('app/Services/CutOffService.php', 'r') as f:
    content = f.read()

phase1_logic = r"""
            $stats['carried'] = 0;
            $stats['cancelled'] = 0;

            // --- Phase 1: Handle existing 'continue' items ---
            $continueItems = RecoveryItem::where('status', 'continue')
                ->whereDate('source_date', $date)
                ->where('source_shift', $shiftName)
                ->get();

            $nextShiftName = $shiftName === 'Shift Pagi' ? 'Shift Malam' : 'Shift Pagi';
            $nextDate = $shiftName === 'Shift Pagi' ? $date : \Carbon\Carbon::parse($date)->addDay()->toDateString();

            foreach ($continueItems as $item) {
                $linkedPlan = $item->production_plan_id ? \App\Models\ProductionPlan::find($item->production_plan_id) : null;
                $actualQty = $linkedPlan ? (float)($linkedPlan->ok ?? 0) : 0;
                $planQty = $linkedPlan ? (float)($linkedPlan->plan ?? 0) : (float)$item->plan_qty;

                if ($actualQty >= $planQty) {
                    $item->update([.status. => .completed.]); // Need to fix quotes later
                    $stats['cancelled']++;
                    continue;
                }

                $recoveryQty = max(0, $planQty - $actualQty);

                $item->update([
                    'status' => 'scheduled',
                    'source_date' => $nextDate,
                    'source_shift' => $nextShiftName,
                    'recovery_qty' => $recoveryQty
                ]);

                \App\Models\ProductionPlan::create([
                    "plan_date" => $nextDate,
                    "shift_name" => $nextShiftName,
                    "press_name" => $item->press_name,
                    "row_type" => "job",
                    "job_no" => $item->job_no,
                    "job_master" => $item->job_master ?? $item->job_name,
                    "plan" => $recoveryQty,
                    "ct_detik" => $item->ct_detik,
                    "dct" => $item->dct,
                    "total_mesin" => $item->total_mesin,
                    "source_type" => "recovery",
                    "recovery_id" => $item->id,
                    "row_no" => 9999
                ]);

                $stats['carried']++;
            }

            $existingPlanIds = RecoveryItem::whereIn('status', ['waiting_approval', 'scheduled', 'in_production', 'continue'])
                ->pluck('production_plan_id')
                ->filter()
                ->toArray();
"""
phase1_logic = phase1_logic.replace('.status. => .completed.', "'status' => 'completed'")

content = re.sub(
    r"// --- Phase 1: Handle existing 'continue' items ---\s*&continueItems = RecoveryItem::where\('status', 'continue'\)\s*->whereDate\('source_date', \$date\)\s*->where\('source_shift', \$shiftName\)\s*->get\();",
    phase1_logic,
    content
)

content = content.replace(
    "->where('row_type', 'job')",
    "->where('row_type', 'job')\n                ->whereNotIn('id', $existingPlanIds)"
J
with open('app/Services/CutOffService.php', 'w') as f:
    f.write(content)
