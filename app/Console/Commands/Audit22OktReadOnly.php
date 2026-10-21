<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class Audit22OktReadOnly extends Command
{
    protected $signature = 'app:audit-22-okt-readonly';
    protected $description = 'Read-only comprehensive audit of 22 Oktober 2026 PPC Excel dump vs DB';

    public function handle()
    {
        $dumpPath = storage_path('logs/import_dump.json');
        if (!file_exists($dumpPath)) {
            $this->error("File $dumpPath tidak ditemukan!");
            return 1;
        }

        $dump = json_decode(file_get_contents($dumpPath), true);
        $data = $dump['data'] ?? [];
        $sheets = $data['sheets'] ?? [];
        $uploadDate = '2026-10-22'; // Format ISO tanggal di DB production_plans

        $dbPlans = DB::table('production_plans')
            ->whereDate('plan_date', '2026-10-22')
            ->get();

        $this->info("====================================================================================================");
        $this->info(" AUDIT FORENSIK READ-ONLY: EXCEL DUMP VS DATABASE (TANGGAL: $uploadDate)");
        $this->info("====================================================================================================");

        // ====================================================================
        // BAGIAN 1: REKONSILIASI PER BARIS DENGAN KATEGORI ATURAN BISNIS
        // ====================================================================
        $this->info("\n--- 1. TABEL REKONSILIASI PER BARIS (BERDASARKAN ATURAN BISNIS) ---");
        $this->line(sprintf("%-8s | %-11s | %-3s | %-18s | %-5s | %-8s | %-12s | %-6s | %s",
            'LINE', 'SHIFT', 'ROW', 'JOB NO', 'PLAN', 'EXCEL', 'DB_TYPE', 'DB_ID', 'STATUS'
        ));
        $this->line(str_repeat('-', 100));

        $stats = [
            'MATCH' => 0,
            'SUMMARY_ONLY' => 0,
            'TYPE_MISMATCH' => 0,
            'DATA_MISMATCH' => 0,
            'NOT_IN_DB' => 0,
        ];

        $excelRowUsage = [];

        foreach ($sheets as $k => $sec) {
            $press = $sec['press_name'] ?? '';
            $shift = trim(ucwords(strtolower(preg_replace('/[\(\s]REV.*|[\(\s]REVISI.*|\d+/i', '', $sec['shift_name'] ?? ''))));
            $rows = $sec['rows'] ?? [];

            foreach ($rows as $rIdx => $r) {
                $rawJn = trim($r['job_no'] ?? '');
                $rawJm = trim($r['job_master'] ?? '');
                $planQty = (int)($r['plan'] ?? 0);
                $excelType = $r['row_type'] ?? 'job';
                $rowNo = $r['row_no'] ?? ($rIdx + 1);
                $startTime = $r['start_time'] ?? null;
                $finishTime = $r['finish_time'] ?? null;

                // Aturan Bisnis: Apakah baris summary murni?
                $isSummaryExcel = in_array(strtoupper($rawJn), ['TOTAL FINISH', 'TOTAL FNISH', 'FINISH', 'TOTAL'])
                    || in_array(strtoupper($rawJm), ['TOTAL FINISH', 'TOTAL FNISH', 'FINISH', 'TOTAL STROKE', 'TOTAL TPT', 'TARGET GSPH', 'GSPH']);

                // Cari baris di DB yang sesuai
                $match = $dbPlans->first(function($db) use ($press, $shift, $rawJn, $rawJm, $rowNo, $startTime, $finishTime) {
                    if ($db->press_name !== $press) return false;
                    if (strcasecmp($db->shift_name, $shift) !== 0 && !str_contains(strtolower($db->shift_name), strtolower($shift))) return false;
                    
                    // Prioritaskan exact match pada row_no & job_no
                    if ($rawJn && strcasecmp(trim($db->job_no), $rawJn) === 0 && $db->row_no == $rowNo) return true;
                    // Match by job_no & start_time (untuk split part)
                    if ($rawJn && strcasecmp(trim($db->job_no), $rawJn) === 0 && $db->start_time == $startTime) return true;
                    // Match by row_no
                    if ($db->row_no == $rowNo && in_array($db->row_type, ['break', 'note', 'total_finish'])) return true;
                    // Fallback match by job_no
                    if ($rawJn && strcasecmp(trim($db->job_no), $rawJn) === 0) return true;

                    return false;
                });

                if (!$match) {
                    if ($isSummaryExcel) {
                        $catStatus = 'SUMMARY_ONLY';
                    } else {
                        $catStatus = 'NOT_IN_DB';
                    }
                    $dbType = '-';
                    $dbId = '-';
                } else {
                    $dbType = $match->row_type;
                    $dbId = $match->id;
                    $excelRowUsage[$match->id][] = $rowNo;

                    // Evaluasi Kategori
                    if ($isSummaryExcel && in_array($dbType, ['total_finish', 'note', 'summary'])) {
                        $catStatus = 'SUMMARY_ONLY';
                    } elseif ($excelType === $dbType) {
                        // Cek apakah ada data mismatch (plan qty selisih)
                        if ($excelType === 'job' && (int)$match->plan !== $planQty) {
                            $catStatus = 'DATA_MISMATCH';
                        } else {
                            $catStatus = 'MATCH';
                        }
                    } elseif ($excelType === 'job' && $planQty == 0 && (float)($r['ct_detik'] ?? 0) == 0 && $dbType === 'note') {
                        // Aturan Bisnis: Baris tanpa Plan Qty & CT diimpor sebagai note (Perbantuan dsb)
                        $catStatus = 'MATCH'; // Sesuai aturan Classifier
                    } else {
                        $catStatus = 'TYPE_MISMATCH';
                    }
                }

                $stats[$catStatus] = ($stats[$catStatus] ?? 0) + 1;

                $this->line(sprintf("%-8s | %-11s | %-3d | %-18s | %-5d | %-8s | %-12s | %-6s | %s",
                    $press,
                    $shift,
                    $rowNo,
                    substr($rawJn ?: $rawJm, 0, 18),
                    $planQty,
                    $excelType,
                    $dbType,
                    $dbId,
                    $catStatus
                ));
            }
        }

        $this->info("\n--- STATISTIK HASIL REKONSILIASI ---");
        foreach ($stats as $k => $v) {
            $this->line(" - $k: $v baris");
        }

        // ====================================================================
        // BAGIAN 2: AUDIT KHUSUS SEGMEN JOB DENGAN DB ID SAMA (SPLIT JOBS)
        // ====================================================================
        $this->info("\n====================================================================================================");
        $this->info(" 2. AUDIT KHUSUS SEGMEN JOB YANG BERBAGI DB ID SAMA (SPLIT JOBS / OVERWRITE DETEKSI)");
        $this->info("====================================================================================================");

        $multiUsedIds = array_filter($excelRowUsage, fn($rows) => count($rows) > 1);

        if (empty($multiUsedIds)) {
            $this->info(" Tidak ada DB ID yang dipakai bersama oleh beberapa baris Excel.");
        } else {
            $this->warn(" Ditemukan " . count($multiUsedIds) . " DB ID yang terhubung ke beberapa baris Excel:");
            $this->line(sprintf("%-6s | %-8s | %-12s | %-18s | %-10s | %-10s | %-12s | %s",
                'DB_ID', 'LINE', 'SHIFT', 'JOB NO', 'DB_PLAN', 'EXCEL_ROWS', 'EXCEL_PLANS', 'KETERANGAN'
            ));
            $this->line(str_repeat('-', 95));

            foreach ($multiUsedIds as $id => $rows) {
                $dbRec = $dbPlans->firstWhere('id', $id);
                // Kumpulkan data excel untuk rows ini
                $excelPlans = [];
                foreach ($sheets as $sec) {
                    if ($sec['press_name'] !== $dbRec->press_name) continue;
                    foreach ($sec['rows'] as $r) {
                        if (in_array($r['row_no'] ?? null, $rows)) {
                            $excelPlans[] = ($r['plan'] ?? 0);
                        }
                    }
                }

                $this->line(sprintf("%-6d | %-8s | %-12s | %-18s | %-10d | %-10s | %-12s | %s",
                    $id,
                    $dbRec->press_name,
                    $dbRec->shift_name,
                    $dbRec->job_no ?: $dbRec->job_master,
                    $dbRec->plan,
                    implode(',', $rows),
                    implode('+', $excelPlans),
                    (count($excelPlans) > 1 && $dbRec->plan == end($excelPlans)) ? 'TERTIMPA (OVERWRITTEN)' : 'TERGABUNG (MERGED)'
                ));
            }
        }

        // ====================================================================
        // BAGIAN 3: AUDIT KHUSUS PRESS C (Perbantuan Ke B-Line & DB ID 17132)
        // ====================================================================
        $this->info("\n====================================================================================================");
        $this->info(" 3. AUDIT KHUSUS PRESS C SHIFT PAGI (Perbantuan Ke B-Line & DB ID 17132)");
        $this->info("====================================================================================================");

        $pressCPlans = $dbPlans->where('press_name', 'PRESS C');
        $this->line("Daftar seluruh baris PRESS C di Database 22 Oktober (Total: " . $pressCPlans->count() . "):");
        foreach ($pressCPlans as $pc) {
            $this->line(sprintf(" - DB ID: %-5d | Row: %-2d | Type: %-12s | Job: %-22s | Plan: %-4d | Time: %s-%s",
                $pc->id, $pc->row_no, $pc->row_type, $pc->job_no ?: $pc->job_master, $pc->plan, $pc->start_time, $pc->finish_time
            ));
        }

        // ====================================================================
        // BAGIAN 4: VERIFIKASI INTEGRITAS DATA AKTUAL (READ-ONLY)
        // ====================================================================
        $this->info("\n====================================================================================================");
        $this->info(" 4. VERIFIKASI INTEGRITAS DATA AKTUAL (READ-ONLY)");
        $this->info("====================================================================================================");

        $dpCount = DB::table('daily_productions')->whereDate('work_date', $uploadDate)->count();
        $dtCount = DB::table('downtimes')->whereDate('start_time', $uploadDate)->count();
        $sessCount = DB::table('production_sessions')->whereDate('start_time', $uploadDate)->count();
        $jmCount = DB::table('job_masters')->where('created_at', 'LIKE', "$uploadDate%")->count();

        $this->line("Total DailyProduction (OK/Repair/Reject): $dpCount record");
        $this->line("Total Downtime & Dandori: $dtCount record");
        $this->line("Total ProductionSession: $sessCount record");
        $this->line("Total JobMaster 22 Okt: $jmCount record");
        $this->info("STATUS: DATA AKTUAL 100% AMAN (TIDAK ADA MUTASI / READ-ONLY).");

        $this->info("\n====================================================================================================");
        return 0;
    }
}
