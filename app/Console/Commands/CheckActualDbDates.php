<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckActualDbDates extends Command
{
    protected $signature = 'app:check-actual-db-dates';
    protected $description = 'Check distinct plan_dates and raw plan_date values in production_plans';

    public function handle()
    {
        $dumpPath = storage_path('logs/import_dump.json');
        if (file_exists($dumpPath)) {
            $dump = json_decode(file_get_contents($dumpPath), true);
            $this->line("Dump upload_date raw: " . var_export($dump['data']['upload_date'] ?? null, true));
        }

        $this->info("\n--- DISTINCT PLAN DATES IN DB ---");
        $dates = DB::table('production_plans')
            ->selectRaw('plan_date, DATE(plan_date) as d, COUNT(*) as c')
            ->groupBy('plan_date', DB::raw('DATE(plan_date)'))
            ->orderByDesc('d')
            ->get();

        foreach ($dates as $d) {
            $this->line(" Raw: '{$d->plan_date}' | DATE(): '{$d->d}' | Count: {$d->c}");
        }

        $this->info("\n--- SAMPLE ROWS WITH ID 17132 ---");
        $sample = DB::table('production_plans')->find(17132);
        if ($sample) {
            $this->line(" ID 17132: Date='{$sample->plan_date}', Press='{$sample->press_name}', Shift='{$sample->shift_name}', Job='{$sample->job_no}', Type='{$sample->row_type}'");
        } else {
            $this->warn(" ID 17132 tidak ditemukan!");
            $latest = DB::table('production_plans')->orderByDesc('id')->take(3)->get();
            foreach ($latest as $l) {
                $this->line(" Latest ID {$l->id}: Date='{$l->plan_date}', Press='{$l->press_name}', Shift='{$l->shift_name}', Job='{$l->job_no}', Type='{$l->row_type}'");
            }
        }

        return 0;
    }
}
