<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Dandori;
use App\Models\Downtime;
use App\Models\JobMaster;
use App\Models\ProductionSession;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FixOrphanedDandoriDurations extends Command
{
    protected $signature = 'production:fix-orphaned-dandori';
    protected $description = 'Close orphaned Dandori & Downtime records whose job has already started production or completed';

    public function handle(): int
    {
        $this->info("=== SCANNING & FIXING ORPHANED DANDORI & DOWNTIME RECORDS ===");

        // 1. Find open Dandori records (finish_time is null)
        $openDandoris = Dandori::whereNull('finish_time')->get();
        $this->info("Found {$openDandoris->count()} open Dandori records.");

        $fixedDandoriCount = 0;
        foreach ($openDandoris as $d) {
            $job = $d->nextJob;
            if (!$job) continue;

            $dStart = $d->start_time ? Carbon::parse($d->start_time) : null;
            if (!$dStart) continue;

            $finishTime = null;

            // Anchor 1: When job started actual production
            if ($job->started_at && Carbon::parse($job->started_at) > $dStart) {
                $finishTime = Carbon::parse($job->started_at);
            }
            // Anchor 2: When job has production session
            if (!$finishTime) {
                $firstSession = ProductionSession::where('job_master_id', $job->id)
                    ->whereNotNull('start_time')
                    ->where('start_time', '>', $d->start_time)
                    ->orderBy('start_time')
                    ->first();
                if ($firstSession) {
                    $finishTime = Carbon::parse($firstSession->start_time);
                }
            }
            // Anchor 3: When job was finished
            if (!$finishTime && $job->finished_at && Carbon::parse($job->finished_at) > $dStart) {
                $finishTime = Carbon::parse($job->finished_at);
            }

            if ($finishTime) {
                $durMinutes = abs($finishTime->diffInSeconds($dStart)) / 60;
                $d->update([
                    'finish_time' => $finishTime,
                    'duration_minutes' => round($durMinutes, 2)
                ]);
                $this->info("✓ Closed Dandori ID {$d->id} for Job {$job->job_number}: Start {$dStart->format('H:i')}, Finish {$finishTime->format('H:i')} (" . round($durMinutes, 1) . "m)");
                $fixedDandoriCount++;
            }
        }

        // 2. Find open Downtimes (finish_time is null) on jobs that are complete or started
        $openDowntimes = Downtime::whereNull('finish_time')->get();
        $fixedDtCount = 0;
        foreach ($openDowntimes as $dt) {
            $job = $dt->jobMaster;
            if (!$job) continue;

            $dtStart = $dt->start_time ? Carbon::parse($dt->start_time) : null;
            if (!$dtStart) continue;

            $finishTime = null;
            if (in_array(strtolower($job->status), ['complete', 'finished', 'done']) && $job->finished_at) {
                $finishTime = Carbon::parse($job->finished_at);
            } elseif (strtolower($dt->jenis_downtime) === 'dandori' && $job->started_at && Carbon::parse($job->started_at) > $dtStart) {
                $finishTime = Carbon::parse($job->started_at);
            }

            if ($finishTime) {
                $durSeconds = abs($finishTime->diffInSeconds($dtStart));
                $dt->update([
                    'finish_time' => $finishTime,
                    'duration_seconds' => $durSeconds
                ]);
                $this->info("✓ Closed Downtime ID {$dt->id} ({$dt->jenis_downtime}) for Job {$job->job_number}: Start {$dtStart->format('H:i')}, Finish {$finishTime->format('H:i')}");
                $fixedDtCount++;
            }
        }

        $this->info("=== SUMMARY: Fixed {$fixedDandoriCount} Dandori records, {$fixedDtCount} Downtime records ===");

        return 0;
    }
}
