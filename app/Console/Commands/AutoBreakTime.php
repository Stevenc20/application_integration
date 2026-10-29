<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MasterBreakTime;
use App\Models\JobMaster;
use App\Models\Downtime;
use App\Models\DailyProduction;
use App\Models\ProductionSession;
use App\Services\ProductionMetricsService;
use App\Services\ProductionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AutoBreakTime extends Command
{
    protected $signature = 'break:auto';
    protected $description = 'Auto-pause/resume running jobs during scheduled break times';

    public function handle(ProductionService $productionService): int
    {
        $now = Carbon::now();
        $productionDate = ProductionMetricsService::currentProductionDate($now);
        $shiftName = ProductionMetricsService::currentProductionShift($now);
        $currentMinutes = $now->hour * 60 + $now->minute;

        $breaks = MasterBreakTime::getApplicableBreaks($productionDate, $shiftName);

        if ($breaks->isEmpty()) {
            return 0;
        }

        $runningJobs = JobMaster::whereIn('status', ['running', 'paused'])
            ->whereHas('productionSessions', function ($q) use ($productionDate) {
                $q->where('work_date', $productionDate)->whereIn('status', ['running', 'paused']);
            })
            ->get();

        if ($runningJobs->isEmpty()) {
            return 0;
        }

        $breakCount = 0;
        foreach ($runningJobs as $job) {
            $effectiveWorkDate = $productionService->resolveWorkDate($job->id, $productionDate);

            $activeBreak = Downtime::where('job_master_id', $job->id)
                ->whereNull('finish_time')
                ->where('jenis_downtime', 'break time')
                ->first();

            $inBreakWindow = false;
            $matchedBreak = null;

            foreach ($breaks as $b) {
                $startMin = MasterBreakTime::timeToMinutes(substr($b->waktu_mulai, 0, 5));
                $endMin = MasterBreakTime::timeToMinutes(substr($b->waktu_selesai, 0, 5));
                $matches = ($startMin > $endMin)
                    ? ($currentMinutes >= $startMin || $currentMinutes < $endMin)
                    : ($currentMinutes >= $startMin && $currentMinutes < $endMin);

                if ($matches) {
                    $inBreakWindow = true;
                    $matchedBreak = $b;
                    break;
                }
            }

            if ($inBreakWindow && !$activeBreak) {
                $downtime = Downtime::create([
                    'job_master_id' => $job->id,
                    'jenis_downtime' => 'break time',
                    'problem' => $matchedBreak->label ?? 'BREAK TIME',
                    'penyebab' => '-',
                    'action' => '-',
                    'pic' => 'AUTO BREAK',
                    'source' => 'AUTO',
                    'image_path' => null,
                    'image_path_part' => null,
                    'image_path_tooling' => null,
                    'start_time' => $now,
                ]);

                $session = ProductionSession::where('job_master_id', $job->id)
                    ->whereDate('work_date', $effectiveWorkDate)
                    ->where('status', 'running')
                    ->first();
                if ($session) {
                    $runtime = $productionService->calculateRuntime($job->id);
                    $session->update([
                        'total_seconds' => $runtime,
                        'status' => 'paused',
                        'pause_time' => $now
                    ]);
                    DailyProduction::updateOrCreate(
                        ['job_master_id' => $job->id, 'work_date' => $effectiveWorkDate],
                        ['runtime_seconds' => $runtime]
                    );
                }

                JobMaster::where('id', $job->id)->update(['status' => 'paused']);
                if ($job->line) {
                    \App\Services\DashboardRealtimeService::signalUpdate($job->line);
                }

                $this->log('AUTO BREAK START', $job->job_number, $matchedBreak->label, $now->format('H:i:s'));
                $breakCount++;

            } elseif (!$inBreakWindow && $activeBreak) {
                $startTime = Carbon::parse($activeBreak->start_time);
                $duration = abs($now->diffInSeconds($startTime));

                $activeBreak->update([
                    'finish_time' => $now,
                    'duration_seconds' => $duration,
                ]);

                $windowStart = Carbon::parse($effectiveWorkDate)->setTime(7, 0, 0);
                $windowEnd = Carbon::parse($effectiveWorkDate)->addDay()->setTime(7, 30, 0);

                $totalDowntime = Downtime::where('job_master_id', $job->id)
                    ->where('jenis_downtime', '!=', 'dandori')
                    ->where(function ($q) use ($windowStart, $windowEnd, $effectiveWorkDate) {
                        $q->whereBetween('start_time', [$windowStart, $windowEnd])
                          ->orWhere(function ($sub) use ($windowStart, $windowEnd) {
                              $sub->whereNull('start_time')
                                  ->whereBetween('created_at', [$windowStart, $windowEnd]);
                          })
                          ->orWhereDate('created_at', $effectiveWorkDate);
                    })
                    ->sum('duration_seconds');

                DailyProduction::updateOrCreate(
                    ['job_master_id' => $job->id, 'work_date' => $effectiveWorkDate],
                    ['downtime_seconds' => $totalDowntime]
                );

                $session = ProductionSession::where('job_master_id', $job->id)
                    ->whereDate('work_date', $effectiveWorkDate)
                    ->where('status', 'paused')
                    ->first();
                if ($session) {
                    $session->update([
                        'status' => 'running',
                        'start_time' => $now,
                        'pause_time' => null
                    ]);
                }

                JobMaster::where('id', $job->id)->update(['status' => 'running']);
                if ($job->line) {
                    \App\Services\DashboardRealtimeService::signalUpdate($job->line);
                }

                $this->log('AUTO BREAK END', $job->job_number, $activeBreak->problem, $now->format('H:i:s'));
                $breakCount++;
            }
        }

        if ($breakCount > 0) {
            $this->info("AutoBreakTime: processed $breakCount break transitions at {$now->format('H:i:s')}");
        }

        return 0;
    }

    private function log(string $event, string $jobNo, string $label, string $time): void
    {
        $logPath = storage_path('logs/auto-break.log');
        $line = "[{$time}] {$event} | Job: {$jobNo} | {$label}\n";
        file_put_contents($logPath, $line, FILE_APPEND | LOCK_EX);
    }
}
