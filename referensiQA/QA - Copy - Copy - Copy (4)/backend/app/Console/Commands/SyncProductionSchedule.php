<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ProductionSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncProductionSchedule extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'qa:sync-schedule';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync production schedules from PPC db_integration to QA system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting production schedule sync...');
        $date = Carbon::today();

        try {
            // Hapus data dummy lama (yang SAP order no formatnya SPK-YYYYMMDD-0X)
            ProductionSchedule::whereDate('tanggal_produksi', $date)
                ->where('sap_order_no', 'like', 'SPK-%')
                ->delete();

            // Ambil semua job dari PPC untuk tanggal ini (hanya row_type = job, bukan break/note)
            $plans = \App\Models\Integration\ProductionPlan::whereDate('plan_date', $date)
                        ->where('row_type', 'job')
                        ->whereNotNull('job_master')
                        ->where('job_master', '!=', '')
                        ->whereNotIn(DB::connection('mysql_integration')->raw('UPPER(TRIM(job_master))'), [
                            'TOTAL FINISH', 'TOTAL FNISH', 'FINISH', 'PLAN',
                            'TOTAL STROKE', 'TOTAL  STROKE', 'TOTAL TPT',
                            'TARGET GSPH', 'GSPH', 'TOTAL PCS', 'DELETE PLAN SHIFT 1', 'TOTAL'
                        ])
                        ->get();

            $syncedCount = 0;

            foreach ($plans as $plan) {
                if (empty($plan->job_no) && empty($plan->job_master)) continue;

                $job_no    = $plan->job_no     ?? $plan->job_master;
                $ok        = (int) ($plan->ok     ?? 0);
                $repair    = (int) ($plan->repair  ?? 0);
                $reject    = (int) ($plan->reject  ?? 0);
                $actual    = $ok + $repair + $reject;

                // Ambil keterangan detail repair/reject dari PPC via job_number match
                $repairNotes = [];
                $rejectNotes = [];

                // Cari job_master di db_integration berdasarkan job_number = job_no
                $jobMaster = DB::connection('mysql_integration')
                    ->table('job_masters')
                    ->where('job_number', $job_no)
                    ->first();

                if ($jobMaster) {
                    $rrLogs = DB::connection('mysql_integration')
                        ->table('repair_reject_logs')
                        ->where('job_master_id', $jobMaster->id)
                        ->get(['type', 'defect_name', 'qty_a', 'qty_b', 'area_problem', 'root_cause', 'countermeasure', 'repair_category']);

                    foreach ($rrLogs as $log) {
                        $entry = [
                            'defect'          => $log->defect_name,
                            'qty'             => ($log->qty_a ?? 0) + ($log->qty_b ?? 0),
                            'area'            => $log->area_problem,
                            'root_cause'      => $log->root_cause,
                            'countermeasure'  => $log->countermeasure,
                        ];
                        if ($log->type === 'repair') {
                            $entry['category'] = $log->repair_category;
                            $repairNotes[] = $entry;
                        } else {
                            $rejectNotes[] = $entry;
                        }
                    }
                }

                ProductionSchedule::updateOrCreate(
                    [
                        'sap_order_no'     => $job_no,
                        'tanggal_produksi' => $date,
                        'press_name'       => $plan->press_name ?? 'UNKNOWN',
                        'shift_name'       => $plan->shift_name ?? 'Shift Pagi',
                    ],
                    [
                        'job_no'                   => $job_no,
                        'part_no'                  => $plan->job_master,
                        'part_name'                => $plan->job_master,
                        'line'                     => $plan->press_name ?? 'Line Unknown',
                        'target_qty'               => (int) ($plan->plan ?? 0),
                        'actual_qty'               => $actual,
                        'ok_qty'                   => $ok,
                        'ng_qty'                   => $reject,
                        'repair_qty'               => $repair,
                        'ppc_plan_id'              => $plan->id,
                        'row_no'                   => $plan->row_no,
                        'production_repair_notes'  => !empty($repairNotes) ? $repairNotes : null,
                        'production_reject_notes'  => !empty($rejectNotes) ? $rejectNotes : null,
                        'status'                   => 'scheduled',
                    ]
                );
                
                $syncedCount++;
            }

            $this->info("Sync completed successfully. Synced {$syncedCount} schedules for today.");
            Log::info("Production Schedule Cron executed successfully. {$syncedCount} records synced.");

        } catch (\Exception $e) {
            $this->error('Failed to sync production data: ' . $e->getMessage());
            Log::error('Production Schedule Cron Failed: ' . $e->getMessage() . ' | ' . $e->getTraceAsString());
        }
    }
}
