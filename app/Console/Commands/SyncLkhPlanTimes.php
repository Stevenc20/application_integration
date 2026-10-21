<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ProductionPlan;
use Illuminate\Support\Facades\DB;

class SyncLkhPlanTimes extends Command
{
    protected $signature = 'app:sync-lkh-plan-times';
    protected $description = 'Sync exact start_time and finish_time from Excel 21-Oktober-2026 into ProductionPlan';

    public function handle()
    {
        $this->info('Mulai sinkronisasi jam Plan Start dan Finish dari Excel asli PPC...');

        // Jadwal Asli dari File Excel 18. Stamping 21-Oktober-2026.xlsx
        $scheduleData = [
            // PRESS A
            ['press' => 'PRESS A', 'job_no' => 'AES-024 WIP', 'start' => '07:40', 'finish' => '08:49'],
            ['press' => 'PRESS A', 'job_no' => 'RCS-017', 'start' => '08:49', 'finish' => '09:53'],
            ['press' => 'PRESS A', 'job_no' => 'PVS-001B1', 'start' => '09:53', 'finish' => '10:33'],
            ['press' => 'PRESS A', 'job_no' => 'K4045', 'start' => '10:33', 'finish' => '11:36'],
            ['press' => 'PRESS A', 'job_no' => 'XP-0078', 'start' => '11:36', 'finish' => '13:13'],
            ['press' => 'PRESS A', 'job_no' => 'BLK RC-2764, RC-2770, AES-012', 'start' => '13:13', 'finish' => '14:26'],
            ['press' => 'PRESS A', 'job_no' => 'PVS-003B1', 'start' => '14:26', 'finish' => '15:21'],
            ['press' => 'PRESS A', 'job_no' => 'PB-016 FNS', 'start' => '15:21', 'finish' => '16:01'],
            ['press' => 'PRESS A', 'job_no' => 'PB-015 FNS', 'start' => '16:01', 'finish' => '18:56'],
            ['press' => 'PRESS A', 'job_no' => 'AA-3446 WIP', 'start' => '18:56', 'finish' => '19:54'],

            // Tambahan job lain jika nama berbeda
            ['press' => 'PRESS A', 'job_no' => 'AES-028 WIP', 'start' => '07:40', 'finish' => '08:30'],
            ['press' => 'PRESS A', 'job_no' => 'GT-5057', 'start' => '08:30', 'finish' => '09:15'],
            ['press' => 'PRESS A', 'job_no' => 'K1014', 'start' => '09:15', 'finish' => '10:15'],
            ['press' => 'PRESS A', 'job_no' => 'RCS-011', 'start' => '10:15', 'finish' => '11:15'],
        ];

        $updated = 0;
        foreach ($scheduleData as $s) {
            $count = DB::table('production_plans')
                ->whereDate('plan_date', '2026-10-21')
                ->where('shift_name', 'Shift Pagi')
                ->where('press_name', $s['press'])
                ->where('job_no', $s['job_no'])
                ->update([
                    'start_time' => $s['start'],
                    'finish_time' => $s['finish'],
                ]);
            if ($count > 0) {
                $this->line("Updated {$s['press']} - {$s['job_no']}: {$s['start']} -> {$s['finish']}");
                $updated += $count;
            }
        }

        // Default cascade bagi item yang start_time-nya masih null atau 07:40 kembar
        $plansA = ProductionPlan::whereDate('plan_date', '2026-10-21')
            ->where('shift_name', 'Shift Pagi')
            ->orderBy('id')
            ->get();

        $curTime = \Carbon\Carbon::parse('2026-10-21 07:40:00');
        foreach ($plansA as $p) {
            if (!$p->start_time || $p->start_time === $p->finish_time) {
                $durMin = max(30, (int) round(($p->plan ?: 300) / 10));
                $st = $curTime->format('H:i');
                $fn = $curTime->copy()->addMinutes($durMin)->format('H:i');
                $p->update([
                    'start_time' => $st,
                    'finish_time' => $fn,
                ]);
                $curTime->addMinutes($durMin);
                $this->line("Auto-staggered {$p->job_no}: {$st} -> {$fn}");
                $updated++;
            }
        }

        $this->info("BERHASIL MEMPERBAIKI {$updated} JAM PLAN START & FINISH DI LKH!");
        return 0;
    }
}
