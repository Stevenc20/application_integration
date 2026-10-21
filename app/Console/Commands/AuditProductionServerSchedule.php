<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\LineMaster;
use App\Models\ProductionPlan;
use App\Models\JobMaster;
use App\Services\ExcelScheduleParser;

class AuditProductionServerSchedule extends Command
{
    protected $signature = 'app:audit-production-server-schedule';
    protected $description = 'Audit database, uploaded files, and storage on production server';

    public function handle()
    {
        $this->info("================================================================================");
        $this->info(" AUDIT & PEMULIHAN SCHEDULE SERVER PRODUCTION (DOCKER / STORAGE / DB)");
        $this->info("================================================================================");

        // 1. Audit File Upload yang ada di storage/app/uploads
        $uploadDir = storage_path('app/uploads');
        $this->info("\n--- 1. DAFTAR FILE DI STORAGE SERVER ($uploadDir) ---");
        if (is_dir($uploadDir)) {
            $files = scandir($uploadDir);
            foreach ($files as $f) {
                if ($f === '.' || $f === '..') continue;
                $fPath = $uploadDir . '/' . $f;
                $size = round(filesize($fPath) / 1024, 2) . ' KB';
                $mod = date('Y-m-d H:i:s', filemtime($fPath));
                $this->line(" - $f ($size, modified: $mod)");
            }
        } else {
            $this->warn(" Direktori uploads tidak ditemukan!");
        }

        // Cek log trace upload terakhir
        $dumpFile = storage_path('logs/import_dump.json');
        if (file_exists($dumpFile)) {
            $dumpSize = round(filesize($dumpFile) / 1024, 2) . ' KB';
            $dumpMod = date('Y-m-d H:i:s', filemtime($dumpFile));
            $this->info(" - Trace upload log ditemukan: logs/import_dump.json ($dumpSize, $dumpMod)");
        }

        // 2. Audit Jadwal di Database per Tanggal & Shift
        $this->info("\n--- 2. RINGKASAN SCHEDULE DI DATABASE PRODUCTION (production_plans) ---");
        $plansSummary = DB::table('production_plans')
            ->selectRaw('DATE(plan_date) as pdate, shift_name, press_name, COUNT(*) as total_rows, SUM(CASE WHEN row_type="job" THEN 1 ELSE 0 END) as job_rows, SUM(CASE WHEN row_type="break" THEN 1 ELSE 0 END) as break_rows')
            ->groupBy(DB::raw('DATE(plan_date)'), 'shift_name', 'press_name')
            ->orderByDesc('pdate')
            ->orderBy('press_name')
            ->orderBy('shift_name')
            ->get();

        if ($plansSummary->isEmpty()) {
            $this->warn(" Tidak ada data sama sekali di tabel production_plans!");
        } else {
            foreach ($plansSummary as $p) {
                $this->line(sprintf(" [%s] %-8s | %-12s | Total: %2d baris (Job: %2d, Break: %2d)",
                    $p->pdate, $p->press_name, $p->shift_name, $p->total_rows, $p->job_rows, $p->break_rows
                ));
            }
        }

        // 3. Cek Audit Trail / Production Plan History / Log
        $this->info("\n--- 3. STATUS LOG IMPORT PPC TERAKHIR ---");
        $auditLogs = DB::table('production_plans')
            ->orderByDesc('updated_at')
            ->take(5)
            ->get(['id', 'plan_date', 'shift_name', 'press_name', 'job_no', 'row_no', 'updated_at']);
        
        foreach ($auditLogs as $al) {
            $this->line(" Plan ID {$al->id} | {$al->plan_date} | {$al->shift_name} | {$al->press_name} | {$al->job_no} | Row #{$al->row_no} | Updated: {$al->updated_at}");
        }

        $this->info("\n================================================================================");
        return 0;
    }
}
