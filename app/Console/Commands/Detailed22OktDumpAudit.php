<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class Detailed22OktDumpAudit extends Command
{
    protected $signature = 'app:detailed-22-okt-dump-audit';
    protected $description = 'Detailed row-by-row audit of import_dump.json and database comparison for 22 Oktober';

    public function handle()
    {
        $this->info("================================================================================");
        $this->info(" AUDIT DETAIL PER BARIS: import_dump.json VS DATABASE PRODUCTION (22 OKT)");
        $this->info("================================================================================");

        $dumpPath = storage_path('logs/import_dump.json');
        if (!file_exists($dumpPath)) {
            $this->error("File $dumpPath tidak ditemukan!");
            return 1;
        }

        $dump = json_decode(file_get_contents($dumpPath), true);
        $data = $dump['data'] ?? [];
        $sheets = $data['sheets'] ?? [];

        // 1. Audit Section Shift Malam di dalam Dump
        $this->info("\n=== BAGIAN 1: PEMERIKSAAN SECTION SHIFT MALAM DI DUMP EXCEL ===");
        $malamSections = array_filter($sheets, fn($s) => str_contains(strtolower($s['shift_name'] ?? ''), 'malam'));
        
        $this->line("Ditemukan " . count($malamSections) . " section Shift Malam di dump:");
        foreach ($malamSections as $k => $sec) {
            $this->line("\n--- Section: {$k} (Press: {$sec['press_name']}, Shift: {$sec['shift_name']}) ---");
            $rows = $sec['rows'] ?? [];
            $this->line("Total baris mentah: " . count($rows));
            foreach ($rows as $idx => $r) {
                $this->line(sprintf("  Row #%02d | Job: %-18s | Master: %-18s | Plan: %-5s | CT: %-4s | Time: %s-%s | Type: %s | Ket: %s",
                    $idx + 1,
                    $r['job_no'] ?? '-',
                    $r['job_master'] ?? '-',
                    $r['plan'] ?? '0',
                    $r['ct_detik'] ?? '0',
                    $r['start_time'] ?? '--:--',
                    $r['finish_time'] ?? '--:--',
                    $r['row_type'] ?? '-',
                    $r['keterangan'] ?? '-'
                ));
            }
        }

        // 2. Audit Khusus PRESS C Shift Pagi di dalam Dump
        $this->info("\n=== BAGIAN 2: PEMERIKSAAN DETAIL PRESS C SHIFT PAGI DI DUMP ===");
        $pressCSections = array_filter($sheets, fn($s) => 
            str_contains(strtoupper($s['press_name'] ?? ''), 'PRESS C') && 
            str_contains(strtolower($s['shift_name'] ?? ''), 'pagi')
        );

        foreach ($pressCSections as $k => $sec) {
            $this->line("\n--- Dump Section: {$k} ({$sec['press_name']} - {$sec['shift_name']}) ---");
            $rows = $sec['rows'] ?? [];
            $this->line("Total baris mentah di Excel dump: " . count($rows));
            foreach ($rows as $idx => $r) {
                $this->line(sprintf("  Row #%02d | Job: %-20s | Master: %-20s | Plan: %-5s | CT: %-4s | Time: %s-%s | Type: %s | Ket: %s",
                    $idx + 1,
                    $r['job_no'] ?? '-',
                    $r['job_master'] ?? '-',
                    $r['plan'] ?? '0',
                    $r['ct_detik'] ?? '0',
                    $r['start_time'] ?? '--:--',
                    $r['finish_time'] ?? '--:--',
                    $r['row_type'] ?? '-',
                    $r['keterangan'] ?? '-'
                ));
            }
        }

        // 3. Rekonsiliasi Per Baris: Excel Dump vs Database Production 22 Oktober
        $this->info("\n=== BAGIAN 3: TABEL REKONSILIASI PER BARIS (DUMP VS DB 22 OKTOBER) ===");
        $dbPlans = DB::table('production_plans')
            ->whereDate('plan_date', '2026-10-22')
            ->get();

        $this->line(sprintf("%-8s | %-12s | %-3s | %-18s | %-5s | %-8s | %-8s | %-6s | %s",
            'LINE', 'SHIFT', 'ROW', 'JOB NO', 'PLAN', 'DUMP_TYP', 'DB_TYPE', 'DB_ID', 'STATUS'
        ));
        $this->line(str_repeat('-', 100));

        foreach ($sheets as $k => $sec) {
            $press = $sec['press_name'] ?? '';
            $shift = trim(ucwords(strtolower(preg_replace('/[\(\s]REV.*|[\(\s]REVISI.*|\d+/i', '', $sec['shift_name'] ?? ''))));
            $rows = $sec['rows'] ?? [];

            foreach ($rows as $r) {
                $rawJn = $r['job_no'] ?? '';
                $rawJm = $r['job_master'] ?? '';
                $planQty = (int)($r['plan'] ?? 0);
                $dumpType = $r['row_type'] ?? 'job';
                $rowNo = $r['row_no'] ?? 0;

                // Cari di database yang cocok
                $match = $dbPlans->first(function($db) use ($press, $shift, $rawJn, $rawJm, $rowNo) {
                    if ($db->press_name !== $press) return false;
                    if (strcasecmp($db->shift_name, $shift) !== 0 && !str_contains(strtolower($db->shift_name), strtolower($shift))) return false;
                    
                    // Match by job_no or row_no
                    if ($rawJn && strcasecmp(trim($db->job_no), trim($rawJn)) === 0) return true;
                    if ($rowNo && $db->row_no == $rowNo) return true;
                    return false;
                });

                $dbType = $match ? $match->row_type : 'MISSING';
                $dbId = $match ? $match->id : '-';
                $status = $match ? 'MATCH' : 'NOT_IN_DB';

                // Evaluasi status selisih
                if ($match && $match->row_type !== $dumpType) {
                    $status = 'TYPE_MISMATCH';
                }

                $this->line(sprintf("%-8s | %-12s | %-3d | %-18s | %-5d | %-8s | %-8s | %-6s | %s",
                    $press,
                    $shift,
                    $rowNo,
                    substr($rawJn ?: $rawJm, 0, 18),
                    $planQty,
                    $dumpType,
                    $dbType,
                    $dbId,
                    $status
                ));
            }
        }

        $this->info("\n================================================================================");
        return 0;
    }
}
