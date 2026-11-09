<?php

namespace Tests\Feature;

use App\Models\JobMaster;
use App\Models\LineMaster;
use App\Models\ProductionPlan;
use App\Services\ProductionMetricsService;
use App\Services\ProductionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OneScheduleOneJobIdentityP0Test extends TestCase
{
    use RefreshDatabase;

    protected LineMaster $line;

    protected function setUp(): void
    {
        parent::setUp();

        $this->line = LineMaster::firstOrCreate(
            ['line_code' => '400T'],
            ['line_name' => '400T', 'kapasitas' => 400, 'type_line' => 'stamping']
        );
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    protected function makePlan(array $overrides = []): ProductionPlan
    {
        return ProductionPlan::create(array_merge([
            'line_master_id' => $this->line->id,
            'plan_date'      => '2026-06-26',
            'shift_name'     => 'Shift Malam',
            'press_name'     => '400T',
            'hari'           => 'jumat',
            'tgl'            => '26/06/2026',
            'jam'            => 'S2',
            'revisi'         => '0',
            'row_no'         => 1,
            'row_type'       => 'job',
            'job_no'         => 'RG-3111 FNS',
            'job_master'     => '53341-BZ130 (RG-3111) FNS',
            'plan'           => 800,
            'original_plan'  => 800,
            'ct_detik'       => 8,
            'dct'            => 15,
            'ok'             => 0,
            'status'         => 'pending',
            'source_type'    => 'ppc',
        ], $overrides));
    }

    protected function makeJobMasterForPlan(ProductionPlan $plan, array $overrides = []): JobMaster
    {
        return JobMaster::create(array_merge([
            'job_number'  => $plan->job_no . '-' . $plan->id,
            'job_name'    => $plan->job_master,
            'line'        => $plan->press_name,
            'target_qty'  => $plan->plan,
            'status'      => 'PENDING',
            'sequence_no' => $plan->row_no,
        ], $overrides));
    }

    public function test_unsplit_schedule_has_single_root_identity_and_target_qty(): void
    {
        $plan = $this->makePlan([
            'row_no'        => 1,
            'job_no'        => 'RG-3004 DRW',
            'job_master'    => '58307-BZ010 (RG-3004) DRW',
            'plan'          => 800,
            'original_plan' => 800,
            'start_time'    => '21:00',
            'finish_time'   => '23:58',
        ]);

        $job = $this->makeJobMasterForPlan($plan);

        $this->assertFalse($plan->isChildSlice());
        $this->assertSame($plan->id, $plan->resolveRootPlan()->id);
        $this->assertSame([$plan->id], $plan->getScheduleGroupPlanIds());
        $this->assertSame(800, $plan->effectiveTargetQty());
        $this->assertSame(800, ProductionMetricsService::effectivePlanQty($plan));
        $this->assertSame('23:58', $plan->effectiveFinishTime());
        $this->assertSame("RG-3004 DRW-{$plan->id}", $plan->canonicalJobNumber());

        $this->assertSame($plan->id, $job->resolveRootPlan()?->id);
        $this->assertSame($job->id, $job->resolveRootJobMaster()->id);
        $this->assertSame(800, $job->effectiveTargetQty());
    }

    public function test_single_break_split_schedule_resolves_to_one_root_identity_without_double_counting(): void
    {
        // Case: Schedule of 500 PCS split across 1 break into 300 PCS (parent) + 200 PCS (child)
        $parent = $this->makePlan([
            'row_no'        => 1,
            'job_no'        => 'RG-2765 PIER',
            'job_master'    => '53341-BZ010 (RG-2765) PIER',
            'plan'          => 300,
            'original_plan' => 500,
            'start_time'    => '11:00',
            'finish_time'   => '12:00',
            'shift_name'    => 'Shift Pagi',
            'parent_job_id' => null,
            'session_no'    => 1,
        ]);

        $child = $this->makePlan([
            'row_no'        => 3,
            'job_no'        => 'RG-2765 PIER',
            'job_master'    => '53341-BZ010 (RG-2765) PIER',
            'plan'          => 200,
            'original_plan' => 200,
            'start_time'    => '12:40',
            'finish_time'   => '13:30',
            'shift_name'    => 'Shift Pagi',
            'parent_job_id' => $parent->id,
            'session_no'    => 2,
        ]);

        $parentJob = $this->makeJobMasterForPlan($parent);
        $childJob = $this->makeJobMasterForPlan($child);

        $this->assertFalse($parent->isChildSlice());
        $this->assertTrue($child->isChildSlice());

        // Both slices resolve to the exact same root ProductionPlan
        $this->assertSame($parent->id, $parent->resolveRootPlan()->id);
        $this->assertSame($parent->id, $child->resolveRootPlan()->id);
        $this->assertSame([$parent->id, $child->id], $parent->getScheduleGroupPlanIds());
        $this->assertSame([$parent->id, $child->id], $child->getScheduleGroupPlanIds());

        // Target PPC is counted ONCE (500), never 300 and never 500 + 200 = 700
        $this->assertSame(500, $parent->effectiveTargetQty());
        $this->assertSame(500, $child->effectiveTargetQty());
        $this->assertSame('13:30', $parent->effectiveFinishTime());
        $this->assertSame($parent->canonicalJobNumber(), $child->canonicalJobNumber());

        // JobMaster resolution: child JobMaster resolves back to root JobMaster & 500 target
        $this->assertSame($parentJob->id, $childJob->resolveRootJobMaster()->id);
        $this->assertSame(500, $parentJob->effectiveTargetQty());
        $this->assertSame(500, $childJob->effectiveTargetQty());

        // ProductionService sibling resolution links parent & child JobMasters
        $service = app(ProductionService::class);
        $this->assertSame([$childJob->id], $service->getSiblingJobMasters($parentJob->id)->pluck('id')->all());
        $this->assertSame([$parentJob->id], $service->getSiblingJobMasters($childJob->id)->pluck('id')->all());
        $this->assertEqualsCanonicalizing(
            [$parentJob->id, $childJob->id],
            $service->getScheduleGroupJobMasterIds($childJob->id)
        );
    }

    public function test_multi_break_three_slice_schedule_resolves_chained_tree_and_summed_target(): void
    {
        // Case from 02 JULI 2026 Shift Pagi: RC-2764 FNS (500 PCS total across 3 slices: 95 + 335 + 70)
        // Chained parent_job_id: P -> C1 -> C2
        $slice1 = $this->makePlan([
            'plan_date'     => '2026-07-02',
            'shift_name'    => 'Shift Pagi',
            'press_name'    => '250T',
            'row_no'        => 3,
            'job_no'        => 'RC-2764 FNS',
            'job_master'    => '57627/28-BZ020 (RC-2764) FNS',
            'plan'          => 95,
            'original_plan' => 500,
            'start_time'    => '11:36',
            'finish_time'   => '12:00',
            'parent_job_id' => null,
            'session_no'    => 1,
        ]);

        $slice2 = $this->makePlan([
            'plan_date'     => '2026-07-02',
            'shift_name'    => 'Shift Pagi',
            'press_name'    => '250T',
            'row_no'        => 5,
            'job_no'        => 'RC-2764 FNS',
            'job_master'    => '57627/28-BZ020 (RC-2764) FNS',
            'plan'          => 335,
            'original_plan' => 405,
            'start_time'    => '12:40',
            'finish_time'   => '14:15',
            'parent_job_id' => $slice1->id,
            'session_no'    => 2,
        ]);

        $slice3 = $this->makePlan([
            'plan_date'     => '2026-07-02',
            'shift_name'    => 'Shift Pagi',
            'press_name'    => '250T',
            'row_no'        => 7,
            'job_no'        => 'RC-2764 FNS',
            'job_master'    => '57627/28-BZ020 (RC-2764) FNS',
            'plan'          => 70,
            'original_plan' => 70,
            'start_time'    => '14:30',
            'finish_time'   => '14:55',
            'parent_job_id' => $slice2->id,
            'session_no'    => 3,
        ]);

        $jm1 = $this->makeJobMasterForPlan($slice1);
        $jm2 = $this->makeJobMasterForPlan($slice2);
        $jm3 = $this->makeJobMasterForPlan($slice3);

        // Next distinct schedule after slice3
        $nextSchedule = $this->makePlan([
            'plan_date'     => '2026-07-02',
            'shift_name'    => 'Shift Pagi',
            'press_name'    => '250T',
            'row_no'        => 8,
            'job_no'        => 'RC-2765 PIER',
            'job_master'    => '57627/28-BZ020 (RC-2765) PIER',
            'plan'          => 500,
            'original_plan' => 500,
            'start_time'    => '14:55',
            'finish_time'   => '16:30',
            'parent_job_id' => null,
        ]);
        $jmNext = $this->makeJobMasterForPlan($nextSchedule);

        $this->assertSame($slice1->id, $slice3->resolveRootPlan()->id);
        $this->assertSame([$slice1->id, $slice2->id, $slice3->id], $slice1->getScheduleGroupPlanIds());
        $this->assertSame([$slice1->id, $slice2->id, $slice3->id], $slice3->getScheduleGroupPlanIds());

        $this->assertSame(500, $slice1->effectiveTargetQty());
        $this->assertSame(500, $slice2->effectiveTargetQty());
        $this->assertSame(500, $slice3->effectiveTargetQty());
        $this->assertSame('14:55', $slice1->effectiveFinishTime());

        $service = app(ProductionService::class);
        $this->assertEqualsCanonicalizing(
            [$jm1->id, $jm2->id, $jm3->id],
            $service->getScheduleGroupJobMasterIds($jm3->id)
        );
        $this->assertSame($jm1->id, $service->resolveRootJobMaster($jm3)->id);

        // Next job from slice1 skips slice2 and slice3 and lands on RC-2765 PIER
        $resolvedNext = $service->getNextJob($jm1->id);
        $this->assertNotNull($resolvedNext);
        $this->assertSame($jmNext->id, $resolvedNext->id);
    }

    public function test_dandori_only_pre_break_slice_with_zero_plan_resolves_full_post_break_target(): void
    {
        // Case from 26 JUNI 2026 Shift Malam: RG-3111 FNS
        // Pre-break 23:58-00:00 has Dandori 2 min & plan = 0; Post-break 00:45-02:35 has plan = 800
        $preBreak = $this->makePlan([
            'row_no'        => 2,
            'job_no'        => 'RG-3111 FNS',
            'job_master'    => '53341-BZ130 (RG-3111) FNS',
            'plan'          => 0,
            'original_plan' => 0,
            'start_time'    => '23:58',
            'finish_time'   => '00:00',
            'parent_job_id' => null,
            'session_no'    => 1,
        ]);

        $postBreak = $this->makePlan([
            'row_no'        => 4,
            'job_no'        => 'RG-3111 FNS',
            'job_master'    => '53341-BZ130 (RG-3111) FNS',
            'plan'          => 800,
            'original_plan' => 800,
            'start_time'    => '00:45',
            'finish_time'   => '02:35',
            'parent_job_id' => $preBreak->id,
            'session_no'    => 2,
        ]);

        $preJob = $this->makeJobMasterForPlan($preBreak);
        $postJob = $this->makeJobMasterForPlan($postBreak);

        // Effective target must be 800 (never 0!)
        $this->assertSame(800, $preBreak->effectiveTargetQty());
        $this->assertSame(800, $postBreak->effectiveTargetQty());
        $this->assertSame(800, $preJob->effectiveTargetQty());
        $this->assertSame(800, $postJob->effectiveTargetQty());
        $this->assertSame('02:35', $preBreak->effectiveFinishTime());
    }

    public function test_separate_schedules_with_same_part_number_remain_distinct_identities(): void
    {
        // Mandatory rule: Separate schedules with the same job_no / part_number must NEVER be merged!
        $scheduleA = $this->makePlan([
            'row_no'        => 1,
            'job_no'        => 'RG-3111 FNS',
            'job_master'    => '53341-BZ130 (RG-3111) FNS',
            'plan'          => 800,
            'original_plan' => 800,
            'start_time'    => '21:00',
            'finish_time'   => '23:00',
            'parent_job_id' => null,
            'split_group'   => null,
        ]);

        $scheduleB = $this->makePlan([
            'row_no'        => 6,
            'job_no'        => 'RG-3111 FNS',
            'job_master'    => '53341-BZ130 (RG-3111) FNS',
            'plan'          => 600,
            'original_plan' => 600,
            'start_time'    => '03:00',
            'finish_time'   => '04:30',
            'parent_job_id' => null,
            'split_group'   => null,
        ]);

        $jobA = $this->makeJobMasterForPlan($scheduleA);
        $jobB = $this->makeJobMasterForPlan($scheduleB);

        // Both are root schedules with distinct identities
        $this->assertSame($scheduleA->id, $scheduleA->resolveRootPlan()->id);
        $this->assertSame($scheduleB->id, $scheduleB->resolveRootPlan()->id);
        $this->assertNotSame($scheduleA->id, $scheduleB->id);

        // Group plans must NOT bleed into each other
        $this->assertSame([$scheduleA->id], $scheduleA->getScheduleGroupPlanIds());
        $this->assertSame([$scheduleB->id], $scheduleB->getScheduleGroupPlanIds());

        // Targets remain 800 and 600 separately (never summed to 1400)
        $this->assertSame(800, $scheduleA->effectiveTargetQty());
        $this->assertSame(600, $scheduleB->effectiveTargetQty());
        $this->assertSame(800, $jobA->effectiveTargetQty());
        $this->assertSame(600, $jobB->effectiveTargetQty());

        // Sibling JobMasters must be empty for both
        $service = app(ProductionService::class);
        $this->assertCount(0, $service->getSiblingJobMasters($jobA->id));
        $this->assertCount(0, $service->getSiblingJobMasters($jobB->id));
        $this->assertSame([$jobA->id], $service->getScheduleGroupJobMasterIds($jobA->id));
        $this->assertSame([$jobB->id], $service->getScheduleGroupJobMasterIds($jobB->id));
    }

    public function test_production_metrics_service_shift_normalization_and_work_date_boundaries(): void
    {
        // Shift Pagi: 07:30 - 21:00
        $this->assertSame('2026-06-26', ProductionMetricsService::currentProductionDate(Carbon::parse('2026-06-26 07:30:00')));
        $this->assertSame('Shift Pagi', ProductionMetricsService::currentProductionShift(Carbon::parse('2026-06-26 07:30:00')));

        // Shift Malam before midnight: 21:00 -> work_date = 2026-06-26
        $this->assertSame('2026-06-26', ProductionMetricsService::currentProductionDate(Carbon::parse('2026-06-26 21:15:00')));
        $this->assertSame('Shift Malam', ProductionMetricsService::currentProductionShift(Carbon::parse('2026-06-26 21:15:00')));

        // Shift Malam after midnight: 00:05 and 07:29 -> work_date = 2026-06-26 (yesterday's calendar date)
        $this->assertSame('2026-06-26', ProductionMetricsService::currentProductionDate(Carbon::parse('2026-06-27 00:05:00')));
        $this->assertSame('Shift Malam', ProductionMetricsService::currentProductionShift(Carbon::parse('2026-06-27 00:05:00')));
        $this->assertSame('2026-06-26', ProductionMetricsService::currentProductionDate(Carbon::parse('2026-06-27 07:29:00')));

        // Normalize shift representations
        $this->assertSame('Shift Pagi', ProductionMetricsService::normalizeShiftName(1));
        $this->assertSame('Shift Pagi', ProductionMetricsService::normalizeShiftName('Shift 1'));
        $this->assertSame('Shift Malam', ProductionMetricsService::normalizeShiftName(2));
        $this->assertSame('Shift Malam', ProductionMetricsService::normalizeShiftName('Shift 2'));
        $this->assertSame('Shift Malam', ProductionMetricsService::normalizeShiftName('Shift Malam'));
    }
}
