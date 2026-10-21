<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\LineMaster;
use App\Models\JobMaster;
use App\Models\DailyProduction;

class ReconcileFullProductionSchedule extends Command
{
    protected $signature = 'app:reconcile-full-production-schedule';
    protected $description = 'Reconstruct and synchronize full PPC schedule including jobs and break intervals safely';

    public function handle()
    {
        $this->info('=== REKONSTRUKSI & PEMULIHAN JADWAL PPC DENGAN SEKAT ISTIRAHAT ===');

        $jsonFile = base_path('exact_shift_pagi_all_lines.json');
        if (!file_exists($jsonFile)) {
            $this->error("File $jsonFile tidak ditemukan!");
            return 1;
        }

        $allData = json_decode(file_get_contents($jsonFile), true);
        $lineMap = LineMaster::pluck('id', 'line_name')->toArray();

        $date = '2026-10-21';
        $shiftName = 'Shift Pagi';

        foreach ($allData as $pressName => $rows) {
            $this->info("\n--- Memproses {$pressName} (" . count($rows) . " baris) ---");
            $lineId = $lineMap[$pressName] ?? 1;

            foreach ($rows as $item) {
                $rowNo = $item['row_no'] ?? 1;
                $jobNo = trim($item['job_no'] ?? '');
                $jobMaster = trim($item['job_master'] ?? $jobNo);
                $isBreak = ($item['row_type'] ?? '') === 'break' 
                    || in_array($jobNo, ['ISTIRAHAT SIANG', 'CINGKORAK', 'BREAKTIME', 'SPORT DAY', 'ISTIRAHAT SORE', 'Perbantuan Ke B-Line']);
                $rowType = $isBreak ? 'break' : 'job';
                $planQty = (int)($item['plan'] ?? 0);
                $startTime = $item['start'] ?? null;
                $finishTime = $item['finish'] ?? null;
                $tpt = $item['tpt'] ?? null;
                $gsph = $item['gsph'] ?? null;
                $pt = $item['pt'] ?? null;
                $ct = $item['ct'] ?? null;
                $totalMesin = $item['m'] ?? ($isBreak ? 0 : 4);

                // Cari apakah plan sudah ada di database untuk baris ini
                $existing = DB::table('production_plans')
                    ->whereDate('plan_date', $date)
                    ->where('shift_name', $shiftName)
                    ->where('press_name', $pressName)
                    ->where('row_no', $rowNo)
                    ->first();

                // Jika tidak ketemu berdasarkan row_no, cari berdasarkan job_no & start_time
                if (!$existing) {
                    $existing = DB::table('production_plans')
                        ->whereDate('plan_date', $date)
                        ->where('shift_name', $shiftName)
                        ->where('press_name', $pressName)
                        ->where('job_no', $jobNo)
                        ->where('start_time', $startTime)
                        ->first();
                }

                if ($existing) {
                    // Update data jadwal tanpa mengubah id (agar relasi aktual tetap aman)
                    DB::table('production_plans')
                        ->where('id', $existing->id)
                        ->update([
                            'row_no' => $rowNo,
                            'row_type' => $rowType,
                            'job_no' => $jobNo,
                            'job_master' => $jobMaster,
                            'plan' => $planQty,
                            'target_qty' => $planQty,
                            'start_time' => $startTime,
                            'finish_time' => $finishTime,
                            'tpt' => $tpt,
                            'gsph_item' => $gsph,
                            'process_time' => $pt,
                            'ct_detik' => $ct,
                            'total_mesin' => $totalMesin,
                            'updated_at' => now(),
                        ]);
                    $this->line(" [UPDATE] Row #{$rowNo}: {$jobNo} ({$rowType}) {$startTime}-{$finishTime}");
                } else {
                    // Insert baris baru (seperti baris break / job yang belum tercatat)
                    DB::table('production_plans')->insert([
                        'line_master_id' => $lineId,
                        'plan_date' => $date,
                        'shift_name' => $shiftName,
                        'press_name' => $pressName,
                        'row_no' => $rowNo,
                        'row_type' => $rowType,
                        'source_type' => 'ppc',
                        'job_master' => $jobMaster,
                        'job_no' => $jobNo,
                        'plan' => $planQty,
                        'target_qty' => $planQty,
                        'status' => 'approved',
                        'start_time' => $startTime,
                        'finish_time' => $finishTime,
                        'total_mesin' => $totalMesin,
                        'ct_detik' => $ct,
                        'tpt' => $tpt,
                        'gsph_item' => $gsph,
                        'process_time' => $pt,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $this->line(" [INSERT] Row #{$rowNo}: {$jobNo} ({$rowType}) {$startTime}-{$finishTime}");
                }
            }
        }

        $this->info("\n=== PEMULIHAN SELESAI DENGAN SUKSES! ===");
        return 0;
    }
}
