<?php

namespace Tests\Feature;

use App\Models\DailyProduction;
use App\Models\Feature;
use App\Models\JobMaster;
use App\Models\LineMaster;
use App\Models\MasterShift;
use App\Models\ProductionPlan;
use App\Models\ProductionSession;
use App\Models\RoleFeature;
use App\Models\User;
use App\Services\ProductionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultipleScheduleIdentityTest extends TestCase
{
    use RefreshDatabase;

    protected User $leader;
    protected LineMaster $lineA;
    protected MasterShift $shiftPagi;
    protected MasterShift $shiftMalam;
    protected string $today;

    protected function setUp(): void
    {
        parent::setUp();

        $this->today = '2026-11-05';
        Carbon::setTestNow("{$this->today} 09:00:00");

        $this->lineA = LineMaster::create([
            'line_code' => 'PRESS_A',
            'line_name' => 'Press A',
            'status'    => 'active',
            'urut'      => 1,
        ]);

        $this->shiftPagi = MasterShift::firstOrCreate(
            ['name' => 'Shift Pagi'],
            ['planned_start_time' => '07:30', 'planned_end_time' => '21:00']
        );
        $this->shiftMalam = MasterShift::firstOrCreate(
            ['name' => 'Shift Malam'],
            ['planned_start_time' => '21:00', 'planned_end_time' => '07:30']
        );

        $this->leader = User::create([
            'name'     => 'Leader A',
            'nrp'      => 'LD-001',
            'role'     => 'leader',
            'password' => bcrypt('password'),
        ]);

        $inputHarian = Feature::firstOrCreate(
            ['feature_code' => 'input_harian'],
            ['feature_name' => 'Input Harian', 'group_name' => 'Operational']
        );
        RoleFeature::firstOrCreate(['role' => 'leader', 'feature_id' => $inputHarian->id], ['enabled' => true]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * 1. Dua schedule dengan Job No dan Part Number sama (Kasus RC-2770)
     * Penyelesaian schedule pertama (505 pcs) TIDAK boleh membuat schedule kedua (395 pcs) complete.
     */
    public function test_kasus_a_rc2770_two_schedules_first_finish_keeps_second_pending(): void
    {
        // Schedule 1: 505 pcs
        $plan1 = ProductionPlan::create([
            'line_master_id' => $this->lineA->id,
            'plan_date'      => $this->today,
            'shift_name'     => 'Shift Pagi',
            'press_name'     => 'Press A',
            'row_no'         => 1,
            'row_type'       => 'job',
            'job_no'         => 'RC-2770',
            'job_master'     => 'RC-2770 FNS',
            'plan'           => 505,
            'status'         => 'running',
        ]);
        $jm1 = JobMaster::create([
            'job_number'  => "RC-2770-{$plan1->id}",
            'job_name'    => 'RC-2770 FNS',
            'line'        => 'Press A',
            'status'      => 'running',
            'target_qty'  => 505,
            'sequence_no' => 1,
        ]);

        // Schedule 2: 395 pcs
        $plan2 = ProductionPlan::create([
            'line_master_id' => $this->lineA->id,
            'plan_date'      => $this->today,
            'shift_name'     => 'Shift Pagi',
            'press_name'     => 'Press A',
            'row_no'         => 2,
            'row_type'       => 'job',
            'job_no'         => 'RC-2770',
            'job_master'     => 'RC-2770 FNS',
            'plan'           => 395,
            'status'         => 'pending',
        ]);
        $jm2 = JobMaster::create([
            'job_number'  => "RC-2770-{$plan2->id}",
            'job_name'    => 'RC-2770 FNS',
            'line'        => 'Press A',
            'status'      => 'pending',
            'target_qty'  => 395,
            'sequence_no' => 2,
        ]);

        // Finish JM 1 with 505 actual OK
        $service = app(ProductionService::class);
        $service->finishJob($jm1->id, null, false, 505, 0, 0);

        // Verification:
        $this->assertEquals('complete', $jm1->fresh()->status);
        $this->assertTrue(in_array($plan1->fresh()->status, ['complete', 'completed']));

        // Schedule 2 MUST remain pending and untouched!
        $this->assertEquals('pending', $jm2->fresh()->status);
        $this->assertEquals('pending', $plan2->fresh()->status);

        // Operator can start schedule 2 without "Qty sudah terpenuhi" error!
        $service->startJob($jm2->id);
        $this->assertEquals('running', $jm2->fresh()->status);
    }

    /**
     * 2. Tiga schedule dengan Part Number sama (Kasus AES-011V: 200, 180, 60 pcs)
     * Setelah schedule 200 pcs selesai, schedule 180 dan 60 pcs dapat dipilih dan dimulai!
     */
    public function test_kasus_b_aes011v_three_schedules_transition(): void
    {
        $plan1 = ProductionPlan::create([
            'line_master_id' => $this->lineA->id,
            'plan_date'      => $this->today,
            'shift_name'     => 'Shift Pagi',
            'press_name'     => 'Press A',
            'row_no'         => 1,
            'row_type'       => 'job',
            'job_no'         => 'AES-011V',
            'job_master'     => 'AES-011V FNS',
            'plan'           => 200,
            'status'         => 'running',
        ]);
        $jm1 = JobMaster::create([
            'job_number'  => "AES-011V-{$plan1->id}",
            'job_name'    => 'AES-011V FNS',
            'line'        => 'Press A',
            'status'      => 'running',
            'target_qty'  => 200,
            'sequence_no' => 1,
        ]);

        $plan2 = ProductionPlan::create([
            'line_master_id' => $this->lineA->id,
            'plan_date'      => $this->today,
            'shift_name'     => 'Shift Pagi',
            'press_name'     => 'Press A',
            'row_no'         => 2,
            'row_type'       => 'job',
            'job_no'         => 'AES-011V',
            'job_master'     => 'AES-011V FNS',
            'plan'           => 180,
            'status'         => 'pending',
        ]);
        $jm2 = JobMaster::create([
            'job_number'  => "AES-011V-{$plan2->id}",
            'job_name'    => 'AES-011V FNS',
            'line'        => 'Press A',
            'status'      => 'pending',
            'target_qty'  => 180,
            'sequence_no' => 2,
        ]);

        $plan3 = ProductionPlan::create([
            'line_master_id' => $this->lineA->id,
            'plan_date'      => $this->today,
            'shift_name'     => 'Shift Pagi',
            'press_name'     => 'Press A',
            'row_no'         => 3,
            'row_type'       => 'job',
            'job_no'         => 'AES-011V',
            'job_master'     => 'AES-011V FNS',
            'plan'           => 60,
            'status'         => 'pending',
        ]);
        $jm3 = JobMaster::create([
            'job_number'  => "AES-011V-{$plan3->id}",
            'job_name'    => 'AES-011V FNS',
            'line'        => 'Press A',
            'status'      => 'pending',
            'target_qty'  => 60,
            'sequence_no' => 3,
        ]);

        $service = app(ProductionService::class);

        // 1. Selesaikan schedule 1 (200 pcs)
        $service->finishJob($jm1->id, null, false, 200, 0, 0);

        $this->assertEquals('complete', $jm1->fresh()->status);
        $this->assertEquals('pending', $jm2->fresh()->status);
        $this->assertEquals('pending', $jm3->fresh()->status);

        // 2. Mulai schedule 2 (180 pcs) via startDandori / startJob
        $service->startDandori($jm2->id, $this->today);
        $this->assertEquals('running', $jm2->fresh()->status);

        // Selesaikan schedule 2 (180 pcs)
        $service->finishJob($jm2->id, null, false, 180, 0, 0);
        $this->assertEquals('complete', $jm2->fresh()->status);
        $this->assertEquals('pending', $jm3->fresh()->status);

        // 3. Mulai schedule 3 (60 pcs)
        $service->startJob($jm3->id, false, $this->today);
        $this->assertEquals('running', $jm3->fresh()->status);
    }

    /**
     * 3. Pemilihan Next Job Otomatis (Auto-Next)
     * Ketika schedule 1 selesai, getNextJob() harus mengembalikan schedule 2 berikutnya
     * meskipun memiliki Job No / Part Number yang sama.
     */
    public function test_auto_next_resolves_next_schedule_with_same_job_no(): void
    {
        $plan1 = ProductionPlan::create([
            'line_master_id' => $this->lineA->id,
            'plan_date'      => $this->today,
            'shift_name'     => 'Shift Pagi',
            'press_name'     => 'Press A',
            'row_no'         => 1,
            'row_type'       => 'job',
            'job_no'         => 'PART-X',
            'job_master'     => 'PART-X ITEM',
            'plan'           => 100,
            'status'         => 'running',
        ]);
        $jm1 = JobMaster::create([
            'job_number'  => "PART-X-{$plan1->id}",
            'job_name'    => 'PART-X ITEM',
            'line'        => 'Press A',
            'status'      => 'running',
            'target_qty'  => 100,
            'sequence_no' => 1,
        ]);

        $plan2 = ProductionPlan::create([
            'line_master_id' => $this->lineA->id,
            'plan_date'      => $this->today,
            'shift_name'     => 'Shift Pagi',
            'press_name'     => 'Press A',
            'row_no'         => 2,
            'row_type'       => 'job',
            'job_no'         => 'PART-X',
            'job_master'     => 'PART-X ITEM',
            'plan'           => 250,
            'status'         => 'pending',
        ]);
        $jm2 = JobMaster::create([
            'job_number'  => "PART-X-{$plan2->id}",
            'job_name'    => 'PART-X ITEM',
            'line'        => 'Press A',
            'status'      => 'pending',
            'target_qty'  => 250,
            'sequence_no' => 2,
        ]);

        $service = app(ProductionService::class);
        $next = $service->getNextJob($jm1->id);

        $this->assertNotNull($next);
        $this->assertEquals($jm2->id, $next->id);
    }

    /**
     * 4. Break-split segments (parent_job_id) tetap sinkron dan tidak rusak.
     */
    public function test_true_break_split_sync_remains_functional(): void
    {
        $parentPlan = ProductionPlan::create([
            'line_master_id' => $this->lineA->id,
            'plan_date'      => $this->today,
            'shift_name'     => 'Shift Pagi',
            'press_name'     => 'Press A',
            'row_no'         => 1,
            'row_type'       => 'job',
            'job_no'         => 'SPLIT-01',
            'job_master'     => 'SPLIT ITEM',
            'plan'           => 300,
            'status'         => 'running',
            'parent_job_id'  => null,
        ]);
        $parentJm = JobMaster::create([
            'job_number'  => "SPLIT-01-{$parentPlan->id}",
            'job_name'    => 'SPLIT ITEM',
            'line'        => 'Press A',
            'status'      => 'running',
            'target_qty'  => 300,
            'sequence_no' => 1,
        ]);

        $childPlan = ProductionPlan::create([
            'line_master_id' => $this->lineA->id,
            'plan_date'      => $this->today,
            'shift_name'     => 'Shift Pagi',
            'press_name'     => 'Press A',
            'row_no'         => 2,
            'row_type'       => 'job',
            'job_no'         => 'SPLIT-01',
            'job_master'     => 'SPLIT ITEM',
            'plan'           => 200,
            'status'         => 'pending',
            'parent_job_id'  => $parentPlan->id,
        ]);
        $childJm = JobMaster::create([
            'job_number'  => "SPLIT-01-{$childPlan->id}",
            'job_name'    => 'SPLIT ITEM',
            'line'        => 'Press A',
            'status'      => 'pending',
            'target_qty'  => 200,
            'sequence_no' => 2,
        ]);

        $service = app(ProductionService::class);
        $siblings = $service->getSiblingJobMasters($parentJm->id);

        // Child is a true break-split sibling
        $this->assertTrue($siblings->contains('id', $childJm->id));
    }
}
