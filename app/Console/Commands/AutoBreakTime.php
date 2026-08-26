<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MasterBreakTime;
use App\Models\JobMaster;
use App\Models\Downtime;
use App\Models\DailyProduction;
use App\Models\ProductionSession;
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
        $today = $now->toDateString();
        $logicalNow = clone $now;
        if ($now->hour < 7 || ($now->hour == 7 && $now->minute < 30)) {
            $logicalNow->subDay();
        }
        $workDate = $logicalNow->toDateString();

        $currentHour = (int) $now->format('H');
        $currentDayIndo = MasterBreakTime::getIndonesianDayName($now);
        $currentDayEn = strtolower($now->format('l'));
        $currentMinutes = $now->hour * 60 + $now->minute;

        $shiftName = ($currentHour >= 7 && $currentHour < 19) ? 'Shift Pagi' : 'Shift Malam';

        $breaks = MasterBreakTime::where('is_active', true)
            ->where(function ($q) use ($currentDayIndo, $currentDayEn) {
                $q->whereIn('hari', [$currentDayIndo, $currentDayEn])->orWhere('hari', 'semua');
            })
            ->where(function ($q) use ($shiftName) {
                $q->where('shift', $shiftName)->orWhereNull('shift');
            })
            ->get();

        if ($breaks->isEmpty()) {
            return 0;
        }

        $runningJobs = JobMaster::whereIn('status', ['running', 'paused'])
            ->whereHas('productionSessions', function ($q) use ($workDate) {
                $q->where('work_date', $workDate)
                  ->whereIn('status', ['running', 'paused']);
            })
            ->get();

        if ($runningJobs->isEmpty()) {
            return 0;
        }

        $breakCount = 0;
        foreach ($runningJobs as $job) {
            $activeBreak = Downtime::where('job_master_id', $job->id)
                ->whereNull('finish_time')
                ->where('jenis_downtime', 'break time')
                ->where('source', 'AUTO')
                ->first();

            $inBreakWindow = false;
            $matchedBreak = null;

            foreach ($breaks as $b) {
                $startMin = MasterBreakTime::timeToMinutes(substr($b->waktu_mulai, 0, 5));
                $endMin = MasterBreakTime::timeToMinutes(substr($b->waktu_selesai, 0, 5));
                if ($currentMinutes >= $startMin && $currentMinutes < $endMin) {
                    $inBreakWindow = true;
                    $matchedBreak = $b;
                    break;
                }
            }

            if ($inBreakWindow && !$activeBreak) {
                $downtime = Downtime::create([
                    'job_master_id' => $job->id,
                    'jenis_downtime' => 'break time',
                    'source' => 'AUTO',
                    'problem' => $matchedBreak->label ?? 'BREAK TIME',
                    'penyebab' => '-',
                    'action' => '-',
                    'pic' => 'AUTO BREAK',
                    'start_time' => $now,
                ]);

                // FIXED: Use production service to properly calculate total_seconds
                app(\App\Services\ProductionService::class)->pauseJob($job->id);

                $this->log('AUTO BREAK START', $job->job_number, $matchedBreak->label, $now->format('H:i:s'));
                $breakCount++;

            } elseif (!$inBreakWindow && $activeBreak && $activeBreak->source === 'AUTO') {
                $startTime = Carbon::parse($activeBreak->start_time);
                $duration = abs($now->diffInSeconds($startTime));

                $activeBreak->update([
                    'finish_time' => $now,
                    'duration_seconds' => $duration,
                ]);

                $totalDowntime = Downtime::where('job_master_id', $job->id)
                    ->whereDate('created_at', $today)
                    ->where('jenis_downtime', '!=', 'dandori')
                    ->sum('duration_seconds');

                DailyProduction::updateOrCreate(
                    ['job_master_id' => $job->id, 'work_date' => $workDate],
                    ['downtime_seconds' => $totalDowntime]
                );

                $otherActiveDowntime = Downtime::where('job_master_id', $job->id)
                    ->whereNull('finish_time')
                    ->exists();

                if (!$otherActiveDowntime) {
                    // FIXED: Use production service to properly reset start_time for timeline segmenting
                    app(\App\Services\ProductionService::class)->resumeJob($job->id);
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
