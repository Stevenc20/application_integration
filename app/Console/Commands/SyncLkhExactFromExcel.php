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
        $this->info('Sinkronisasi 100% persis sesuai file Excel 18. Stamping 21-Oktober-2026.xlsx (Shift Pagi)...');

        // Pastikan job dummy atau job nyasar di Press A Shift Pagi dihapus dari tabel schedule
        // JobMaster, DailyProduction, dan Downtime tetap aman tidak dihapus!
        DB::table('production_plans')
            ->whereDate('plan_date', '2026-10-21')
            ->where('shift_name', 'Shift Pagi')
            ->where('press_name', 'PRESS A')
            ->whereIn('job_no', ['AES-028 WIP', 'GT-5057', 'K1014', 'RCS-011'])
            ->delete();

        // Data 100% persis dari Excel 'Shift Pagi'
        $excelData = [
            'PRESS A' => [
                ['job_no' => 'AES-024 WIP', 'jm' => 'AES-024', 'plan' => 600, 'mesin' => 4, 'ct' => 5.9, 'start' => '07:40', 'finish' => '08:49', 'row_type' => 'job'],
                ['job_no' => 'RCS-017', 'jm' => 'RCS-017', 'plan' => 500, 'mesin' => 4, 'ct' => 6.4, 'start' => '08:49', 'finish' => '09:53', 'row_type' => 'job'],
                ['job_no' => 'PVS-001B1', 'jm' => 'PVS-001B1/2B1', 'plan' => 300, 'mesin' => 4, 'ct' => 6.0, 'start' => '09:53', 'finish' => '10:33', 'row_type' => 'job'],
                ['job_no' => 'K4045', 'jm' => 'K4045/46', 'plan' => 500, 'mesin' => 4, 'ct' => 6.3, 'start' => '10:33', 'finish' => '11:36', 'row_type' => 'job'],
                ['job_no' => 'XP-0078', 'jm' => '57468-BZ120', 'plan' => 450, 'mesin' => 4, 'ct' => 6.2, 'start' => '11:36', 'finish' => '11:45', 'row_type' => 'job'],
                ['job_no' => 'ISTIRAHAT SIANG', 'jm' => 'ISTIRAHAT SIANG', 'plan' => 0, 'mesin' => 0, 'ct' => 0.0, 'start' => '11:45', 'finish' => '12:25', 'row_type' => 'break'],
                ['job_no' => 'XP-0078', 'jm' => '57468-BZ120', 'plan' => 450, 'mesin' => 4, 'ct' => 6.2, 'start' => '12:25', 'finish' => '13:13', 'row_type' => 'job'],
                ['job_no' => 'BLK RC-2764, RC-2770, AES-012', 'jm' => 'RC-2764', 'plan' => 600, 'mesin' => 4, 'ct' => 6.3, 'start' => '13:13', 'finish' => '14:26', 'row_type' => 'job'],
                ['job_no' => 'PVS-003B1', 'jm' => 'PVS-003/4B1', 'plan' => 240, 'mesin' => 4, 'ct' => 6.0, 'start' => '14:26', 'finish' => '15:00', 'row_type' => 'job'],
                ['job_no' => 'CINGKORAK', 'jm' => 'CINGKORAK', 'plan' => 0, 'mesin' => 0, 'ct' => 0.0, 'start' => '15:00', 'finish' => '15:15', 'row_type' => 'break'],
                ['job_no' => 'PVS-003B1', 'jm' => 'PVS-003/4B1', 'plan' => 60, 'mesin' => 4, 'ct' => 6.0, 'start' => '15:15', 'finish' => '15:21', 'row_type' => 'job'],
                ['job_no' => 'PB-016 FNS', 'jm' => 'PB-016/26', 'plan' => 240, 'mesin' => 4, 'ct' => 6.2, 'start' => '15:21', 'finish' => '16:01', 'row_type' => 'job'],
                ['job_no' => 'PB-015 FNS', 'jm' => 'PB-015/25', 'plan' => 0, 'mesin' => 4, 'ct' => 6.2, 'start' => '16:01', 'finish' => '16:15', 'row_type' => 'job'],
                ['job_no' => 'BREAKTIME', 'jm' => 'BREAKTIME', 'plan' => 0, 'mesin' => 0, 'ct' => 0.0, 'start' => '16:15', 'finish' => '16:30', 'row_type' => 'break'],
                ['job_no' => 'SPORT DAY', 'jm' => 'SPORT DAY', 'plan' => 0, 'mesin' => 0, 'ct' => 0.0, 'start' => '16:30', 'finish' => '18:00', 'row_type' => 'break'],
                ['job_no' => 'ISTIRAHAT SORE', 'jm' => 'ISTIRAHAT SORE', 'plan' => 0, 'mesin' => 0, 'ct' => 0.0, 'start' => '18:00', 'finish' => '18:30', 'row_type' => 'break'],
                ['job_no' => 'PB-015 FNS', 'jm' => 'PB-015/25', 'plan' => 240, 'mesin' => 4, 'ct' => 6.2, 'start' => '18:30', 'finish' => '18:56', 'row_type' => 'job'],
                ['job_no' => 'AA-3446 WIP', 'jm' => '61771/72 WIP', 'plan' => 450, 'mesin' => 4, 'ct' => 6.4, 'start' => '18:56', 'finish' => '19:54', 'row_type' => 'job'],
            ],
            'PRESS B' => [
                ['job_no' => 'PB-018N FNS', 'jm' => 'PB-018 FNS', 'plan' => 300, 'mesin' => 4, 'ct' => 10.0, 'start' => '07:40', 'finish' => '08:35', 'row_type' => 'job'],
                ['job_no' => 'PB-033 FNS', 'jm' => 'PB-033/34', 'plan' => 300, 'mesin' => 4, 'ct' => 6.05, 'start' => '08:35', 'finish' => '09:11', 'row_type' => 'job'],
                ['job_no' => 'RCS-018', 'jm' => 'RCS-018', 'plan' => 600, 'mesin' => 4, 'ct' => 6.0, 'start' => '09:11', 'finish' => '10:16', 'row_type' => 'job'],
                ['job_no' => 'AAS-057', 'jm' => 'AAS-057', 'plan' => 480, 'mesin' => 4, 'ct' => 6.0, 'start' => '10:16', 'finish' => '11:09', 'row_type' => 'job'],
                ['job_no' => 'PB-062', 'jm' => 'PB-062', 'plan' => 300, 'mesin' => 4, 'ct' => 5.9, 'start' => '11:09', 'finish' => '11:45', 'row_type' => 'job'],
                ['job_no' => 'ISTIRAHAT SIANG', 'jm' => 'ISTIRAHAT SIANG', 'plan' => 0, 'mesin' => 0, 'ct' => 0.0, 'start' => '11:45', 'finish' => '12:25', 'row_type' => 'break'],
                ['job_no' => 'PB-070', 'jm' => 'PB-070', 'plan' => 360, 'mesin' => 4, 'ct' => 7.0, 'start' => '12:25', 'finish' => '13:12', 'row_type' => 'job'],
                ['job_no' => 'G-017', 'jm' => 'G-017/18', 'plan' => 360, 'mesin' => 2, 'ct' => 8.0, 'start' => '13:12', 'finish' => '14:07', 'row_type' => 'job'],
                ['job_no' => 'AAS-050 BLK', 'jm' => 'AAS-050', 'plan' => 480, 'mesin' => 1, 'ct' => 5.95, 'start' => '13:12', 'finish' => '14:07', 'row_type' => 'job'],
                ['job_no' => 'PB-016 BLK', 'jm' => 'PB-016 BLK', 'plan' => 480, 'mesin' => 1, 'ct' => 6.05, 'start' => '13:12', 'finish' => '14:07', 'row_type' => 'job'],
                ['job_no' => 'AES-017 BLK', 'jm' => 'AES-017', 'plan' => 480, 'mesin' => 1, 'ct' => 5.9, 'start' => '14:07', 'finish' => '15:00', 'row_type' => 'job'],
                ['job_no' => 'NA-053 BLK', 'jm' => 'NA-053/56', 'plan' => 480, 'mesin' => 1, 'ct' => 5.9, 'start' => '14:07', 'finish' => '15:00', 'row_type' => 'job'],
                ['job_no' => 'AE-0090 BLK', 'jm' => 'AE-0090', 'plan' => 430, 'mesin' => 1, 'ct' => 6.0, 'start' => '14:07', 'finish' => '15:00', 'row_type' => 'job'],
                ['job_no' => 'CINGKORAK', 'jm' => 'CINGKORAK', 'plan' => 0, 'mesin' => 0, 'ct' => 0.0, 'start' => '15:00', 'finish' => '15:15', 'row_type' => 'break'],
                ['job_no' => 'AES-017 BLK', 'jm' => 'AES-017', 'plan' => 520, 'mesin' => 1, 'ct' => 5.9, 'start' => '15:15', 'finish' => '16:07', 'row_type' => 'job'],
                ['job_no' => 'NA-053 BLK', 'jm' => 'NA-053/56', 'plan' => 520, 'mesin' => 1, 'ct' => 5.9, 'start' => '15:15', 'finish' => '16:07', 'row_type' => 'job'],
                ['job_no' => 'AE-0090 BLK', 'jm' => 'AE-0090', 'plan' => 600, 'mesin' => 1, 'ct' => 6.0, 'start' => '15:15', 'finish' => '16:15', 'row_type' => 'job'],
                ['job_no' => 'NA-053 FNS', 'jm' => 'NA-053/56', 'plan' => 30, 'mesin' => 3, 'ct' => 5.9, 'start' => '16:07', 'finish' => '16:15', 'row_type' => 'job'],
                ['job_no' => 'BREAKTIME', 'jm' => 'BREAKTIME', 'plan' => 0, 'mesin' => 0, 'ct' => 0.0, 'start' => '16:15', 'finish' => '16:30', 'row_type' => 'break'],
                ['job_no' => 'AE-0090 BLK', 'jm' => 'AE-0090', 'plan' => 170, 'mesin' => 1, 'ct' => 6.0, 'start' => '16:30', 'finish' => '16:47', 'row_type' => 'job'],
                ['job_no' => 'NA-053 FNS', 'jm' => 'NA-053/56', 'plan' => 770, 'mesin' => 3, 'ct' => 5.9, 'start' => '16:30', 'finish' => '17:46', 'row_type' => 'job'],
                ['job_no' => 'NX-2928 FNS', 'jm' => 'NX-2928', 'plan' => 400, 'mesin' => 1, 'ct' => 5.9, 'start' => '16:47', 'finish' => '17:32', 'row_type' => 'job'],
                ['job_no' => 'YCS-001', 'jm' => 'NA-053/56', 'plan' => 100, 'mesin' => 3, 'ct' => 5.9, 'start' => '17:46', 'finish' => '18:00', 'row_type' => 'job'],
            ],
            'PRESS C' => [
                ['job_no' => 'PV-006BA1 BLK', 'jm' => 'PV-006B', 'plan' => 150, 'mesin' => 1, 'ct' => 25.0, 'start' => '07:40', 'finish' => '08:50', 'row_type' => 'job'],
                ['job_no' => 'PV-005BA1 BLK', 'jm' => 'PV-006B', 'plan' => 150, 'mesin' => 1, 'ct' => 25.0, 'start' => '08:50', 'finish' => '10:00', 'row_type' => 'job'],
                ['job_no' => 'Perbantuan Ke B-Line', 'jm' => 'Perbantuan Ke B-Line', 'plan' => 0, 'mesin' => 0, 'ct' => 0.0, 'start' => '10:00', 'finish' => '11:45', 'row_type' => 'break'],
                ['job_no' => 'ISTIRAHAT SIANG', 'jm' => 'ISTIRAHAT SIANG', 'plan' => 0, 'mesin' => 0, 'ct' => 40.0, 'start' => '11:45', 'finish' => '12:25', 'row_type' => 'break'],
                ['job_no' => 'Perbantuan Ke B-Line', 'jm' => 'Perbantuan Ke B-Line', 'plan' => 0, 'mesin' => 0, 'ct' => 0.0, 'start' => '12:25', 'finish' => '13:45', 'row_type' => 'break'],
                ['job_no' => 'J-4051', 'jm' => 'LOKAL R', 'plan' => 30, 'mesin' => 4, 'ct' => 14.0, 'start' => '13:45', 'finish' => '14:12', 'row_type' => 'job'],
                ['job_no' => 'J-4057', 'jm' => 'LOKAL R', 'plan' => 15, 'mesin' => 4, 'ct' => 14.0, 'start' => '14:12', 'finish' => '14:36', 'row_type' => 'job'],
                ['job_no' => 'J-4053', 'jm' => 'GALVANIS R', 'plan' => 15, 'mesin' => 4, 'ct' => 14.0, 'start' => '14:36', 'finish' => '15:00', 'row_type' => 'job'],
                ['job_no' => 'CINGKORAK', 'jm' => 'CINGKORAK', 'plan' => 0, 'mesin' => 0, 'ct' => 15.0, 'start' => '15:00', 'finish' => '15:15', 'row_type' => 'break'],
                ['job_no' => 'Perbantuan Ke B-Line', 'jm' => 'Perbantuan Ke B-Line', 'plan' => 0, 'mesin' => 0, 'ct' => 0.0, 'start' => '15:15', 'finish' => '16:15', 'row_type' => 'break'],
            ],
            'PRESS D' => [
                ['job_no' => 'IB-050', 'jm' => 'IB-050', 'plan' => 300, 'mesin' => 4, 'ct' => 6.7, 'start' => '07:40', 'finish' => '08:24', 'row_type' => 'job'],
                ['job_no' => 'G-028', 'jm' => 'G-028', 'plan' => 250, 'mesin' => 4, 'ct' => 6.5, 'start' => '08:24', 'finish' => '09:02', 'row_type' => 'job'],
                ['job_no' => 'G-034', 'jm' => 'G-034', 'plan' => 50, 'mesin' => 4, 'ct' => 6.5, 'start' => '09:02', 'finish' => '09:13', 'row_type' => 'job'],
                ['job_no' => 'RC-2764 FNS', 'jm' => '57417-BZ120-00', 'plan' => 800, 'mesin' => 4, 'ct' => 6.6, 'start' => '09:13', 'finish' => '10:56', 'row_type' => 'job'],
                ['job_no' => 'RC-2770 FNS', 'jm' => '57418-BZ020-00', 'plan' => 305, 'mesin' => 4, 'ct' => 6.6, 'start' => '10:56', 'finish' => '11:45', 'row_type' => 'job'],
                ['job_no' => 'ISTIRAHAT SIANG', 'jm' => 'ISTIRAHAT SIANG', 'plan' => 0, 'mesin' => 0, 'ct' => 0.0, 'start' => '11:45', 'finish' => '12:25', 'row_type' => 'break'],
                ['job_no' => 'RC-2770 FNS', 'jm' => '57418-BZ020-00', 'plan' => 495, 'mesin' => 4, 'ct' => 6.6, 'start' => '12:25', 'finish' => '13:20', 'row_type' => 'job'],
                ['job_no' => 'AAS-050/51 FNS', 'jm' => 'AAS-050/51', 'plan' => 240, 'mesin' => 4, 'ct' => 6.7, 'start' => '13:20', 'finish' => '14:00', 'row_type' => 'job'],
                ['job_no' => 'AES-012 FNS', 'jm' => '58115-BZ100', 'plan' => 300, 'mesin' => 4, 'ct' => 6.5, 'start' => '14:00', 'finish' => '14:43', 'row_type' => 'job'],
                ['job_no' => 'AES-012V FNS', 'jm' => '58115-BZ100', 'plan' => 120, 'mesin' => 4, 'ct' => 6.5, 'start' => '14:43', 'finish' => '15:00', 'row_type' => 'job'],
                ['job_no' => 'CINGKORAK', 'jm' => 'CINGKORAK', 'plan' => 0, 'mesin' => 0, 'ct' => 0.0, 'start' => '15:00', 'finish' => '15:15', 'row_type' => 'break'],
                ['job_no' => 'AES-017 FNS', 'jm' => '61658-BZ100', 'plan' => 450, 'mesin' => 4, 'ct' => 6.6, 'start' => '15:15', 'finish' => '16:15', 'row_type' => 'job'],
                ['job_no' => 'BREAKTIME', 'jm' => 'BREAKTIME', 'plan' => 0, 'mesin' => 0, 'ct' => 0.0, 'start' => '16:15', 'finish' => '16:30', 'row_type' => 'break'],
                ['job_no' => 'SPORT DAY', 'jm' => 'SPORT DAY', 'plan' => 0, 'mesin' => 0, 'ct' => 0.0, 'start' => '16:30', 'finish' => '18:00', 'row_type' => 'break'],
                ['job_no' => 'ISTIRAHAT SORE', 'jm' => 'ISTIRAHAT SORE', 'plan' => 0, 'mesin' => 0, 'ct' => 0.0, 'start' => '18:00', 'finish' => '18:30', 'row_type' => 'break'],
                ['job_no' => 'AES-017 FNS', 'jm' => '61658-BZ100', 'plan' => 550, 'mesin' => 4, 'ct' => 6.6, 'start' => '18:30', 'finish' => '19:31', 'row_type' => 'job'],
            ],
        ];

        $totalUpdated = 0;
        foreach ($excelData as $press => $items) {
            $rowNo = 1;
            foreach ($items as $it) {
                // Cari atau buat row yang cocok
                $query = DB::table('production_plans')
                    ->whereDate('plan_date', '2026-10-21')
                    ->where('shift_name', 'Shift Pagi')
                    ->where('press_name', $press);

                if ($it['row_type'] === 'break') {
                    $query->where('row_type', 'break')->where('start_time', $it['start']);
                } else {
                    $query->where('row_type', 'job')->where('job_no', $it['job_no']);
                }

                $existing = $query->first();

                if ($existing) {
                    DB::table('production_plans')
                        ->where('id', $existing->id)
                        ->update([
                            'row_no' => $rowNo,
                            'total_mesin' => $it['mesin'],
                            'start_time' => $it['start'],
                            'finish_time' => $it['finish'],
                            'ct_detik' => $it['ct'],
                            'target_qty' => $it['plan'],
                            'plan' => $it['plan'],
                        ]);
                    $this->line("[{$press}] Updated #{$rowNo} {$it['job_no']} ({$it['start']} - {$it['finish']}, Mesin: {$it['mesin']}, CT: {$it['ct']}s)");
                    $totalUpdated++;
                } else {
                    // Jika belum ada (misalnya baris break atau split part baru), buat baris plan baru
                    DB::table('production_plans')->insert([
                        'line_master_id' => 1,
                        'plan_date' => '2026-10-21',
                        'shift_name' => 'Shift Pagi',
                        'press_name' => $press,
                        'row_no' => $rowNo,
                        'row_type' => $it['row_type'],
                        'source_type' => 'ppc',
                        'job_master' => $it['jm'],
                        'job_no' => $it['job_no'],
                        'plan' => $it['plan'],
                        'target_qty' => $it['plan'],
                        'total_mesin' => $it['mesin'],
                        'ct_detik' => $it['ct'],
                        'start_time' => $it['start'],
                        'finish_time' => $it['finish'],
                        'status' => 'pending',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $this->line("[{$press}] Inserted #{$rowNo} {$it['job_no']} ({$it['start']} - {$it['finish']}, Mesin: {$it['mesin']}, CT: {$it['ct']}s)");
                    $totalUpdated++;
                }
                $rowNo++;
            }
        }

        $this->info("BERHASIL! {$totalUpdated} item jadwal Shift Pagi disinkronkan 100% persis Excel asli.");
        return 0;
    }
}
