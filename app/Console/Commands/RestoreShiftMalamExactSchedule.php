<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\JobMaster;
use App\Models\ProductionPlan;
use Illuminate\Support\Facades\DB;

class RestoreShiftMalamExactSchedule extends Command
{
    protected $signature = 'app:restore-shift-malam-schedule';
    protected $description = 'Kembalikan status item shift malam dan shift pagi agar sinkron dengan schedule dan konsol input harian';

    public function handle()
    {
        $this->info('=== SINKRONISASI TOTAL SHIFT PAGI & SHIFT MALAM ===');

        // 1. Pastikan JobMaster Shift Pagi yang sudah selesai statusnya COMPLETE
        DB::table('job_masters')
            ->whereIn('id', [18, 6573])
            ->update([
                'status' => 'complete',
                'finished_at' => '2026-10-21 16:30:00'
            ]);

        // Tutup semua downtime dan session Shift Pagi yang masih NULL
        DB::table('downtimes')
            ->whereNull('finish_time')
            ->whereDate('start_time', '2026-10-21')
            ->where('start_time', '<', '2026-10-21 17:00:00')
            ->update([
                'finish_time' => '2026-10-21 16:30:00',
                'duration_seconds' => DB::raw("TIMESTAMPDIFF(SECOND, start_time, '2026-10-21 16:30:00')")
            ]);

        // production_sessions menggunakan 'total_seconds' dan status 'finished'
        DB::table('production_sessions')
            ->whereNull('finish_time')
            ->whereDate('start_time', '2026-10-21')
            ->where('start_time', '<', '2026-10-21 17:00:00')
            ->update([
                'finish_time' => '2026-10-21 16:30:00',
                'total_seconds' => DB::raw("TIMESTAMPDIFF(SECOND, start_time, '2026-10-21 16:30:00')"),
                'status' => 'finished'
            ]);

        // 2. SHIFT MALAM: Aktifkan 4 Line masing-masing 1 Job Utama sesuai jadwal
        // Catatan: production_plans.status enum adalah: 'pending', 'approved', 'completed'
        // JobMaster.status enum mendukung: 'running', 'pending', 'complete'
        $malamRunningJobs = [
            'PRESS A' => ['plan_id' => 17040, 'jm_id' => 6696, 'job_no' => 'PVS-003B1', 'start' => '21:05', 'finish' => '22:00'],
            'PRESS B' => ['plan_id' => 17059, 'jm_id' => 6697, 'job_no' => 'PB-062', 'start' => '21:05', 'finish' => '22:00'],
            'PRESS C' => ['plan_id' => 17066, 'jm_id' => 6698, 'job_no' => 'PV-005BA1 BLK', 'start' => '21:05', 'finish' => '22:00'],
            'PRESS D' => ['plan_id' => 17069, 'jm_id' => 6699, 'job_no' => 'AAS-054 FNS', 'start' => '21:05', 'finish' => '22:00'],
        ];

        foreach ($malamRunningJobs as $line => $dt) {
            // Update ProductionPlan Shift Malam menjadi 'approved'
            DB::table('production_plans')
                ->where('id', $dt['plan_id'])
                ->update([
                    'shift_name' => 'Shift Malam',
                    'press_name' => $line,
                    'status' => 'approved',
                    'row_type' => 'job',
                    'start_time' => $dt['start'],
                    'finish_time' => $dt['finish'],
                ]);

            // Update JobMaster agar RUNNING aktif di Input Harian
            DB::table('job_masters')
                ->where('id', $dt['jm_id'])
                ->update([
                    'status' => 'running',
                    'started_at' => '2026-10-21 17:00:00',
                    'finished_at' => null,
                ]);

            $this->line("[SHIFT MALAM - {$line}] Job {$dt['job_no']} di-set aktif RUNNING.");
        }

        // Job antrean lainnya di Shift Malam di-set PENDING (agar tidak menumpuk di console)
        DB::table('job_masters')
            ->whereIn('id', [6700, 6701, 6702])
            ->update([
                'status' => 'pending',
                'started_at' => null,
                'finished_at' => null,
            ]);

        DB::table('production_plans')
            ->whereIn('id', [17060, 17061, 17063])
            ->update([
                'status' => 'pending',
            ]);

        $this->info('=== BERHASIL! DATA SHIFT MALAM & SHIFT PAGI SUDAH 100% SINKRON & BERSIH ===');
        return 0;
    }
}
