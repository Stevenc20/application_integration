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
        $currentHour = (int) $now->format('H');
        $currentDay = strtolower($now->format('l'));
        $currentMinutes = $now->hour * 60 + $now->minute;

        $shiftName = ($currentHour >= 7 && $currentHour < 19) ? 'Shift Pagi' : 'Shift Malam';

        $breaks = MasterBreakTime::where('is_active', true)
            ->where(function ($q) use ($currentDay) {
                $q->where('hari', $currentDay)->orWhere('hari', 'semua');
            })
            ->where(function ($q) use ($shiftName) {
                $q->where('shift', $shiftName)->orWhereNull('shift');
            })
            ->get();

        if ($breaks->isEmpty()) {
            return 0;
        }

        $runningJobs = JobMaster::where('status', 'running')
            ->whereHas('productionSessions', function ($q) use ($today) {
                $q->where('work_date', $today)->where('status', 'running');
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
                    'problem' => $matchedBreak->label ?? 'BREAK TIME',
                    'penyebab' => '-',
                    'action' => '-',
                    'pic' => 'AUTO BREAK',
                    'start_time' => $now,
                ]);

                $session = ProductionSession::where('job_master_id', $job->id)
                    ->whereDate('work_date', $today)
                    ->where('status', 'running')
                    ->first();
                if ($session) {
                    $session->update(['status' => 'paused', 'pause_time' => $now]);
                }

                JobMaster::where('id', $job->id)->update(['status' => 'paused']);

                $this->log('AUTO BREAK START', $job->job_number, $matchedBreak->label, $now->format('H:i:s'));
                $breakCount++;

            } elseif (!$inBreakWindow && $activeBreak) {
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
                    ['job_master_id' => $job->id, 'work_date' => $today],
                    ['downtime_seconds' => $totalDowntime]
                );

                $session = ProductionSession::where('job_master_id', $job->id)
                    ->whereDate('work_date', $today)
                    ->where('status', 'paused')
                    ->first();
                if ($session) {
                    $session->update(['status' => 'running', 'start_time' => $now]);
                }

                JobMaster::where('id', $job->id)->update(['status' => 'running']);

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
