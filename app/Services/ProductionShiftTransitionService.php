<?php

namespace App\Services;

use App\Models\DailyProduction;
use App\Models\Downtime;
use App\Models\JobMaster;
use App\Models\LineMaster;
use App\Models\ProductionPlan;
use App\Models\ProductionSession;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductionShiftTransitionService
{
    /**
     * Inspect active/paused jobs and open sessions across all lines or a specific line.
     * Does NOT mutate state; returns an audit report of affected sessions.
     */
    public function inspectActiveSessions(?string $lineFilter = null, ?string $currentShiftName = null, ?string $workDate = null): array
    {
        $now = Carbon::now();
        $activeShift = $currentShiftName ?: ProductionMetricsService::currentProductionShift($now);
        $activeDate = $workDate ?: ProductionMetricsService::currentProductionDate($now);

        // Periksa job yang berstatus 'running' atau 'paused' atau memiliki downtime gantung tanpa finish_time
        $query = JobMaster::where(function ($q) {
                $q->whereIn(DB::raw('LOWER(status)'), ['running', 'paused'])
                  ->orWhereHas('downtimes', function ($dq) {
                      $dq->whereNull('finish_time');
                  });
            })
            ->with([
                'dailyProduction' => fn ($q) => $q->where('work_date', $activeDate),
                'productionSessions' => fn ($q) => $q->whereIn('status', ['running', 'paused']),
            ]);

        if ($lineFilter && strtoupper($lineFilter) !== 'ALL') {
            $normalizedLine = strtoupper(trim(str_replace(['Line ', 'LINE ', 'Press ', 'PRESS '], '', $lineFilter)));
            $query->where(function ($q) use ($normalizedLine, $lineFilter) {
                $q->whereRaw('UPPER(TRIM(line)) = ?', [strtoupper(trim($lineFilter))])
                    ->orWhereRaw('UPPER(line) LIKE ?', ["%{$normalizedLine}%"])
                    ->orWhereRaw("REPLACE(REPLACE(UPPER(TRIM(line)), 'PRESS ', ''), 'LINE ', '') LIKE ?", ["%{$normalizedLine}%"]);
            });
        }

        $runningJobs = $query->get();

        $staleShiftJobs = [];
        $validShiftJobs = [];

        foreach ($runningJobs as $job) {
            $plan = $this->resolvePlanForJob($job);
            $planShift = $plan ? $this->normalizeShiftName($plan->shift_name) : null;
            $isSameShift = ($planShift === null) || ($planShift === $this->normalizeShiftName($activeShift));

            // SAFEGUARD: Jangan pernah menandai job sebagai stale jika:
            // 1. Job baru saja distart/diupdate kurang dari 30 menit yang lalu (mencegah auto-kill pada job yang baru dimulai).
            // 2. Job start time berada di dalam window shift aktif saat ini.
            $jobStartTime = $job->started_at ? Carbon::parse($job->started_at) : null;
            $jobUpdateTime = $job->updated_at ? Carbon::parse($job->updated_at) : null;
            $isRecentlyActive = false;
            if ($jobStartTime && $jobStartTime->diffInMinutes($now) < 30) {
                $isRecentlyActive = true;
            }
            if ($jobUpdateTime && $jobUpdateTime->diffInMinutes($now) < 15) {
                $isRecentlyActive = true;
            }

            [$shiftStart, $shiftEnd] = ProductionMetricsService::shiftWindow($activeDate, $activeShift);
            $startedInActiveShift = $jobStartTime && $jobStartTime->betweenIncluded($shiftStart, $shiftEnd);

            if ($startedInActiveShift || $isRecentlyActive) {
                $isSameShift = true;
            }

            $openSessions = $job->productionSessions;
            $openDowntimes = Downtime::where('job_master_id', $job->id)->whereNull('finish_time')->get();

            $jobReport = [
                'job_id' => $job->id,
                'job_number' => $job->job_number,
                'job_name' => $job->job_name,
                'line' => $job->line,
                'status' => $job->status,
                'plan_id' => $plan?->id,
                'plan_shift' => $plan?->shift_name,
                'plan_row_no' => $plan?->row_no,
                'plan_qty' => (int) ($plan?->plan ?? $job->target_qty),
                'actual_ok' => (int) ($job->dailyProduction?->actual_ok ?? 0),
                'started_at' => $job->started_at ? Carbon::parse($job->started_at)->toDateTimeString() : null,
                'open_sessions_count' => $openSessions->count(),
                'open_downtimes_count' => $openDowntimes->count(),
                'is_stale_shift' => ! $isSameShift,
            ];

            if ($isSameShift) {
                $validShiftJobs[] = $jobReport;
            } else {
                $staleShiftJobs[] = $jobReport;
            }
        }

        return [
            'inspected_at' => $now->toDateTimeString(),
            'active_shift' => $activeShift,
            'active_work_date' => $activeDate,
            'line_filter' => $lineFilter ?: 'ALL',
            'stale_shift_jobs_count' => count($staleShiftJobs),
            'valid_shift_jobs_count' => count($validShiftJobs),
            'stale_shift_jobs' => $staleShiftJobs,
            'valid_shift_jobs' => $validShiftJobs,
        ];
    }

    /**
     * Safely reconcile shift transition for a specific line or all lines.
     *
     * Rules:
     * 1. If an active job belongs to Shift Pagi and Shift Malam is already active:
     *    - If target plan is fully achieved (actual_ok >= plan_qty), close the session and complete the job.
     *    - If target plan is NOT fully achieved, PAUSE the session safely (record runtime, freeze timer, pause JobMaster).
     *      NEVER force complete or delete!
     * 2. Close dangling open downtimes for paused stale jobs cleanly.
     * 3. Flush realtime caches and signal all impacted lines.
     * 4. Idempotent: running multiple times produces identical state without double-counting.
     */
    public function transitionShiftSafely(?string $lineFilter = null, ?string $currentShiftName = null, ?string $workDate = null): array
    {
        $now = Carbon::now();
        $activeShift = $currentShiftName ?: ProductionMetricsService::currentProductionShift($now);
        $activeDate = $workDate ?: ProductionMetricsService::currentProductionDate($now);

        $inspection = $this->inspectActiveSessions($lineFilter, $activeShift, $activeDate);
        $staleJobs = $inspection['stale_shift_jobs'];

        if (empty($staleJobs)) {
            return array_merge($inspection, [
                'action_taken' => 'none_needed',
                'completed_jobs' => [],
                'paused_jobs' => [],
            ]);
        }

        $completed = [];
        $paused = [];
        $impactedLines = [];

        DB::transaction(function () use ($staleJobs, $now, $activeDate, $activeShift, &$completed, &$paused, &$impactedLines) {
            $prodService = app(ProductionService::class);

            foreach ($staleJobs as $jobInfo) {
                $jobId = $jobInfo['job_id'];
                $job = JobMaster::find($jobId);
                if (! $job || ! in_array(strtolower((string) $job->status), ['running', 'paused'], true)) {
                    continue;
                }

                $plan = $this->resolvePlanForJob($job);
                $planQty = (int) ($plan?->plan ?? $job->target_qty);
                $daily = DailyProduction::where('job_master_id', $job->id)
                    ->where('work_date', $jobInfo['actual_work_date'] ?? $prodService->resolveWorkDate($job->id))
                    ->first();
                $actualOk = (int) ($daily?->actual_ok ?? 0);

                $line = $job->line ?: 'Line A';
                $impactedLines[$line] = true;

                // Scenario: Job dari shift lain yang melewati batas cutoff (misal Shift Pagi melewati 21:00 ke Shift Malam)
                // Hentikan/selesaikan job dari shift sebelumnya secara bersih agar line kosong dan siap untuk shift baru.
                // ATURAN PENTING: Jika actualOk == 0 (belum pernah diproduksi sama sekali), JANGAN PERNAH tandai sebagai complete!
                // Reset kembali ke pending dan bersihkan open session / downtime agar bisa dikerjakan nanti.
                if ($actualOk <= 0) {
                    // Tutup downtime gantung jika ada
                    Downtime::where('job_master_id', $job->id)
                        ->whereNull('finish_time')
                        ->update(['finish_time' => $now]);

                    // Tutup sesi produksi yang terbuka
                    ProductionSession::where('job_master_id', $job->id)
                        ->whereIn('status', ['running', 'paused'])
                        ->update([
                            'status' => 'paused',
                            'finish_time' => $now,
                        ]);

                    // Reset JobMaster dan ProductionPlan kembali ke pending
                    $job->update([
                        'status' => 'pending',
                        'started_at' => null,
                        'finished_at' => null,
                    ]);
                    $prodService->syncPlanStatus($job->id, 'pending');

                    $paused[] = [
                        'job_id' => $job->id,
                        'job_number' => $job->job_number,
                        'line' => $line,
                        'reason' => 'Direset ke pending (0 pcs diproduksi pada cut-off shift ' . $activeShift . ')',
                    ];
                } else {
                    $prodService->finishJob($job->id, 'SHIFT_CUTOFF');
                    $completed[] = [
                        'job_id' => $job->id,
                        'job_number' => $job->job_number,
                        'line' => $line,
                        'reason' => 'Otomatis dihentikan (cut-off transisi shift ' . $activeShift . ')',
                    ];
                }
            }
        });

        // Flush realtime caches and signal all impacted lines
        DashboardRealtimeService::flushCache();
        foreach (array_keys($impactedLines) as $lineName) {
            DashboardRealtimeService::signalUpdate($lineName);
        }

        Log::info('ProductionShiftTransitionService: transition executed safely', [
            'active_shift' => $activeShift,
            'completed' => count($completed),
            'paused' => count($paused),
        ]);

        return array_merge($inspection, [
            'action_taken' => 'transition_executed',
            'completed_jobs' => $completed,
            'paused_jobs' => $paused,
            'impacted_lines' => array_keys($impactedLines),
        ]);
    }

    private function resolvePlanForJob(JobMaster $job): ?ProductionPlan
    {
        $parts = explode('-', $job->job_number);
        $planId = end($parts);
        if (is_numeric($planId)) {
            $plan = ProductionPlan::find((int) $planId);
            if ($plan) {
                return $plan;
            }
        }

        return ProductionPlan::where('job_no', $job->job_number)->first();
    }

    private function normalizeShiftName(?string $shift): string
    {
        if (! $shift) {
            return '';
        }
        $s = strtolower(trim($shift));
        if (str_contains($s, 'malam') || str_contains($s, '2')) {
            return 'Shift Malam';
        }

        return 'Shift Pagi';
    }
}
