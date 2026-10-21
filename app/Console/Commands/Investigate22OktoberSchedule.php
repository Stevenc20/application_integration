<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class Investigate22OktoberSchedule extends Command
{
    protected $signature = 'app:investigate-22-okt';
    protected $description = 'Forensic investigation of 22 Oktober schedule, import dump, and PRESS C breakdown';

    public function handle()
    {
        $this->info("================================================================================");
        $this->info(" INVESTIGASI FORENSIK 22 OKTOBER & DUMP IMPORT PPC");
        $this->info("================================================================================");

        // 1. Cek isi storage/logs/import_dump.json
        $dumpPath = storage_path('logs/import_dump.json');
        if (file_exists($dumpPath)) {
            $dump = json_decode(file_get_contents($dumpPath), true);
            $this->info("\n--- 1. METADATA PARSER DARI storage/logs/import_dump.json ---");
            $this->line("Parser Engine: " . ($dump['source'] ?? 'unknown'));
            $data = $dump['data'] ?? [];
            $this->line("Upload Date terdeteksi: " . ($data['upload_date'] ?? '-'));
            $sheets = $data['sheets'] ?? [];
            $this->line("Total Sections/Sheets terbaca: " . count($sheets));
            foreach ($sheets as $k => $s) {
                $this->line(sprintf(" - [%s] Press: %-8s | Shift: %-12s | Rows: %d",
                    $k, $s['press_name'] ?? '-', $s['shift_name'] ?? '-', count($s['rows'] ?? [])
                ));
            }

            // Cek log summary import jika ada
            if (isset($data['log']['summary'])) {
                $this->line("Log Summary: " . json_encode($data['log']['summary']));
            }
        } else {
            $this->warn("File storage/logs/import_dump.json tidak ditemukan!");
        }

        // 2. Tampilkan semua baris production_plans tanggal 2026-10-22
        $this->info("\n--- 2. DETAIL SELURUH BARIS DATABASE TANGGAL 2026-10-22 ---");
        $plans = DB::table('production_plans')
            ->whereDate('plan_date', '2026-10-22')
            ->orderBy('press_name')
            ->orderBy('shift_name')
            ->orderBy('row_no')
            ->get([
                'id', 'press_name', 'shift_name', 'row_no', 'row_type', 'job_no', 
                'job_master', 'plan', 'ct_detik', 'start_time', 'finish_time', 'status'
            ]);

        $this->line(sprintf("%-6s | %-8s | %-12s | %-3s | %-6s | %-18s | %-6s | %-11s | %-8s",
            'ID', 'LINE', 'SHIFT', 'ROW', 'TYPE', 'JOB NO', 'PLAN', 'START-FIN', 'STATUS'
        ));
        $this->line(str_repeat('-', 95));

        $countByPressType = [];
        foreach ($plans as $p) {
            $key = $p->press_name . ' (' . $p->shift_name . ')';
            $countByPressType[$key][$p->row_type] = ($countByPressType[$key][$p->row_type] ?? 0) + 1;

            $this->line(sprintf("%-6d | %-8s | %-12s | %-3d | %-6s | %-18s | %-6d | %s-%s | %-8s",
                $p->id,
                $p->press_name,
                $p->shift_name,
                $p->row_no,
                $p->row_type,
                substr($p->job_no ?: $p->job_master, 0, 18),
                $p->plan,
                $p->start_time ?: '--:--',
                $p->finish_time ?: '--:--',
                $p->status
            ));
        }

        $this->info("\n--- 3. REKAP KLASIFIKASI ROW_TYPE PER LINE 2026-10-22 ---");
        foreach ($countByPressType as $line => $types) {
            $this->line(sprintf(" * %-22s -> %s", $line, json_encode($types)));
        }

        // 4. Cek apakah ada jadwal Shift Malam untuk tanggal 2026-10-22 atau 2026-10-21 di DB
        $this->info("\n--- 4. AUDIT SHIFT MALAM DI DATABASE ---");
        $nightPlans = DB::table('production_plans')
            ->where('shift_name', 'LIKE', '%Malam%')
            ->whereDate('plan_date', '>=', '2026-10-20')
            ->select('id', 'plan_date', 'shift_name', 'press_name', 'row_no', 'row_type', 'job_no', 'plan', 'status')
            ->orderBy('plan_date')
            ->orderBy('press_name')
            ->get();
        
        $this->line("Total baris Shift Malam (>= 2026-10-20): " . $nightPlans->count());
        foreach ($nightPlans as $np) {
            $this->line(sprintf(" [%s] %-8s | %-12s | Row %-2d | %-5s | %-18s | Plan: %-4d | %s",
                $np->plan_date, $np->press_name, $np->shift_name, $np->row_no, $np->row_type, $np->job_no, $np->plan, $np->status
            ));
        }

        $this->info("\n================================================================================");
        return 0;
    }
}
