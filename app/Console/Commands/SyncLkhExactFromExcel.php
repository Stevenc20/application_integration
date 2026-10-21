<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ProductionPlan;
use Illuminate\Support\Facades\DB;

class SyncLkhExactFromExcel extends Command
{
    protected $signature = 'app:sync-lkh-exact-excel';
    protected $description = 'Sync exact sequence, start_time, finish_time, and CT from 18. Stamping 21-Oktober-2026.xlsx';

    public function handle()
    {
        $this->info('Membersihkan dan menyinkronkan LKH Shift Pagi murni 100% item Job produksi persis Excel...');

        // 1. Hapus seluruh baris 'break' / istirahat dari production_plans di Shift Pagi
        DB::table('production_plans')
            ->whereDate('plan_date', '2026-10-21')
            ->where('shift_name', 'Shift Pagi')
            ->where(function($q) {
                $q->where('row_type', 'break')
                  ->orWhere('job_no', 'LIKE', '%ISTIRAHAT%')
                  ->orWhere('job_no', 'LIKE', '%CINGKORAK%')
                  ->orWhere('job_no', 'LIKE', '%BREAKTIME%')
                  ->orWhere('job_no', 'LIKE', '%SPORT DAY%')
                  ->orWhere('job_no', 'LIKE', '%Perbantuan%');
            })
            ->delete();

        // 2. Hapus job nyasar di PRESS A (AES-028 WIP, GT-5057, K1014, RCS-011)
        DB::table('production_plans')
            ->whereDate('plan_date', '2026-10-21')
            ->where('shift_name', 'Shift Pagi')
            ->where('press_name', 'PRESS A')
            ->whereIn('job_no', ['AES-028 WIP', 'GT-5057', 'K1014', 'RCS-011'])
            ->delete();

        // 3. Murni Item Job Produksi beserta TPT, GSPH, dan Process Time persis Excel
        $excelData = [
            'PRESS A' => [
                ['job_no' => 'AES-024 WIP', 'jm' => 'AES-024', 'plan' => 600, 'mesin' => 4, 'ct' => 5.9, 'start' => '07:40', 'finish' => '08:49', 'tpt' => 69, 'gsph' => 522, 'pt' => 59.0],
                ['job_no' => 'RCS-017', 'jm' => 'RCS-017', 'plan' => 500, 'mesin' => 4, 'ct' => 6.4, 'start' => '08:49', 'finish' => '09:53', 'tpt' => 64, 'gsph' => 469, 'pt' => 53.33],
                ['job_no' => 'PVS-001B1', 'jm' => 'PVS-001B1/2B1', 'plan' => 300, 'mesin' => 4, 'ct' => 6.0, 'start' => '09:53', 'finish' => '10:33', 'tpt' => 40, 'gsph' => 450, 'pt' => 30.0],
                ['job_no' => 'K4045', 'jm' => 'K4045/46', 'plan' => 500, 'mesin' => 4, 'ct' => 6.3, 'start' => '10:33', 'finish' => '11:36', 'tpt' => 63, 'gsph' => 476, 'pt' => 52.5],
                ['job_no' => 'XP-0078', 'jm' => '57468-BZ120', 'plan' => 450, 'mesin' => 4, 'ct' => 6.2, 'start' => '11:36', 'finish' => '13:13', 'tpt' => 57, 'gsph' => 474, 'pt' => 46.5],
                ['job_no' => 'BLK RC-2764, RC-2770, AES-012', 'jm' => 'RC-2764', 'plan' => 600, 'mesin' => 4, 'ct' => 6.3, 'start' => '13:13', 'finish' => '14:26', 'tpt' => 73, 'gsph' => 493, 'pt' => 63.0],
                ['job_no' => 'PVS-003B1', 'jm' => 'PVS-003/4B1', 'plan' => 300, 'mesin' => 4, 'ct' => 6.0, 'start' => '14:26', 'finish' => '15:21', 'tpt' => 40, 'gsph' => 450, 'pt' => 30.0],
                ['job_no' => 'PB-016 FNS', 'jm' => 'PB-016/26', 'plan' => 240, 'mesin' => 4, 'ct' => 6.2, 'start' => '15:21', 'finish' => '16:01', 'tpt' => 40, 'gsph' => 360, 'pt' => 24.8],
                ['job_no' => 'PB-015 FNS', 'jm' => 'PB-015/25', 'plan' => 240, 'mesin' => 4, 'ct' => 6.2, 'start' => '16:01', 'finish' => '18:56', 'tpt' => 40, 'gsph' => 360, 'pt' => 24.8],
                ['job_no' => 'AA-3446 WIP', 'jm' => '61771/72 WIP', 'plan' => 450, 'mesin' => 4, 'ct' => 6.4, 'start' => '18:56', 'finish' => '19:54', 'tpt' => 58, 'gsph' => 466, 'pt' => 48.0],
            ],
            'PRESS B' => [
                ['job_no' => 'PB-018N FNS', 'jm' => 'PB-018 FNS', 'plan' => 300, 'mesin' => 4, 'ct' => 10.0, 'start' => '07:40', 'finish' => '08:35', 'tpt' => 55, 'gsph' => 327, 'pt' => 50.0],
                ['job_no' => 'PB-033 FNS', 'jm' => 'PB-033/34', 'plan' => 300, 'mesin' => 4, 'ct' => 6.05, 'start' => '08:35', 'finish' => '09:11', 'tpt' => 36, 'gsph' => 500, 'pt' => 30.25],
                ['job_no' => 'RCS-018', 'jm' => 'RCS-018', 'plan' => 600, 'mesin' => 4, 'ct' => 6.0, 'start' => '09:11', 'finish' => '10:16', 'tpt' => 65, 'gsph' => 554, 'pt' => 60.0],
                ['job_no' => 'AAS-057', 'jm' => 'AAS-057', 'plan' => 480, 'mesin' => 4, 'ct' => 6.0, 'start' => '10:16', 'finish' => '11:09', 'tpt' => 53, 'gsph' => 543, 'pt' => 48.0],
                ['job_no' => 'PB-062', 'jm' => 'PB-062', 'plan' => 300, 'mesin' => 4, 'ct' => 5.9, 'start' => '11:09', 'finish' => '11:45', 'tpt' => 36, 'gsph' => 500, 'pt' => 29.5],
                ['job_no' => 'PB-070', 'jm' => 'PB-070', 'plan' => 360, 'mesin' => 4, 'ct' => 7.0, 'start' => '12:25', 'finish' => '13:12', 'tpt' => 47, 'gsph' => 460, 'pt' => 42.0],
                ['job_no' => 'G-017', 'jm' => 'G-017/18', 'plan' => 360, 'mesin' => 2, 'ct' => 8.0, 'start' => '13:12', 'finish' => '14:07', 'tpt' => 55, 'gsph' => 393, 'pt' => 48.0],
                ['job_no' => 'AAS-050 BLK', 'jm' => 'AAS-050', 'plan' => 480, 'mesin' => 1, 'ct' => 5.95, 'start' => '13:12', 'finish' => '14:07', 'tpt' => 55, 'gsph' => 524, 'pt' => 47.6],
                ['job_no' => 'PB-016 BLK', 'jm' => 'PB-016 BLK', 'plan' => 480, 'mesin' => 1, 'ct' => 6.05, 'start' => '13:12', 'finish' => '14:07', 'tpt' => 55, 'gsph' => 524, 'pt' => 48.4],
                ['job_no' => 'AES-017 BLK', 'jm' => 'AES-017', 'plan' => 1000, 'mesin' => 1, 'ct' => 5.9, 'start' => '14:07', 'finish' => '16:07', 'tpt' => 105, 'gsph' => 571, 'pt' => 98.33],
                ['job_no' => 'NA-053 BLK', 'jm' => 'NA-053/56', 'plan' => 1000, 'mesin' => 1, 'ct' => 5.9, 'start' => '14:07', 'finish' => '16:07', 'tpt' => 105, 'gsph' => 571, 'pt' => 98.33],
                ['job_no' => 'AE-0090 BLK', 'jm' => 'AE-0090', 'plan' => 1200, 'mesin' => 1, 'ct' => 6.0, 'start' => '14:07', 'finish' => '16:47', 'tpt' => 130, 'gsph' => 554, 'pt' => 120.0],
                ['job_no' => 'NA-053 FNS', 'jm' => 'NA-053/56', 'plan' => 800, 'mesin' => 3, 'ct' => 5.9, 'start' => '16:07', 'finish' => '17:46', 'tpt' => 84, 'gsph' => 571, 'pt' => 78.67],
                ['job_no' => 'NX-2928 FNS', 'jm' => 'NX-2928', 'plan' => 400, 'mesin' => 1, 'ct' => 5.9, 'start' => '16:47', 'finish' => '17:32', 'tpt' => 45, 'gsph' => 533, 'pt' => 39.33],
                ['job_no' => 'YCS-001', 'jm' => 'NA-053/56', 'plan' => 100, 'mesin' => 3, 'ct' => 5.9, 'start' => '17:46', 'finish' => '18:00', 'tpt' => 14, 'gsph' => 429, 'pt' => 9.83],
            ],
            'PRESS C' => [
                ['job_no' => 'PV-006BA1 BLK', 'jm' => 'PV-006B', 'plan' => 150, 'mesin' => 1, 'ct' => 25.0, 'start' => '07:40', 'finish' => '08:50', 'tpt' => 70, 'gsph' => 129, 'pt' => 62.5],
                ['job_no' => 'PV-005BA1 BLK', 'jm' => 'PV-006B', 'plan' => 150, 'mesin' => 1, 'ct' => 25.0, 'start' => '08:50', 'finish' => '10:00', 'tpt' => 70, 'gsph' => 129, 'pt' => 62.5],
                ['job_no' => 'J-4051', 'jm' => 'LOKAL R', 'plan' => 30, 'mesin' => 4, 'ct' => 14.0, 'start' => '13:45', 'finish' => '14:12', 'tpt' => 27, 'gsph' => 67, 'pt' => 7.0],
                ['job_no' => 'J-4057', 'jm' => 'LOKAL R', 'plan' => 15, 'mesin' => 4, 'ct' => 14.0, 'start' => '14:12', 'finish' => '14:36', 'tpt' => 24, 'gsph' => 38, 'pt' => 3.5],
                ['job_no' => 'J-4053', 'jm' => 'GALVANIS R', 'plan' => 15, 'mesin' => 4, 'ct' => 14.0, 'start' => '14:36', 'finish' => '15:00', 'tpt' => 24, 'gsph' => 38, 'pt' => 3.5],
            ],
            'PRESS D' => [
                ['job_no' => 'IB-050', 'jm' => 'IB-050', 'plan' => 300, 'mesin' => 4, 'ct' => 6.7, 'start' => '07:40', 'finish' => '08:24', 'tpt' => 44, 'gsph' => 409, 'pt' => 33.5],
                ['job_no' => 'G-028', 'jm' => 'G-028', 'plan' => 250, 'mesin' => 4, 'ct' => 6.5, 'start' => '08:24', 'finish' => '09:02', 'tpt' => 38, 'gsph' => 395, 'pt' => 27.08],
                ['job_no' => 'G-034', 'jm' => 'G-034', 'plan' => 50, 'mesin' => 4, 'ct' => 6.5, 'start' => '09:02', 'finish' => '09:13', 'tpt' => 11, 'gsph' => 273, 'pt' => 5.42],
                ['job_no' => 'RC-2764 FNS', 'jm' => '57417-BZ120-00', 'plan' => 800, 'mesin' => 4, 'ct' => 6.6, 'start' => '09:13', 'finish' => '10:56', 'tpt' => 103, 'gsph' => 466, 'pt' => 88.0],
                ['job_no' => 'RC-2770 FNS', 'jm' => '57418-BZ020-00', 'plan' => 800, 'mesin' => 4, 'ct' => 6.6, 'start' => '10:56', 'finish' => '13:20', 'tpt' => 104, 'gsph' => 462, 'pt' => 88.0],
                ['job_no' => 'AAS-050/51 FNS', 'jm' => 'AAS-050/51', 'plan' => 240, 'mesin' => 4, 'ct' => 6.7, 'start' => '13:20', 'finish' => '14:00', 'tpt' => 40, 'gsph' => 360, 'pt' => 26.8],
                ['job_no' => 'AES-012 FNS', 'jm' => '58115-BZ100', 'plan' => 300, 'mesin' => 4, 'ct' => 6.5, 'start' => '14:00', 'finish' => '14:43', 'tpt' => 43, 'gsph' => 419, 'pt' => 32.5],
                ['job_no' => 'AES-012V FNS', 'jm' => '58115-BZ100', 'plan' => 120, 'mesin' => 4, 'ct' => 6.5, 'start' => '14:43', 'finish' => '15:00', 'tpt' => 17, 'gsph' => 424, 'pt' => 13.0],
                ['job_no' => 'AES-017 FNS', 'jm' => '61658-BZ100', 'plan' => 1000, 'mesin' => 4, 'ct' => 6.6, 'start' => '15:15', 'finish' => '19:31', 'tpt' => 121, 'gsph' => 496, 'pt' => 110.0],
            ],
        ];

        $totalUpdated = 0;
        foreach ($excelData as $press => $items) {
            $rowNo = 1;
            foreach ($items as $it) {
                $query = DB::table('production_plans')
                    ->whereDate('plan_date', '2026-10-21')
                    ->where('shift_name', 'Shift Pagi')
                    ->where('press_name', $press)
                    ->where('job_no', $it['job_no']);

                $existing = $query->first();

                if ($existing) {
                    DB::table('production_plans')
                        ->where('id', $existing->id)
                        ->update([
                            'row_no' => $rowNo,
                            'row_type' => 'job',
                            'total_mesin' => $it['mesin'],
                            'start_time' => $it['start'],
                            'finish_time' => $it['finish'],
                            'ct_detik' => $it['ct'],
                            'target_qty' => $it['plan'],
                            'plan' => $it['plan'],
                            'tpt' => $it['tpt'],
                            'gsph_item' => $it['gsph'],
                            'process_time' => $it['pt'],
                        ]);
                    $this->line("[{$press}] Updated #{$rowNo} {$it['job_no']}: GSPH {$it['gsph']}, TPT {$it['tpt']}m, CT {$it['ct']}s");
                    $totalUpdated++;
                } else {
                    DB::table('production_plans')->insert([
                        'line_master_id' => 1,
                        'plan_date' => '2026-10-21',
                        'shift_name' => 'Shift Pagi',
                        'press_name' => $press,
                        'row_no' => $rowNo,
                        'row_type' => 'job',
                        'source_type' => 'ppc',
                        'job_master' => $it['jm'],
                        'job_no' => $it['job_no'],
                        'plan' => $it['plan'],
                        'target_qty' => $it['plan'],
                        'total_mesin' => $it['mesin'],
                        'ct_detik' => $it['ct'],
                        'start_time' => $it['start'],
                        'finish_time' => $it['finish'],
                        'tpt' => $it['tpt'],
                        'gsph_item' => $it['gsph'],
                        'process_time' => $it['pt'],
                        'status' => 'pending',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $this->line("[{$press}] Inserted #{$rowNo} {$it['job_no']}: GSPH {$it['gsph']}, TPT {$it['tpt']}m, CT {$it['ct']}s");
                    $totalUpdated++;
                }
                $rowNo++;
            }
        }

        $this->info("BERHASIL! {$totalUpdated} item jadwal disinkronkan dengan GSPH, TPT, dan Process Time 100% persis Excel.");
        return 0;
    }
}
