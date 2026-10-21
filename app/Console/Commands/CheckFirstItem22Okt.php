<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckFirstItem22Okt extends Command
{
    protected $signature = 'app:check-first-item-22-okt';
    protected $description = 'Check first item of 22 Oktober in DB';

    public function handle()
    {
        $firstItem = DB::table('production_plans')
            ->whereDate('plan_date', '2026-10-22')
            ->where('press_name', 'PRESS A')
            ->where('shift_name', 'Shift Pagi')
            ->where('row_type', 'job')
            ->orderBy('row_no')
            ->first();

        if ($firstItem) {
            $this->info("First Item ID: {$firstItem->id}");
            $this->line("Job No: {$firstItem->job_no}");
            $this->line("Plan Qty: {$firstItem->plan}");
            $this->line("CT (ct_detik): {$firstItem->ct_detik}");
            $this->line("Process Time: {$firstItem->process_time}");
            $this->line("TPT: {$firstItem->tpt}");
            $this->line("GSPH Item (raw): " . var_export($firstItem->gsph_item, true));
            $this->line("Start - Finish: {$firstItem->start_time} - {$firstItem->finish_time}");
        } else {
            $this->warn("Item tidak ditemukan!");
        }

        return 0;
    }
}
