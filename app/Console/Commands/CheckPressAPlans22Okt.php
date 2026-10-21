<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckPressAPlans22Okt extends Command
{
    protected $signature = 'app:check-pressa-22-okt';
    protected $description = 'Check all rows of PRESS A in DB for 22 Oktober';

    public function handle()
    {
        $plans = DB::table('production_plans')
            ->whereDate('plan_date', '2026-10-22')
            ->where('press_name', 'PRESS A')
            ->where('shift_name', 'Shift Pagi')
            ->orderBy('row_no')
            ->get();

        $this->info("Row No | Job No | Type | Plan | CT | TPT | GSPH Item | Start - Finish");
        foreach ($plans as $p) {
            $this->line(sprintf("%-6d | %-20s | %-6s | %-5d | %-4.1f | %-5.1f | %-10s | %s - %s",
                $p->row_no, $p->job_no, $p->row_type, $p->plan, (float)$p->ct_detik, (float)$p->tpt, $p->gsph_item, $p->start_time, $p->finish_time
            ));
        }

        return 0;
    }
}
