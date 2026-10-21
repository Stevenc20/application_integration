<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class HardResetDandori extends Command
{
    protected $signature = 'reset:dandori {line=A}';
    protected $description = 'Hard reset all accidental dandori and runtime on specific line';

    public function handle()
    {
        $line = $this->argument('line');
        $linePattern = '%' . trim(str_ireplace(['line', 'press'], '', $line)) . '%';

        $jobs = DB::table('job_masters')->where('line', 'like', $linePattern)->get();
        $jobIds = $jobs->pluck('id')->toArray();

        // 1. Hapus semua downtime di line ini hari ini
        DB::table('downtimes')->whereIn('job_master_id', $jobIds)->delete();

        // 2. Hapus semua dandori records
        DB::table('dandoris')->whereIn('next_job_id', $jobIds)->delete();

        // 3. Hapus semua production sessions
        DB::table('production_sessions')->whereIn('job_master_id', $jobIds)->delete();

        // 4. Hapus semua daily production
        DB::table('daily_productions')->whereIn('job_master_id', $jobIds)->delete();

        // 5. Hapus semua production logs
        DB::table('production_logs')->whereIn('job_master_id', $jobIds)->delete();

        // 6. Hapus recovery items
        DB::table('recovery_items')->delete();

        // 7. Reset status job_masters
        DB::table('job_masters')->whereIn('id', $jobIds)->update([
            'status' => 'pending',
            'started_at' => null,
            'finished_at' => null,
            'updated_at' => now(),
        ]);

        // 8. Reset production plans
        DB::table('production_plans')->whereDate('plan_date', today())->update([
            'act_start' => null,
            'act_finish' => null,
            'status' => 'pending',
        ]);

        $this->info("BERSIH TOTAL LINE {$line}!");
        return 0;
    }
}
