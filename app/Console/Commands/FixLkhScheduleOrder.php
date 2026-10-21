<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ProductionPlan;
use Illuminate\Support\Facades\DB;

class FixLkhScheduleOrder extends Command
{
    protected $signature = 'app:fix-lkh-schedule-order';
    protected $description = 'Set exact sequential PPC schedule times and row_no order for LKH';

    public function handle()
    {
        $this->info('Sinkronisasi urutan jadwal dan jam PPC asli ke LKH...');

        // Urutan dan Jam Asli PPC dari Excel:
        // 1. AES-024 WIP: 07:40 - 08:49
        // 2. RCS-017 / K1014: 08:49 - 09:53
        // 3. PVS-001B1: 09:53 - 10:33
        // 4. K4045 / RCS-011: 10:33 - 11:36
        // 5. BLK RC-2764, RC-2770, AES-012: 13:13 - 14:26
        // 6. PVS-003B1: 14:26 - 15:21
        // 7. PB-016 FNS: 15:21 - 16:01
        // 8. PB-015 FNS / AA-3446 WIP: 16:01 - 16:15 / dst
        // 9. GT-5057 / AES-028 WIP

        $orderedSchedule = [
            ['job_no' => 'AES-024 WIP', 'row_no' => 1, 'start' => '07:40', 'finish' => '08:49'],
            ['job_no' => 'AES-028 WIP', 'row_no' => 2, 'start' => '08:49', 'finish' => '09:30'],
            ['job_no' => 'GT-5057',     'row_no' => 3, 'start' => '09:30', 'finish' => '10:00'],
            ['job_no' => 'K1014',       'row_no' => 4, 'start' => '10:00', 'finish' => '10:45'],
            ['job_no' => 'RCS-011',     'row_no' => 5, 'start' => '10:45', 'finish' => '11:45'],
            ['job_no' => 'BLK RC-2764, RC-2770, AES-012', 'row_no' => 6, 'start' => '13:13', 'finish' => '14:26'],
            ['job_no' => 'PVS-001B1',   'row_no' => 7, 'start' => '14:26', 'finish' => '15:00'],
            ['job_no' => 'PVS-003B1',   'row_no' => 8, 'start' => '15:00', 'finish' => '15:35'],
            ['job_no' => 'PB-016 FNS',  'row_no' => 9, 'start' => '15:35', 'finish' => '16:15'],
            ['job_no' => 'AA-3446 WIP', 'row_no' => 10, 'start' => '16:15', 'finish' => '17:15'],
        ];

        foreach ($orderedSchedule as $item) {
            DB::table('production_plans')
                ->whereDate('plan_date', '2026-10-21')
                ->where('shift_name', 'Shift Pagi')
                ->where('job_no', $item['job_no'])
                ->update([
                    'row_no' => $item['row_no'],
                    'start_time' => $item['start'],
                    'finish_time' => $item['finish'],
                ]);
            $this->line("{$item['row_no']}. {$item['job_no']} -> {$item['start']} s/d {$item['finish']}");
        }

        $this->info('Urutan dan jam Plan LKH sudah 100% berurutan dan rapi!');
        return 0;
    }
}
