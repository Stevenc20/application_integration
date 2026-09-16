<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupKaryawanNrp extends Command
{
    protected $signature = 'karyawan:cleanup-nrp
                            {--dry-run : Only show changes, do not execute}';

    protected $description = 'Trim nrp_karyawan to 4 digits and remove alphanumeric records';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        // 1. Delete alphanumeric records (contain non-digit characters)
        $alpha = DB::table('karyawans')
            ->where('nrp_karyawan', 'REGEXP', '[^0-9]')
            ->get();

        $this->line('Alphanumeric NRP to delete: ' . $alpha->count());
        foreach ($alpha as $k) {
            $this->line("  [{$k->id_karyawan}] {$k->nrp_karyawan} - {$k->nama_karyawan}");
        }

        if (!$dryRun && $alpha->isNotEmpty()) {
            $deleted = DB::table('karyawans')
                ->where('nrp_karyawan', 'REGEXP', '[^0-9]')
                ->delete();
            $this->info("Deleted {$deleted} alphanumeric records.");
        }

        // 2. Trim 5-digit numeric to first 4 digits
        $fiveDigit = DB::table('karyawans')
            ->where('nrp_karyawan', 'REGEXP', '^[0-9]{5}$')
            ->get();

        $this->line('5-digit NRP to trim: ' . $fiveDigit->count());
        foreach ($fiveDigit as $k) {
            $newNrp = substr($k->nrp_karyawan, 0, 4);
            $this->line("  [{$k->id_karyawan}] {$k->nrp_karyawan} -> {$newNrp} ({$k->nama_karyawan})");

            if (!$dryRun) {
                DB::table('karyawans')
                    ->where('id_karyawan', $k->id_karyawan)
                    ->update(['nrp_karyawan' => $newNrp]);
            }
        }

        if (!$dryRun) {
            $this->info("Trimmed {$fiveDigit->count()} records.");
        } else {
            $this->warn('DRY RUN — no changes executed.');
        }

        return Command::SUCCESS;
    }
}
