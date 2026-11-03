<?php

namespace Tests\Feature;

use App\Models\DailyProduction;
use App\Models\JobMaster;
use App\Models\LineMaster;
use App\Models\ProductionPlan;
use App\Models\RepairRejectLog;
use App\Models\RepairRejectImage;
use App\Models\User;
use App\Services\DashboardDetailService;
use App\Services\DashboardRealtimeService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RepairRejectSyncIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        DashboardDetailService::flushCache();
    }

    public function test_detail_modal_distinguishes_manual_logs_from_pure_aggregates(): void
    {
        $line = LineMaster::create(['line_code' => 'PA', 'line_name' => 'Line A', 'status' => 'active']);
        $user = User::factory()->create(['name' => 'Operator A']);
        $date = '2026-11-04';

        $plan = ProductionPlan::create([
            'line_master_id' => $line->id,
            'job_no' => 'JOB-RR-01',
            'job_master' => 'BRACKET ASSY',
            'press_name' => 'Line A',
            'plan_date' => $date,
            'shift_name' => 'Shift Pagi',
            'plan' => 500,
            'row_no' => 1,
            'row_type' => 'job',
        ]);

        $job = JobMaster::create([
            'job_number' => 'JOB-RR-01-' . $plan->id,
            'job_name' => 'BRACKET ASSY',
            'line' => 'Line A',
            'target_qty' => 500,
            'status' => 'complete',
            'started_at' => Carbon::parse($date . ' 08:00:00'),
            'finished_at' => Carbon::parse($date . ' 12:00:00'),
        ]);

        DailyProduction::create([
            'job_master_id' => $job->id,
            'work_date' => $date,
            'actual_ok' => 450,
            'actual_qty' => 470,
            'actual_repair' => 15,
            'actual_reject' => 5,
            'runtime_seconds' => 7200,
            'line' => 'Line A',
            'shift' => 'Shift Pagi',
        ]);

        // Manual log ONLY for Repair
        $repairLog = RepairRejectLog::create([
            'job_master_id' => $job->id,
            'type' => 'repair',
            'defect_name' => 'Dent / Baret',
            'qty_a' => 15,
            'repair_category' => 'Press',
            'area_problem' => 'OP 10',
            'root_cause' => 'Burr pada die',
            'countermeasure' => 'Pembersihan die',
            'created_by' => $user->id,
        ]);

        RepairRejectImage::create([
            'repair_reject_log_id' => $repairLog->id,
            'image_path' => 'repair_reject/' . $repairLog->id . '/test_part.webp',
            'image_type' => 'before',
        ]);

        $service = app(DashboardDetailService::class);
        $details = $service->getLineDetail('Line A', $date, 1);

        $this->assertNotEmpty($details);
        $row = $details[0];

        $this->assertEquals(15, $row['repair']);
        $this->assertEquals(5, $row['reject']);

        // 1. Manual Repair Log: Has real defect name, PIC, root cause, countermeasure, and images
        $this->assertCount(1, $row['repair_list']);
        $this->assertEquals('Dent / Baret', $row['repair_list'][0]['defect_name']);
        $this->assertEquals('Operator A', $row['repair_list'][0]['pic']);
        $this->assertEquals('Burr pada die', $row['repair_list'][0]['root_cause']);
        $this->assertEquals('Pembersihan die', $row['repair_list'][0]['countermeasure']);
        $this->assertNotEmpty($row['repair_list'][0]['images']);
        $this->assertArrayNotHasKey('is_aggregate', $row['repair_list'][0]);

        // 2. Reject has NO manual log: Must show as aggregate placeholder WITHOUT fake root cause or fake PIC
        $this->assertCount(1, $row['reject_list']);
        $this->assertEquals(5, $row['reject_list'][0]['qty']);
        $this->assertEquals('Reject (Agregat Produksi)', $row['reject_list'][0]['defect_name']);
        $this->assertEquals('-', $row['reject_list'][0]['root_cause']);
        $this->assertEquals('-', $row['reject_list'][0]['countermeasure']);
        $this->assertEquals('-', $row['reject_list'][0]['pic']);
        $this->assertEmpty($row['reject_list'][0]['images']);
        $this->assertTrue($row['reject_list'][0]['is_aggregate']);
    }

    public function test_input_harian_queue_falls_back_to_plan_counts_when_daily_production_unmapped(): void
    {
        $line = LineMaster::create(['line_code' => 'PB', 'line_name' => 'Line B', 'status' => 'active']);
        $date = \App\Services\ProductionMetricsService::currentProductionDate();

        $plan = ProductionPlan::create([
            'line_master_id' => $line->id,
            'job_no' => 'JOB-RR-02',
            'job_master' => 'PANEL INNER',
            'press_name' => 'Line B',
            'plan_date' => $date,
            'shift_name' => 'Shift Pagi',
            'plan' => 300,
            'ok' => 280,
            'repair' => 15,
            'reject' => 5,
            'row_no' => 1,
            'row_type' => 'job',
            'status' => 'complete',
        ]);

        $job = JobMaster::create([
            'job_number' => 'JOB-RR-02-' . $plan->id,
            'job_name' => 'PANEL INNER',
            'line' => 'Line B',
            'target_qty' => 300,
            'status' => 'complete',
        ]);

        $user = User::create([
            'name' => 'Tester Supervisor',
            'nrp' => 'SUP-001',
            'password' => bcrypt('secret'),
            'role' => 'superadmin',
        ]);

        $response = $this->actingAs($user)->get(route('operational.input_harian', [
            'date' => $date,
            'line' => 'Line B',
            'shift' => 'Shift Pagi',
        ]));

        $response->assertStatus(200);
        $response->assertSee('JOB-RR-02');
        $response->assertSee('15');
        $response->assertSee('5');
    }

    public function test_night_shift_crossing_midnight_and_multiple_items_sync_consistently(): void
    {
        $line = LineMaster::create(['line_code' => 'PD', 'line_name' => 'Line D', 'status' => 'active']);
        $date = '2026-11-04';

        // Item 1: Complete with manual log
        $plan1 = ProductionPlan::create([
            'line_master_id' => $line->id,
            'job_no' => 'JOB-NIGHT-01',
            'job_master' => 'FLOOR SIDE',
            'press_name' => 'Line D',
            'plan_date' => $date,
            'shift_name' => 'Shift Malam',
            'plan' => 400,
            'row_no' => 1,
            'row_type' => 'job',
        ]);

        $job1 = JobMaster::create([
            'job_number' => 'JOB-NIGHT-01-' . $plan1->id,
            'job_name' => 'FLOOR SIDE',
            'line' => 'Line D',
            'target_qty' => 400,
            'status' => 'complete',
        ]);

        DailyProduction::create([
            'job_master_id' => $job1->id,
            'work_date' => $date,
            'actual_ok' => 380,
            'actual_qty' => 395,
            'actual_repair' => 10,
            'actual_reject' => 5,
            'runtime_seconds' => 5400,
            'line' => 'Line D',
            'shift' => 'Shift Malam',
        ]);

        // Item 2: Running with aggregate only
        $plan2 = ProductionPlan::create([
            'line_master_id' => $line->id,
            'job_no' => 'JOB-NIGHT-02',
            'job_master' => 'CROSS MEMBER',
            'press_name' => 'Line D',
            'plan_date' => $date,
            'shift_name' => 'Shift Malam',
            'plan' => 600,
            'row_no' => 2,
            'row_type' => 'job',
        ]);

        $job2 = JobMaster::create([
            'job_number' => 'JOB-NIGHT-02-' . $plan2->id,
            'job_name' => 'CROSS MEMBER',
            'line' => 'Line D',
            'target_qty' => 600,
            'status' => 'running',
        ]);

        DailyProduction::create([
            'job_master_id' => $job2->id,
            'work_date' => $date,
            'actual_ok' => 150,
            'actual_qty' => 170,
            'actual_repair' => 20,
            'actual_reject' => 0,
            'runtime_seconds' => 3600,
            'line' => 'Line D',
            'shift' => 'Shift Malam',
        ]);

        $service = app(DashboardDetailService::class);
        $details = $service->getLineDetail('Line D', $date, 2);

        $this->assertCount(2, $details);

        // Verify Item 1 counts
        $this->assertEquals(380, $details[0]['good']);
        $this->assertEquals(10, $details[0]['repair']);
        $this->assertEquals(5, $details[0]['reject']);

        // Verify Item 2 counts (Running, aggregate repair 20, reject 0)
        $this->assertEquals(150, $details[1]['good']);
        $this->assertEquals(20, $details[1]['repair']);
        $this->assertEquals(0, $details[1]['reject']);
        $this->assertCount(1, $details[1]['repair_list']);
        $this->assertTrue($details[1]['repair_list'][0]['is_aggregate']);
        $this->assertEquals('-', $details[1]['repair_list'][0]['root_cause']);
    }

    public function test_input_harian_queue_respects_zero_daily_production_vs_plan_fallback(): void
    {
        $line = LineMaster::create(['line_code' => 'PC', 'line_name' => 'Line C', 'status' => 'active']);
        $date = \App\Services\ProductionMetricsService::currentProductionDate();

        // Kasus A: DailyProduction ada dan bernilai 0 aktualnya (job baru running/pending dan memang belum ada hasil)
        $planA = ProductionPlan::create([
            'line_master_id' => $line->id,
            'job_no' => 'JOB-ZERO-DP',
            'job_master' => 'BEAM REAR',
            'press_name' => 'Line C',
            'plan_date' => $date,
            'shift_name' => 'Shift Pagi',
            'plan' => 200,
            'ok' => 50, // Nilai lama di plan
            'repair' => 10,
            'reject' => 2,
            'row_no' => 1,
            'row_type' => 'job',
            'status' => 'pending',
        ]);

        $jobA = JobMaster::create([
            'job_number' => 'JOB-ZERO-DP-' . $planA->id,
            'job_name' => 'BEAM REAR',
            'line' => 'Line C',
            'target_qty' => 200,
            'status' => 'pending',
        ]);

        // DailyProduction ada dengan aktual 0
        DailyProduction::create([
            'job_master_id' => $jobA->id,
            'work_date' => $date,
            'actual_ok' => 0,
            'actual_qty' => 0,
            'actual_repair' => 0,
            'actual_reject' => 0,
            'line' => 'Line C',
            'shift' => 'Shift Pagi',
        ]);

        $user = User::create([
            'name' => 'Tester Supervisor 2',
            'nrp' => 'SUP-002',
            'password' => bcrypt('secret'),
            'role' => 'superadmin',
        ]);

        $response = $this->actingAs($user)->get(route('operational.input_harian', [
            'date' => $date,
            'line' => 'Line C',
            'shift' => 'Shift Pagi',
        ]));

        $response->assertStatus(200);
        $response->assertSee('JOB-ZERO-DP');
    }

    public function test_detail_modal_handles_partial_manual_logs_and_remaining_aggregate_difference(): void
    {
        $line = LineMaster::create(['line_code' => 'PA', 'line_name' => 'Line A', 'status' => 'active']);
        $user = User::factory()->create(['name' => 'Operator A']);
        $date = '2026-11-04';

        $plan = ProductionPlan::create([
            'line_master_id' => $line->id,
            'job_no' => 'JOB-PARTIAL-01',
            'job_master' => 'HOOD ASSY',
            'press_name' => 'Line A',
            'plan_date' => $date,
            'shift_name' => 'Shift Pagi',
            'plan' => 500,
            'row_no' => 1,
            'row_type' => 'job',
        ]);

        $job = JobMaster::create([
            'job_number' => 'JOB-PARTIAL-01-' . $plan->id,
            'job_name' => 'HOOD ASSY',
            'line' => 'Line A',
            'target_qty' => 500,
            'status' => 'complete',
        ]);

        // Agregat produksi: Repair 25 pcs, Reject 10 pcs
        DailyProduction::create([
            'job_master_id' => $job->id,
            'work_date' => $date,
            'actual_ok' => 465,
            'actual_qty' => 500,
            'actual_repair' => 25,
            'actual_reject' => 10,
            'runtime_seconds' => 7200,
            'line' => 'Line A',
            'shift' => 'Shift Pagi',
        ]);

        // Manual log HANYA SEBAGIAN:
        // Repair baru dicatat 10 pcs (sisa 15 pcs belum dibuat log manual)
        $repairLog = RepairRejectLog::create([
            'job_master_id' => $job->id,
            'type' => 'repair',
            'defect_name' => 'Dent Kecil',
            'qty_a' => 10,
            'repair_category' => 'Press',
            'area_problem' => 'OP 20',
            'root_cause' => 'Debu die',
            'countermeasure' => 'Wiping',
            'created_by' => $user->id,
        ]);

        RepairRejectImage::create([
            'repair_reject_log_id' => $repairLog->id,
            'image_path' => 'repair_reject/' . $repairLog->id . '/partial_repair.webp',
            'image_type' => 'before',
        ]);

        // Reject baru dicatat 4 pcs (sisa 6 pcs belum dibuat log manual)
        $rejectLog = RepairRejectLog::create([
            'job_master_id' => $job->id,
            'type' => 'reject',
            'defect_name' => 'Sobek Parah',
            'qty_a' => 4,
            'repair_category' => 'Press',
            'area_problem' => 'OP 10',
            'root_cause' => 'Material tipis',
            'countermeasure' => 'Sortir supplier',
            'created_by' => $user->id,
        ]);

        RepairRejectImage::create([
            'repair_reject_log_id' => $rejectLog->id,
            'image_path' => 'repair_reject/' . $rejectLog->id . '/partial_reject.webp',
            'image_type' => 'before',
        ]);

        $service = app(DashboardDetailService::class);
        $details = $service->getLineDetail('Line A', $date, 1);

        $this->assertNotEmpty($details);
        $row = $details[0];

        // Total kuantitas pada baris tetap sama persis dengan agregat
        $this->assertEquals(25, $row['repair']);
        $this->assertEquals(10, $row['reject']);

        // Repair list harus memiliki 2 entri:
        // 1. Manual log: 10 pcs (dengan Root Cause riil)
        // 2. Sisa agregat: 15 pcs (dengan Root Cause '-')
        $this->assertCount(2, $row['repair_list']);
        $totalRepairItemsQty = array_sum(array_column($row['repair_list'], 'qty'));
        $this->assertEquals(25, $totalRepairItemsQty); // Tidak boleh ada selisih ataupun double counting!
        $this->assertEquals('Dent Kecil', $row['repair_list'][0]['defect_name']);
        $this->assertEquals(10, $row['repair_list'][0]['qty']);
        $this->assertEquals('Repair (Sisa Agregat Produksi)', $row['repair_list'][1]['defect_name']);
        $this->assertEquals(15, $row['repair_list'][1]['qty']);
        $this->assertTrue($row['repair_list'][1]['is_aggregate']);

        // Reject list juga harus memiliki 2 entri:
        // 1. Manual log: 4 pcs
        // 2. Sisa agregat: 6 pcs
        $this->assertCount(2, $row['reject_list']);
        $totalRejectItemsQty = array_sum(array_column($row['reject_list'], 'qty'));
        $this->assertEquals(10, $totalRejectItemsQty); // Tidak boleh ada selisih ataupun double counting!
        $this->assertEquals('Sobek Parah', $row['reject_list'][0]['defect_name']);
        $this->assertEquals(4, $row['reject_list'][0]['qty']);
        $this->assertEquals('Reject (Sisa Agregat Produksi)', $row['reject_list'][1]['defect_name']);
        $this->assertEquals(6, $row['reject_list'][1]['qty']);
        $this->assertTrue($row['reject_list'][1]['is_aggregate']);
    }
}
