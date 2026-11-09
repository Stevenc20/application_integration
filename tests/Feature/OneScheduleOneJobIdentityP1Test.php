<?php

namespace Tests\Feature;

use App\Models\DailyProduction;
use App\Models\JobMaster;
use App\Models\LineMaster;
use App\Models\MasterBreakTime;
use App\Models\ProductionPlan;
use App\Models\RecoveryItem;
use App\Services\CutOffService;
use App\Services\PpcScheduleMergeService;
use App\Services\TimelineGenerationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OneScheduleOneJobIdentityP1Test extends TestCase
{
    use RefreshDatabase;

    protected LineMaster $pressA;
    protected LineMaster $press250T;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pressA = LineMaster::firstOrCreate(
            ['line_code' => 'PRESS_A'],
            ['line_name' => 'PRESS A', 'kapasitas' => 400, 'type_line' => 'stamping', 'status' => 'active']
        );

        $this->press250T = LineMaster::firstOrCreate(
            ['line_code' => '250T'],
            ['line_name' => '250T', 'kapasitas' => 250, 'type_line' => 'stamping', 'status' => 'active']
        );
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_ppc_merge_import_links_single_and_multi_break_splits_to_root_identity_and_preserves_independent_schedules(): void
    {
        $mergeService = app(PpcScheduleMergeService::class);

        $sheets = [
            [
                'shift_name' => 'Shift Pagi (Rev)',
                'press_name' => 'PRESS A',
                'hari'       => 'Jumat Pagi',
                'tgl'        => '26-Juni-2026',
                'jam'        => '07:30 - 16:15 WIB',
                'revisi'     => '1',
                'rows'       => [
                    // 1. Schedule split across 1 break: K4045 (60 before break + 440 after break = 500)
                    [
                        'row_no'       => 1,
                        'row_type'     => 'job',
                        'job_master'   => 'K4045/46',
                        'job_no'       => 'K4045',
                        'plan'         => 60,
                        'ct_detik'     => 6.5,
                        'process_time' => 6.5,
                        'dct'          => 8,
                        'tpt'          => 15,
                        'start_time'   => '11:30',
                        'finish_time'  => '11:45',
                    ],
                    [
                        'row_no'       => 2,
                        'row_type'     => 'break',
                        'job_master'   => '',
                        'job_no'       => 'ISTIRAHAT JUMAT',
                        'plan'         => 0,
                        'dct'          => 60,
                        'tpt'          => 60,
                        'start_time'   => '11:45',
                        'finish_time'  => '12:45',
                    ],
                    [
                        'row_no'       => 3,
                        'row_type'     => 'job',
                        'job_master'   => 'K4045/46',
                        'job_no'       => 'K4045',
                        'plan'         => 440,
                        'ct_detik'     => 6.5,
                        'process_time' => 48,
                        'dct'          => 0,
                        'tpt'          => 48,
                        'start_time'   => '12:45',
                        'finish_time'  => '13:33',
                    ],
                    // 2. Schedule split across 2 breaks (3 slices): RC-2764 FNS (95 + 335 + 70 = 500)
                    [
                        'row_no'       => 4,
                        'row_type'     => 'job',
                        'job_master'   => '57627/28-BZ020 (RC-2764) FNS',
                        'job_no'       => 'RC-2764 FNS',
                        'plan'         => 95,
                        'ct_detik'     => 7.0,
                        'process_time' => 11,
                        'dct'          => 10,
                        'tpt'          => 21,
                        'start_time'   => '13:33',
                        'finish_time'  => '15:15',
                    ],
                    [
                        'row_no'       => 5,
                        'row_type'     => 'break',
                        'job_master'   => '',
                        'job_no'       => 'CINGKORAK',
                        'plan'         => 0,
                        'dct'          => 15,
                        'tpt'          => 15,
                        'start_time'   => '15:15',
                        'finish_time'  => '15:30',
                    ],
                    [
                        'row_no'       => 6,
                        'row_type'     => 'job',
                        'job_master'   => '57627/28-BZ020 (RC-2764) FNS',
                        'job_no'       => 'RC-2764 FNS',
                        'plan'         => 335,
                        'ct_detik'     => 7.0,
                        'process_time' => 39,
                        'dct'          => 0,
                        'tpt'          => 39,
                        'start_time'   => '15:30',
                        'finish_time'  => '16:30',
                    ],
                    [
                        'row_no'       => 7,
                        'row_type'     => 'break',
                        'job_master'   => '',
                        'job_no'       => 'BREAKTIME',
                        'plan'         => 0,
                        'dct'          => 15,
                        'tpt'          => 15,
                        'start_time'   => '16:30',
                        'finish_time'  => '16:45',
                    ],
                    [
                        'row_no'       => 8,
                        'row_type'     => 'job',
                        'job_master'   => '57627/28-BZ020 (RC-2764) FNS',
                        'job_no'       => 'RC-2764 FNS',
                        'plan'         => 70,
                        'ct_detik'     => 7.0,
                        'process_time' => 8,
                        'dct'          => 0,
                        'tpt'          => 8,
                        'start_time'   => '16:45',
                        'finish_time'  => '16:53',
                    ],
                    // 3. Separate schedule with SAME job_no (K4045) later in the shift (has its own DCT=8)
                    [
                        'row_no'       => 9,
                        'row_type'     => 'job',
                        'job_master'   => 'K4045/46',
                        'job_no'       => 'K4045',
                        'plan'         => 300,
                        'ct_detik'     => 6.5,
                        'process_time' => 32.5,
                        'dct'          => 8,
                        'tpt'          => 41,
                        'start_time'   => '16:53',
                        'finish_time'  => '17:34',
                    ],
                ],
            ],
        ];

        $mergeService->merge($sheets, '2026-06-26');

        $jobPlans = ProductionPlan::whereDate('plan_date', '2026-06-26')
            ->where('row_type', 'job')
            ->orderBy('row_no')
            ->get();

        $this->assertCount(6, $jobPlans);

        // Root schedules: only 3 distinct job identities (K4045 #1, RC-2764 FNS, K4045 #2)
        $rootPlans = $jobPlans->filter(fn (ProductionPlan $p) => !$p->isChildSlice())->values();
        $this->assertCount(3, $rootPlans);

        // Verify K4045 #1 (rows 1 & 3)
        $k4045First = $rootPlans[0];
        $this->assertSame('K4045', $k4045First->job_no);
        $this->assertSame(500, $k4045First->effectiveTargetQty($jobPlans));
        $this->assertSame('13:33', $k4045First->effectiveFinishTime($jobPlans));
        $this->assertCount(2, $k4045First->getScheduleGroupPlans($jobPlans));

        // Verify RC-2764 FNS (rows 4, 6, 8)
        $rc2764 = $rootPlans[1];
        $this->assertSame('RC-2764 FNS', $rc2764->job_no);
        $this->assertSame(500, $rc2764->effectiveTargetQty($jobPlans));
        $this->assertSame('16:53', $rc2764->effectiveFinishTime($jobPlans));
        $this->assertCount(3, $rc2764->getScheduleGroupPlans($jobPlans));

        // Verify K4045 #2 (row 9) is an independent schedule identity!
        $k4045Second = $rootPlans[2];
        $this->assertSame('K4045', $k4045Second->job_no);
        $this->assertNull($k4045Second->parent_job_id);
        $this->assertNotSame($k4045First->id, $k4045Second->id);
        $this->assertSame(300, $k4045Second->effectiveTargetQty($jobPlans));
        $this->assertCount(1, $k4045Second->getScheduleGroupPlans($jobPlans));

        // Scenario H: Re-importing the exact same Excel sheet + non-Rev sheet in same workbook
        // must NOT create duplicate rows and must preserve parent-child links!
        $sheetsWithBaseAndRev = [
            $sheets[0],
            array_merge($sheets[0], ['shift_name' => 'Shift Pagi']),
        ];
        $reimportResult = $mergeService->merge($sheetsWithBaseAndRev, '2026-06-26');
        $this->assertSame(0, $reimportResult['counts']['added']);

        $jobPlansAfterReimport = ProductionPlan::whereDate('plan_date', '2026-06-26')
            ->where('row_type', 'job')
            ->orderBy('row_no')
            ->get();
        $this->assertCount(6, $jobPlansAfterReimport);
        $this->assertCount(3, $jobPlansAfterReimport->filter(fn (ProductionPlan $p) => !$p->isChildSlice()));
    }

    public function test_ppc_merge_import_links_dandori_only_pre_break_slice_on_night_shift(): void
    {
        // Case D & F: RG-3111 FNS on Night Shift (23:58-00:00 plan=0 before break, 00:45-02:35 plan=800 after break)
        $mergeService = app(PpcScheduleMergeService::class);

        $sheets = [
            [
                'shift_name' => 'Shift Malam',
                'press_name' => 'PRESS A',
                'hari'       => 'Jumat Malam',
                'tgl'        => '26-Juni-2026',
                'jam'        => '21:00 - 07:30 WIB',
                'rows'       => [
                    [
                        'row_no'       => 1,
                        'row_type'     => 'job',
                        'job_master'   => '53341-BZ130 (RG-3111) FNS',
                        'job_no'       => 'RG-3111 FNS',
                        'plan'         => 0,
                        'ct_detik'     => 8,
                        'process_time' => 0,
                        'dct'          => 2,
                        'tpt'          => 2,
                        'start_time'   => '23:58',
                        'finish_time'  => '00:00',
                    ],
                    [
                        'row_no'       => 2,
                        'row_type'     => 'break',
                        'job_master'   => '',
                        'job_no'       => 'ISTIRAHAT MALAM',
                        'plan'         => 0,
                        'dct'          => 45,
                        'tpt'          => 45,
                        'start_time'   => '00:00',
                        'finish_time'  => '00:45',
                    ],
                    [
                        'row_no'       => 3,
                        'row_type'     => 'job',
                        'job_master'   => '53341-BZ130 (RG-3111) FNS',
                        'job_no'       => 'RG-3111 FNS',
                        'plan'         => 800,
                        'ct_detik'     => 8,
                        'process_time' => 107,
                        'dct'          => 3,
                        'tpt'          => 110,
                        'start_time'   => '00:45',
                        'finish_time'  => '02:35',
                    ],
                ],
            ],
        ];

        $mergeService->merge($sheets, '2026-06-26');

        $jobPlans = ProductionPlan::whereDate('plan_date', '2026-06-26')
            ->where('row_type', 'job')
            ->orderBy('row_no')
            ->get();

        $this->assertCount(2, $jobPlans);
        $preSlice = $jobPlans[0];
        $postSlice = $jobPlans[1];

        $this->assertFalse($preSlice->isChildSlice());
        $this->assertTrue($postSlice->isChildSlice());
        $this->assertSame($preSlice->id, $postSlice->parent_job_id);
        $this->assertSame(800, $preSlice->effectiveTargetQty($jobPlans));
        $this->assertSame(800, $postSlice->effectiveTargetQty($jobPlans));
        $this->assertSame('02:35', $preSlice->effectiveFinishTime($jobPlans));
    }

    public function test_timeline_generation_multi_break_split_preserves_original_plan_and_links_all_slices_to_root(): void
    {
        // Configure two breaks on Shift Pagi: 12:00-12:45 and 15:15-15:30
        MasterBreakTime::create([
            'hari'          => 'semua',
            'shift'         => 'Shift Pagi',
            'waktu_mulai'   => '12:00',
            'waktu_selesai' => '12:45',
            'type'          => 'istirahat',
            'label'         => 'ISTIRAHAT SIANG',
            'is_active'     => true,
            'sort_order'    => 1,
        ]);
        MasterBreakTime::create([
            'hari'          => 'semua',
            'shift'         => 'Shift Pagi',
            'waktu_mulai'   => '15:15',
            'waktu_selesai' => '15:30',
            'type'          => 'cinkorak',
            'label'         => 'CINGKORAK',
            'is_active'     => true,
            'sort_order'    => 2,
        ]);

        // Job 1: 07:30 - 11:30 (240 mins: plan=1800, ct=8s => 240 mins, dct=0)
        $job1 = ProductionPlan::create([
            'line_master_id' => $this->pressA->id,
            'plan_date'      => '2026-07-02',
            'shift_name'     => 'Shift Pagi',
            'press_name'     => 'PRESS A',
            'hari'           => 'kamis',
            'row_no'         => 1,
            'row_type'       => 'job',
            'job_no'         => 'JOB-1',
            'job_master'     => 'JOB 1 MASTER',
            'plan'           => 1800,
            'ct_detik'       => 8,
            'dct'            => 0,
            'total_mesin'    => 1,
            'source_type'    => 'ppc',
        ]);

        // Job 2: starts at 11:30, needs 240 mins process time (plan=1800, ct=8s, dct=0)
        // Will cross BOTH 12:00-12:45 AND 15:15-15:30 -> split into 3 slices!
        $job2 = ProductionPlan::create([
            'line_master_id' => $this->pressA->id,
            'plan_date'      => '2026-07-02',
            'shift_name'     => 'Shift Pagi',
            'press_name'     => 'PRESS A',
            'hari'           => 'kamis',
            'row_no'         => 2,
            'row_type'       => 'job',
            'job_no'         => 'JOB-2-MULTI',
            'job_master'     => 'JOB 2 MULTI BREAK',
            'plan'           => 1800,
            'ct_detik'       => 8,
            'dct'            => 0,
            'total_mesin'    => 1,
            'source_type'    => 'ppc',
        ]);

        $timelineService = app(TimelineGenerationService::class);
        $timelineService->regenerateSection('2026-07-02', 'Shift Pagi', 'PRESS A');

        $job2Slices = ProductionPlan::whereDate('plan_date', '2026-07-02')
            ->where('job_no', 'JOB-2-MULTI')
            ->orderBy('row_no')
            ->get();

        $this->assertCount(3, $job2Slices);

        $rootSlice = $job2Slices[0];
        $sliceB = $job2Slices[1];
        $sliceC = $job2Slices[2];

        // Root slice keeps original_plan = 1800 (never overwritten by second break split!)
        $this->assertSame($job2->id, $rootSlice->id);
        $this->assertNull($rootSlice->parent_job_id);
        $this->assertEquals(1800, (int) $rootSlice->original_plan);
        $this->assertSame(1, (int) $rootSlice->session_no);

        // Both child slices point directly to rootSlice->id and have sequential session_no (2, 3)
        $this->assertSame($rootSlice->id, (int) $sliceB->parent_job_id);
        $this->assertSame($rootSlice->id, (int) $sliceC->parent_job_id);
        $this->assertSame(2, (int) $sliceB->session_no);
        $this->assertSame(3, (int) $sliceC->session_no);

        // Sum of slice plans equals 1800 and effectiveTargetQty is 1800
        $this->assertSame(1800, (int) $job2Slices->sum('plan'));
        $this->assertSame(1800, $rootSlice->effectiveTargetQty($job2Slices));
        $this->assertSame(1800, $sliceC->effectiveTargetQty($job2Slices));
    }

    public function test_cutoff_service_evaluates_per_root_schedule_identity_without_creating_duplicate_recovery_items(): void
    {
        // Case: Break-split schedule of 500 PCS (parent plan=300, child plan=200).
        // Scenario 1: Operator completed all 500 PCS (recorded on parent or across parent+child).
        // CutOffService must recognize 500/500 is complete and create 0 RecoveryItems!
        $completedRoot = ProductionPlan::create([
            'line_master_id' => $this->pressA->id,
            'plan_date'      => '2026-06-26',
            'shift_name'     => 'Shift Pagi',
            'press_name'     => 'PRESS A',
            'row_no'         => 1,
            'row_type'       => 'job',
            'job_no'         => 'JOB-DONE-500',
            'job_master'     => 'JOB DONE 500',
            'plan'           => 300,
            'original_plan'  => 500,
            'ok'             => 500, // Full 500 recorded on root plan
            'parent_job_id'  => null,
            'source_type'    => 'ppc',
        ]);
        ProductionPlan::create([
            'line_master_id' => $this->pressA->id,
            'plan_date'      => '2026-06-26',
            'shift_name'     => 'Shift Pagi',
            'press_name'     => 'PRESS A',
            'row_no'         => 3,
            'row_type'       => 'job',
            'job_no'         => 'JOB-DONE-500',
            'job_master'     => 'JOB DONE 500',
            'plan'           => 200,
            'original_plan'  => 200,
            'ok'             => 0, // Child slice has ok=0
            'parent_job_id'  => $completedRoot->id,
            'source_type'    => 'ppc',
        ]);

        // Scenario 2: Break-split schedule of 800 PCS (parent plan=300, child plan=500)
        // where operator achieved 300 before break + 150 after break = 450 total (deficit = 350).
        // CutOffService must create EXACTLY 1 RecoveryItem (for root) with plan_qty=800, actual_qty=450, recovery_qty=350!
        $partialRoot = ProductionPlan::create([
            'line_master_id' => $this->pressA->id,
            'plan_date'      => '2026-06-26',
            'shift_name'     => 'Shift Pagi',
            'press_name'     => 'PRESS A',
            'row_no'         => 4,
            'row_type'       => 'job',
            'job_no'         => 'JOB-PARTIAL-800',
            'job_master'     => 'JOB PARTIAL 800',
            'plan'           => 300,
            'original_plan'  => 800,
            'ok'             => 300,
            'parent_job_id'  => null,
            'source_type'    => 'ppc',
        ]);
        ProductionPlan::create([
            'line_master_id' => $this->pressA->id,
            'plan_date'      => '2026-06-26',
            'shift_name'     => 'Shift Pagi',
            'press_name'     => 'PRESS A',
            'row_no'         => 6,
            'row_type'       => 'job',
            'job_no'         => 'JOB-PARTIAL-800',
            'job_master'     => 'JOB PARTIAL 800',
            'plan'           => 500,
            'original_plan'  => 500,
            'ok'             => 150,
            'parent_job_id'  => $partialRoot->id,
            'source_type'    => 'ppc',
        ]);

        $cutOffService = app(CutOffService::class);
        $stats = $cutOffService->processCutOff('2026-06-26', 'Shift Pagi', 'PRESS A');

        $this->assertSame(1, $stats['total_unfinished']);
        $this->assertSame(1, $stats['created']);

        $recoveryItems = RecoveryItem::all();
        $this->assertCount(1, $recoveryItems);

        $rec = $recoveryItems->first();
        $this->assertSame($partialRoot->id, (int) $rec->production_plan_id);
        $this->assertSame('JOB-PARTIAL-800', $rec->job_no);
        $this->assertEquals(800, (float) $rec->plan_qty);
        $this->assertEquals(450, (float) $rec->actual_qty);
        $this->assertEquals(350, (float) $rec->recovery_qty);
    }
}
