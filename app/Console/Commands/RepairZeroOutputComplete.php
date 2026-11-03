<?php

namespace App\Console\Commands;

use App\Models\DailyProduction;
use App\Models\JobMaster;
use App\Models\ProductionLog;
use App\Models\ProductionPlan;
use App\Services\DashboardRealtimeService;
use App\Services\ProductionMetricsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RepairZeroOutputComplete extends Command
{
    protected $signature = 'production:repair-zero-complete
                            {--date= : Tanggal plan (Y-m-d). Default: tanggal produksi saat ini}
                            {--line= : Filter line/press, mis. A / Line A / Press A}
                            {--apply : Tulis perubahan. Tanpa flag ini hanya dry-run}';

    protected $description = 'Reset item yang berstatus complete tetapi total output-nya 0 menjadi pending (aman, bisa dry-run).';

    private const COMPLETE_STATUSES = ['complete', 'completed', 'finished', 'closed', 'done'];

    public function handle(): int
    {
        $date = $this->option('date')
            ? \Carbon\Carbon::parse($this->option('date'))->toDateString()
            : ProductionMetricsService::currentProductionDate();

        $line = trim((string) $this->option('line'));
        $normalizedLine = $line !== ''
            ? strtoupper(trim(str_replace(['Line ', 'LINE ', 'Press ', 'PRESS '], '', $line)))
            : null;

        $apply = (bool) $this->option('apply');

        $this->info('=== REPAIR ZERO-OUTPUT COMPLETE ===');
        $this->line("Tanggal plan : {$date}");
        $this->line('Line         : ' . ($line !== '' ? $line : 'SEMUA'));
        $this->line('Mode         : ' . ($apply ? 'APPLY' : 'DRY-RUN (tambahkan --apply untuk menulis)'));
        $this->newLine();

        $plans = ProductionPlan::whereDate('plan_date', $date)
            ->where('row_type', 'job')
            ->when($normalizedLine !== null, function ($query) use ($normalizedLine, $line) {
                $query->where(function ($q) use ($normalizedLine, $line) {
                    $q->whereRaw(
                        "REPLACE(REPLACE(UPPER(TRIM(COALESCE(press_name, ''))), 'PRESS ', ''), 'LINE ', '') LIKE ?",
                        ["%{$normalizedLine}%"]
                    )->orWhere('press_name', 'LIKE', "%{$line}%");
                });
            })
            ->orderBy('press_name')
            ->orderBy('row_no')
            ->get();

        $candidates = 0;
        $repaired = 0;

        foreach ($plans as $plan) {
            $jobs = JobMaster::where(function ($q) use ($plan) {
                $q->where('job_number', 'LIKE', "%-{$plan->id}")
                    ->orWhere('job_number', $plan->job_no);
            })->get();

            $planStatus = strtolower((string) $plan->status);
            $jobStatuses = $jobs->map(fn ($job) => strtolower((string) $job->status));

            $planIsComplete = in_array($planStatus, self::COMPLETE_STATUSES, true);
            $hasCompleteJob = $jobStatuses->contains(fn ($status) => in_array($status, self::COMPLETE_STATUSES, true));

            if (! $planIsComplete && ! $hasCompleteJob) {
                continue;
            }

            $jobIds = $jobs->pluck('id')->all();

            $dailyTotal = $jobIds
                ? (float) DailyProduction::whereIn('job_master_id', $jobIds)
                    ->selectRaw('COALESCE(SUM(actual_ok + actual_repair + actual_reject), 0) as total')
                    ->value('total')
                : 0.0;

            $logTotal = $jobIds
                ? (float) ProductionLog::whereIn('job_master_id', $jobIds)
                    ->selectRaw('COALESCE(SUM(ok_qty + repair_qty + reject_qty), 0) as total')
                    ->value('total')
                : 0.0;

            $planTotal = (float) ($plan->ok ?? 0) + (float) ($plan->repair ?? 0) + (float) ($plan->reject ?? 0);
            $totalOutput = $dailyTotal + $logTotal + $planTotal;

            if ($totalOutput > 0) {
                continue;
            }

            $candidates++;

            $this->warn(sprintf(
                'Plan #%d | %s | %s | plan=%s | status plan=%s | status job=%s | output=0',
                $plan->id,
                $plan->press_name ?: '-',
                $plan->job_no ?: ($plan->job_master ?: '-'),
                (float) ($plan->plan ?? 0),
                $plan->status ?: '-',
                $jobStatuses->implode(',') ?: '-'
            ));

            if (! $apply) {
                continue;
            }

            DB::transaction(function () use ($plan, $jobs) {
                $plan->update([
                    'status' => 'pending',
                    'act_start' => null,
                    'act_finish' => null,
                ]);

                foreach ($jobs as $job) {
                    if (! in_array(strtolower((string) $job->status), self::COMPLETE_STATUSES, true)) {
                        continue;
                    }

                    $job->update([
                        'status' => 'pending',
                        'started_at' => null,
                        'finished_at' => null,
                    ]);
                }
            });

            $repaired++;
        }

        if ($apply && $repaired > 0) {
            DashboardRealtimeService::flushCache();
        }

        $this->newLine();
        $this->info("Kandidat false-complete : {$candidates}");
        $this->info('Berhasil direset       : ' . ($apply ? $repaired : 0));

        if (! $apply && $candidates > 0) {
            $this->warn('Belum ada data yang diubah. Jalankan ulang dengan --apply untuk menerapkan perbaikan.');
        }

        return self::SUCCESS;
    }
}
