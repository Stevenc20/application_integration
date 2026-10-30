<?php

namespace Tests\Feature;

use App\Models\DailyProduction;
use App\Models\JobMaster;
use App\Models\LineMaster;
use App\Models\ProductionLog;
use App\Models\ProductionPlan;
use App\Services\PpcScheduleMergeService;
use App\Services\ProductionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OverPlanProductionTest extends TestCase
{
    use RefreshDatabase;

    protected LineMaster $line;

    protected function setUp(): void
    {
        parent::setUp();

        // Register SQLite fallback for SUBSTRING_INDEX if needed in tests
        $conn = DB::connection();
        if ($conn->getDriverName() === 'sqlite') {
            $pdo = $conn->getPdo();
            if (method_exists($pdo, 'sqliteCreateFunction')) {
                $pdo->sqliteCreateFunction('SUBSTRING_INDEX', function ($string, $delimiter, $count) {
                    if ($string === null || $delimiter === null) return null;
                    $parts = explode($delimiter, $string);
                    if ($count > 0) {
                        return implode($delimiter, array_slice($parts, 0, $count));
                    }
                    if ($count < 0) {
                        return implode($delimiter, array_slice($parts, $count));
                    }
                    return '';
                }, 3);
            }
        }

        $this->line = LineMaster::create([
            'line_code' => 'LINE_A',
            'line_name' => 'Line A',
        ]);
    }

    public function test_logging_actual_production_exceeding_plan_succeeds_without_error()
    {
        $plan = ProductionPlan::create([
            'plan_date' => now()->toDateString(),
            'shift_name' => 'Shift Pagi',
            'line_master_id' => $this->line->id,
            'job_no' => 'JOB-OP-1',
            'job_master' => 'PART-A',
            'plan' => 50,
            'status' => 'running',
        ]);

        $job = JobMaster::create([
            'job_number' => 'JOB-OP-1-'.$plan->id,
            'job_name' => 'PART-A',
            'line' => 'Line A',
            'target_qty' => 50,
            'capacity' => 50,
            'status' => 'running',
            'started_at' => now()->subHours(2),
        ]);

        $service = new ProductionService();

        // 1. Log 50 OK (exactly plan)
        $res1 = $service->saveProductionLog($job->id, [
            'ok_qty' => 50,
            'repair_qty' => 0,
            'reject_qty' => 0,
        ]);
        $this->assertEquals(50, $res1['actualQty']);

        // 2. Log additional 550 OK (Total 600, Plan 50 -> 1200% efficiency)
        // Previously this threw InvalidArgumentException: "Target Plan maksimal 50 PCS!"
        $res2 = $service->saveProductionLog($job->id, [
            'ok_qty' => 550,
            'repair_qty' => 0,
            'reject_qty' => 0,
        ]);

        $this->assertEquals(600, $res2['actualQty']);
        $this->assertEquals(1200.0, $res2['efficiency']);

        $daily = DailyProduction::where('job_master_id', $job->id)->first();
        $this->assertNotNull($daily);
        $this->assertEquals(600, $daily->actual_ok);
        $this->assertEquals(1200.0, (float) $daily->efficiency);
    }

    public function test_finalization_with_over_plan_completes_without_recovery_item()
    {
        $plan = ProductionPlan::create([
            'plan_date' => now()->toDateString(),
            'shift_name' => 'Shift Pagi',
            'line_master_id' => $this->line->id,
            'job_no' => 'JOB-OP-2',
            'job_master' => 'PART-B',
            'plan' => 50,
            'status' => 'running',
        ]);

        $job = JobMaster::create([
            'job_number' => 'JOB-OP-2-'.$plan->id,
            'job_name' => 'PART-B',
            'line' => 'Line A',
            'target_qty' => 50,
            'capacity' => 50,
            'status' => 'running',
            'started_at' => now()->subHours(2),
        ]);

        ProductionLog::create([
            'job_master_id' => $job->id,
            'ok_qty' => 600,
            'repair_qty' => 0,
            'reject_qty' => 0,
        ]);

        $service = new ProductionService();
        $service->finishJob($job->id, 'FINISH_ONLY', true, 600, 0, 0);

        $job->refresh();
        $this->assertEquals('complete', $job->status);

        $daily = DailyProduction::where('job_master_id', $job->id)->first();
        $this->assertNotNull($daily);
        $this->assertEquals(600, $daily->actual_ok);
        $this->assertEquals(1200.0, (float) $daily->efficiency);

        // Auto-recovery should NOT be created because actual (600) >= plan (50)
        $this->assertDatabaseMissing('recovery_schedules', [
            'production_plan_id' => $plan->id,
        ]);
    }

    public function test_ppc_revision_from_50_to_600_updates_same_logical_job_without_duplicate()
    {
        $date = now()->toDateString();
        $plan = ProductionPlan::create([
            'plan_date' => $date,
            'shift_name' => 'Shift Pagi',
            'press_name' => 'Line A',
            'line_master_id' => $this->line->id,
            'row_no' => 1,
            'job_no' => 'JOB-OP-3',
            'job_master' => 'PART-C',
            'plan' => 50,
            'status' => 'running',
        ]);

        $job = JobMaster::create([
            'job_number' => "PART-C-JOB-OP-3-{$this->line->id}-Pagi-{$plan->id}",
            'job_name' => 'PART-C',
            'line' => 'Line A',
            'shift' => 'Pagi',
            'target_qty' => 50,
            'capacity' => 50,
            'status' => 'running',
            'started_at' => now()->subHours(1),
        ]);

        // Simulasikan revisi PPC yang mengubah target menjadi 600 via merge
        $mergeService = app(PpcScheduleMergeService::class);
        $mergeService->merge([
            [
                'sheet_name' => 'Shift Pagi (Rev)',
                'shift_name' => 'Shift Pagi (Rev)',
                'press_name' => 'Line A',
                'rows' => [
                    [
                        'job_master' => 'PART-C',
                        'job_no' => 'JOB-OP-3',
                        'row_no' => 1,
                        'plan' => 600,
                        'qty_plt' => 10,
                        'keb_mtl' => 1,
                        'total_plt' => 10,
                        'ct' => 5,
                        'process_time' => 50,
                        'reg_active' => 0,
                        'dct' => 0,
                        'mct' => 0,
                        'plan_dct' => 50,
                        'tpt' => 100,
                        'gsph' => 200,
                        'start_time' => '07:30',
                        'finish_time' => '12:00',
                    ],
                ],
            ],
        ], $date);

        // Pastikan job master yang sama ter-update target_qty nya, bukan membuat record baru
        $this->assertEquals(600, $job->fresh()->target_qty);

        // Pastikan tidak ada duplikasi job master untuk plan ini
        $jobsForPlan = JobMaster::where('job_number', 'LIKE', "%-{$plan->id}")->get();
        $this->assertCount(1, $jobsForPlan);

        $plan->refresh();
        $this->assertEquals(600, $plan->plan);
    }
}
