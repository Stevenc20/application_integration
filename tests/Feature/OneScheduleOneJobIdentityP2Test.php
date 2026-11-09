<?php

namespace Tests\Feature;

use App\Http\Controllers\Operational\InputHarianController;
use App\Http\Controllers\SignatureController;
use App\Http\Controllers\Supervisor\ReportController;
use App\Models\DailyProduction;
use App\Models\JobMaster;
use App\Models\LineAssignment;
use App\Models\LineMaster;
use App\Models\ProductionLog;
use App\Models\ProductionPlan;
use App\Models\ProductionSession;
use App\Models\Signature;
use App\Models\User;
use App\Services\ProductionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class OneScheduleOneJobIdentityP2Test extends TestCase
{
    use RefreshDatabase;

    protected LineMaster $pressA;
    protected User $leaderUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pressA = LineMaster::firstOrCreate(
            ['line_code' => 'PRESS_A'],
            ['line_name' => 'Line A', 'kapasitas' => 400, 'type_line' => 'stamping', 'status' => 'active']
        );

        $this->leaderUser = User::factory()->create([
            'name' => 'Leader Malam Test',
            'role' => 'leader a',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * Skenario 1 & 2:
     * - Shift Pagi: Schedule normal dan schedule terpotong satu/beberapa kali istirahat (95 + 335 + 70 = 500).
     * - Memastikan Input Harian hanya membuat & menampilkan 1 identitas JobMaster induk dengan target 500,
     *   dan LKH menampilkan tepat 1 baris untuk schedule split tersebut dengan plan_qty = 500 serta akumulasi
     *   actual OK, Repair, Reject, Start, Finish, dan runtime tepat 1 kali.
     */
    public function test_shift_pagi_normal_and_multi_break_split_schedule_in_input_harian_and_lkh(): void
    {
        $workDate = '2026-07-02';
        Carbon::setTestNow(Carbon::parse('2026-07-02 10:00:00'));
        Auth::login($this->leaderUser);

        // 1. Schedule Normal (1 baris utuh: 300 pcs)
        $normalPlan = ProductionPlan::create([
            'line_master_id' => $this->pressA->id,
            'press_name'    => 'Line A',
            'plan_date'     => $workDate,
            'shift_name'    => 'Shift Pagi',
            'row_no'        => 1,
            'row_type'      => 'job',
            'job_no'        => 'PVS-005B',
            'job_master'    => 'PVS-005/7B',
            'plan'          => 300,
            'original_plan' => 300,
            'ct_detik'      => 6.0,
            'start_time'    => '08:00',
            'finish_time'   => '09:00',
            'status'        => 'pending',
        ]);

        // 2. Schedule Terpotong 2 Kali Istirahat (3 potongan: 95 + 335 + 70 = 500)
        $rootSplit = ProductionPlan::create([
            'line_master_id' => $this->pressA->id,
            'press_name'    => 'Line A',
            'plan_date'     => $workDate,
            'shift_name'    => 'Shift Pagi',
            'row_no'        => 2,
            'row_type'      => 'job',
            'job_no'        => 'RC-2764 FNS',
            'job_master'    => '57417-BZ120-00',
            'plan'          => 95,
            'original_plan' => 500,
            'split_group'   => 'SPLIT-RC2764',
            'session_no'    => 1,
            'ct_detik'      => 7.0,
            'start_time'    => '14:57',
            'finish_time'   => '15:15',
            'status'        => 'pending',
        ]);

        ProductionPlan::create([
            'line_master_id' => $this->pressA->id,
            'press_name'  => 'Line A',
            'plan_date'   => $workDate,
            'shift_name'  => 'Shift Pagi',
            'row_no'      => 3,
            'row_type'    => 'break',
            'job_no'      => 'CINGKORAK',
            'job_master'  => 'CINGKORAK',
            'plan'        => 0,
            'start_time'  => '15:15',
            'finish_time' => '15:30',
        ]);

        $childSplit1 = ProductionPlan::create([
            'line_master_id' => $this->pressA->id,
            'press_name'    => 'Line A',
            'plan_date'     => $workDate,
            'shift_name'    => 'Shift Pagi',
            'row_no'        => 4,
            'row_type'      => 'job',
            'job_no'        => 'RC-2764 FNS',
            'job_master'    => '57417-BZ120-00',
            'plan'          => 335,
            'original_plan' => 500,
            'parent_job_id' => $rootSplit->id,
            'split_group'   => 'SPLIT-RC2764',
            'session_no'    => 2,
            'ct_detik'      => 7.0,
            'start_time'    => '15:30',
            'finish_time'   => '16:15',
            'status'        => 'pending',
        ]);

        ProductionPlan::create([
            'line_master_id' => $this->pressA->id,
            'press_name'  => 'Line A',
            'plan_date'   => $workDate,
            'shift_name'  => 'Shift Pagi',
            'row_no'      => 5,
            'row_type'    => 'break',
            'job_no'      => 'BREAKTIME',
            'job_master'  => 'BREAKTIME',
            'plan'        => 0,
            'start_time'  => '16:15',
            'finish_time' => '16:30',
        ]);

        $childSplit2 = ProductionPlan::create([
            'line_master_id' => $this->pressA->id,
            'press_name'    => 'Line A',
            'plan_date'     => $workDate,
            'shift_name'    => 'Shift Pagi',
            'row_no'        => 6,
            'row_type'      => 'job',
            'job_no'        => 'RC-2764 FNS',
            'job_master'    => '57417-BZ120-00',
            'plan'          => 70,
            'original_plan' => 500,
            'parent_job_id' => $rootSplit->id,
            'split_group'   => 'SPLIT-RC2764',
            'session_no'    => 3,
            'ct_detik'      => 7.0,
            'start_time'    => '16:30',
            'finish_time'   => '16:40',
            'status'        => 'pending',
        ]);

        // Panggil InputHarianController::index()
        $ihReq = Request::create('/operational/input-harian', 'GET', [
            'date'  => $workDate,
            'shift' => 'Shift Pagi',
            'line'  => 'Line A',
        ]);
        $ihReq->setUserResolver(fn () => $this->leaderUser);

        $ihView = app(InputHarianController::class)->index($ihReq);
        $ihData = $ihView->getData();

        // Verifikasi: Hanya 2 JobMaster yang dibuat (untuk normalPlan & rootSplit, TIDAK untuk childSplit1/2)
        $allJms = JobMaster::all();
        $this->assertCount(2, $allJms);
        $rootJm = JobMaster::where('job_number', $rootSplit->canonicalJobNumber())->first();
        $this->assertNotNull($rootJm);
        $this->assertSame(500, (int) $rootJm->target_qty);
        $this->assertSame('16:40', Carbon::parse($rootJm->plan_end)->format('H:i'));

        // Verifikasi tampilan Input Harian ($jobs & $pendingJobs)
        $jobRows = collect($ihData['jobs'])->filter(fn ($p) => ($p->row_type ?? 'job') === 'job')->values();
        $this->assertCount(2, $jobRows);
        $splitRowInIh = $jobRows->firstWhere('id', $rootSplit->id);
        $this->assertSame(500, (int) $splitRowInIh->plan);
        $this->assertSame('16:40', substr((string) $splitRowInIh->finish_time, 0, 5));
        $this->assertCount(2, $ihData['pendingJobs']);

        // Simulasikan produksi 3 sesi pada identitas induk
        $prodService = app(ProductionService::class);
        Carbon::setTestNow(Carbon::parse('2026-07-02 14:57:00'));
        $prodService->startJob($rootJm->id, false, $workDate);
        $prodService->saveProductionLog($rootJm->id, ['ok_qty' => 95, 'repair_qty' => 1, 'reject_qty' => 0], $workDate);

        Carbon::setTestNow(Carbon::parse('2026-07-02 15:45:00'));
        $prodService->saveProductionLog($rootJm->id, ['ok_qty' => 335, 'repair_qty' => 2, 'reject_qty' => 1], $workDate);

        Carbon::setTestNow(Carbon::parse('2026-07-02 16:40:00'));
        $prodService->saveProductionLog($rootJm->id, ['ok_qty' => 70, 'repair_qty' => 0, 'reject_qty' => 1], $workDate);
        $prodService->finishJob($rootJm->id);

        // Verifikasi LKH (ReportController)
        $lkhReq = Request::create('/supervisor/reports/daily-production', 'GET', [
            'date'  => $workDate,
            'shift' => 'Shift Pagi',
            'line'  => 'Line A',
        ]);
        $lkhReq->setUserResolver(fn () => $this->leaderUser);

        $lkhView = app(ReportController::class)->dailyProduction($lkhReq);
        $lkhData = $lkhView->getData();
        $lkhJobs = collect($lkhData['jobsData'])->where('row_type', 'job')->values();

        $this->assertCount(2, $lkhJobs);
        $rcLkh = $lkhJobs->firstWhere('plan_id', $rootSplit->id);
        $this->assertNotNull($rcLkh);
        $this->assertSame(500, (int) $rcLkh['plan_qty']);
        $this->assertSame(500, (int) $rcLkh['actual_good']);
        $this->assertSame(3, (int) $rcLkh['actual_repair']);
        $this->assertSame(2, (int) $rcLkh['actual_reject']);
        $this->assertSame('14:57', $rcLkh['actual_start']->format('H:i'));
        $this->assertSame('16:40', $rcLkh['actual_finish']->format('H:i'));
        // Total plan_qty di LKH = 300 + 500 = 800 (tidak double-counting)
        $this->assertSame(800, (int) $lkhData['totals']['plan_qty']);
        $this->assertSame(500, (int) $lkhData['totals']['actual_good']);
    }

    /**
     * Skenario 3 & 4:
     * - Schedule Shift Malam dengan potongan pertama plan = 0 (Dandori-only pukul 23:58-00:00)
     *   dan potongan kedua pukul 00:45-02:35 (plan = 800).
     * - Produksi baru dimulai setelah tengah malam (pukul 00:50 kalender D+1).
     * - Memastikan target tetap 800 (bukan 0), work_date tetap D (Shift Malam asal),
     *   dan actual produksi muncul penuh di Input Harian maupun LKH Shift Malam tanggal D.
     */
    public function test_shift_malam_dandori_only_pre_break_and_post_midnight_production_stays_in_origin_shift(): void
    {
        $originWorkDate = '2026-07-02';
        // Jam kalender sudah masuk 2026-07-03 pukul 00:50 dini hari (Shift Malam tanggal produksi 2026-07-02)
        Carbon::setTestNow(Carbon::parse('2026-07-03 00:50:00'));
        Auth::login($this->leaderUser);

        $rootDandoriSlice = ProductionPlan::create([
            'line_master_id' => $this->pressA->id,
            'press_name'    => 'Line A',
            'plan_date'     => $originWorkDate,
            'shift_name'    => 'Shift Malam',
            'row_no'        => 1,
            'row_type'      => 'job',
            'job_no'        => 'RC-2764 FNS',
            'job_master'    => '57417-BZ120-00',
            'plan'          => 0,
            'original_plan' => 800,
            'split_group'   => 'SPLIT-MALAM-1',
            'session_no'    => 1,
            'ct_detik'      => 6.5,
            'dct'           => 8,
            'start_time'    => '23:58',
            'finish_time'   => '00:00',
            'status'        => 'pending',
        ]);

        ProductionPlan::create([
            'line_master_id' => $this->pressA->id,
            'press_name'  => 'Line A',
            'plan_date'   => $originWorkDate,
            'shift_name'  => 'Shift Malam',
            'row_no'      => 2,
            'row_type'    => 'break',
            'job_no'      => 'ISTIRAHAT MALAM',
            'job_master'  => 'ISTIRAHAT MALAM',
            'plan'        => 0,
            'start_time'  => '00:00',
            'finish_time' => '00:45',
        ]);

        $postBreakSlice = ProductionPlan::create([
            'line_master_id' => $this->pressA->id,
            'press_name'    => 'Line A',
            'plan_date'     => $originWorkDate,
            'shift_name'    => 'Shift Malam',
            'row_no'        => 3,
            'row_type'      => 'job',
            'job_no'        => 'RC-2764 FNS',
            'job_master'    => '57417-BZ120-00',
            'plan'          => 800,
            'original_plan' => 800,
            'parent_job_id' => $rootDandoriSlice->id,
            'split_group'   => 'SPLIT-MALAM-1',
            'session_no'    => 2,
            'ct_detik'      => 6.5,
            'dct'           => 0,
            'start_time'    => '00:45',
            'finish_time'   => '02:35',
            'status'        => 'pending',
        ]);

        // Buka Input Harian setelah tengah malam tanpa parameter date eksplisit
        $ihReq = Request::create('/operational/input-harian', 'GET', [
            'line' => 'Line A',
        ]);
        $ihReq->setUserResolver(fn () => $this->leaderUser);

        $ihView = app(InputHarianController::class)->index($ihReq);
        $ihData = $ihView->getData();

        // Work date wajib tetap 2026-07-02 dan Shift Malam
        $this->assertSame($originWorkDate, $ihData['date']);
        $this->assertSame('Shift Malam', $ihData['currentShift']);

        // JobMaster induk wajib memiliki target_qty = 800 (bukan 0!)
        $rootJm = JobMaster::where('job_number', $rootDandoriSlice->canonicalJobNumber())->first();
        $this->assertNotNull($rootJm);
        $this->assertSame(800, (int) $rootJm->target_qty);

        // Mulai produksi pada pukul 00:50 (setelah tengah malam) tanpa mem-passing $workDate eksplisit
        $prodService = app(ProductionService::class);
        $prodService->startJob($rootJm->id);
        $prodService->saveProductionLog($rootJm->id, [
            'ok_qty'     => 800,
            'repair_qty' => 4,
            'reject_qty' => 2,
        ]);

        Carbon::setTestNow(Carbon::parse('2026-07-03 02:30:00'));
        // Cek kembali Input Harian saat masih running pukul 02:30 dini hari: log setelah 00:00 harus tampil
        $ihViewRunning = app(InputHarianController::class)->index($ihReq);
        $ihDataRunning = $ihViewRunning->getData();
        $this->assertCount(1, $ihDataRunning['productionLogs']);
        $this->assertSame(800, (int) $ihDataRunning['activeJob']->target_qty);

        $prodService->finishJob($rootJm->id);

        // Pastikan ProductionSession dan DailyProduction tercatat pada work_date = 2026-07-02 (bukan 2026-07-03)
        $dp = DailyProduction::where('job_master_id', $rootJm->id)->first();
        $this->assertNotNull($dp);
        $this->assertSame($originWorkDate, Carbon::parse($dp->work_date)->toDateString());
        $this->assertSame(800, (int) $dp->actual_ok);
        $this->assertSame(4, (int) $dp->actual_repair);
        $this->assertSame(2, (int) $dp->actual_reject);

        // Cek LKH Shift Malam tanggal 2026-07-02
        $lkhReq = Request::create('/supervisor/reports/daily-production', 'GET', [
            'date'  => $originWorkDate,
            'shift' => 'Shift Malam',
            'line'  => 'Line A',
        ]);
        $lkhReq->setUserResolver(fn () => $this->leaderUser);

        $lkhData = app(ReportController::class)->dailyProduction($lkhReq)->getData();
        $lkhJobs = collect($lkhData['jobsData'])->where('row_type', 'job')->values();

        $this->assertCount(1, $lkhJobs);
        $this->assertSame(800, (int) $lkhJobs[0]['plan_qty']);
        $this->assertSame(800, (int) $lkhJobs[0]['actual_good']);
        $this->assertSame(4, (int) $lkhJobs[0]['actual_repair']);
        $this->assertSame(2, (int) $lkhJobs[0]['actual_reject']);
    }

    /**
     * Skenario 5:
     * - Dua schedule independen dengan job_no sama dalam satu shift (baik dipisahkan job lain maupun berurutan
     *   tanpa relasi parent_job_id / split_group).
     * - Memastikan keduanya TIDAK digabungkan di Input Harian maupun LKH.
     */
    public function test_two_independent_schedules_with_same_job_no_remain_separate_in_input_harian_and_lkh(): void
    {
        $workDate = '2026-07-02';
        Carbon::setTestNow(Carbon::parse('2026-07-02 09:00:00'));
        Auth::login($this->leaderUser);

        // Dua schedule independen berurutan dengan job_no sama tetapi parent_job_id = null dan split_group = null
        $sched1 = ProductionPlan::create([
            'line_master_id' => $this->pressA->id,
            'press_name'    => 'Line A',
            'plan_date'     => $workDate,
            'shift_name'    => 'Shift Pagi',
            'row_no'        => 1,
            'row_type'      => 'job',
            'job_no'        => 'GT-5154',
            'job_master'    => 'GT-5154/5156 FNS',
            'plan'          => 300,
            'original_plan' => 300,
            'dct'           => 10,
            'start_time'    => '08:00',
            'finish_time'   => '09:00',
            'status'        => 'pending',
        ]);

        $sched2 = ProductionPlan::create([
            'line_master_id' => $this->pressA->id,
            'press_name'    => 'Line A',
            'plan_date'     => $workDate,
            'shift_name'    => 'Shift Pagi',
            'row_no'        => 2,
            'row_type'      => 'job',
            'job_no'        => 'GT-5154',
            'job_master'    => 'GT-5154/5156 FNS',
            'plan'          => 450,
            'original_plan' => 450,
            'dct'           => 10,
            'start_time'    => '09:10',
            'finish_time'   => '10:30',
            'status'        => 'pending',
        ]);

        // Input Harian
        $ihReq = Request::create('/operational/input-harian', 'GET', [
            'date'  => $workDate,
            'shift' => 'Shift Pagi',
            'line'  => 'Line A',
        ]);
        $ihReq->setUserResolver(fn () => $this->leaderUser);
        $ihData = app(InputHarianController::class)->index($ihReq)->getData();

        $ihJobRows = collect($ihData['jobs'])->where('row_type', 'job')->values();
        $this->assertCount(2, $ihJobRows);
        $this->assertSame(300, (int) $ihJobRows[0]->plan);
        $this->assertSame(450, (int) $ihJobRows[1]->plan);

        // Catat produksi berbeda untuk masing-masing schedule
        $jm1 = JobMaster::where('job_number', $sched1->canonicalJobNumber())->firstOrFail();
        $jm2 = JobMaster::where('job_number', $sched2->canonicalJobNumber())->firstOrFail();

        $prodService = app(ProductionService::class);
        $prodService->startJob($jm1->id, false, $workDate);
        $prodService->saveProductionLog($jm1->id, ['ok_qty' => 300, 'repair_qty' => 0, 'reject_qty' => 0], $workDate);
        $prodService->finishJob($jm1->id);

        $prodService->startJob($jm2->id, false, $workDate);
        $prodService->saveProductionLog($jm2->id, ['ok_qty' => 420, 'repair_qty' => 5, 'reject_qty' => 0], $workDate);
        $prodService->finishJob($jm2->id);

        // LKH: Wajib tetap 2 baris terpisah!
        $lkhReq = Request::create('/supervisor/reports/daily-production', 'GET', [
            'date'  => $workDate,
            'shift' => 'Shift Pagi',
            'line'  => 'Line A',
        ]);
        $lkhReq->setUserResolver(fn () => $this->leaderUser);
        $lkhData = app(ReportController::class)->dailyProduction($lkhReq)->getData();
        $lkhJobs = collect($lkhData['jobsData'])->where('row_type', 'job')->values();

        $this->assertCount(2, $lkhJobs);
        $this->assertSame($sched1->id, $lkhJobs[0]['plan_id']);
        $this->assertSame(300, (int) $lkhJobs[0]['plan_qty']);
        $this->assertSame(300, (int) $lkhJobs[0]['actual_good']);

        $this->assertSame($sched2->id, $lkhJobs[1]['plan_id']);
        $this->assertSame(450, (int) $lkhJobs[1]['plan_qty']);
        $this->assertSame(420, (int) $lkhJobs[1]['actual_good']);
        $this->assertSame(5, (int) $lkhJobs[1]['actual_repair']);
    }

    /**
     * Skenario 6:
     * - Parent tanpa JobMaster tetapi child memiliki JobMaster dan actual produksi (kompatibilitas data historis).
     * - Memastikan Input Harian dan LKH tetap menampilkan actual OK, Repair, Reject, Start, Finish, dan runtime
     *   dari child pada baris induk tanpa kehilangan data.
     */
    public function test_parent_without_job_master_but_child_has_job_master_and_actuals_appears_in_input_harian_and_lkh(): void
    {
        $workDate = '2026-07-02';
        Carbon::setTestNow(Carbon::parse('2026-07-02 23:50:00'));
        Auth::login($this->leaderUser);

        $parentPlan = ProductionPlan::create([
            'line_master_id' => $this->pressA->id,
            'press_name'    => 'Line A',
            'plan_date'     => $workDate,
            'shift_name'    => 'Shift Malam',
            'row_no'        => 1,
            'row_type'      => 'job',
            'job_no'        => 'IF-013 BLK',
            'job_master'    => 'IF-013 BLK',
            'plan'          => 90,
            'original_plan' => 300,
            'split_group'   => 'SPLIT-IF013',
            'session_no'    => 1,
            'ct_detik'      => 6.0,
            'start_time'    => '23:46',
            'finish_time'   => '00:00',
            'status'        => 'completed',
        ]);

        $childPlan = ProductionPlan::create([
            'line_master_id' => $this->pressA->id,
            'press_name'    => 'Line A',
            'plan_date'     => $workDate,
            'shift_name'    => 'Shift Malam',
            'row_no'        => 2,
            'row_type'      => 'job',
            'job_no'        => 'IF-013 BLK',
            'job_master'    => 'IF-013 BLK',
            'plan'          => 210,
            'original_plan' => 300,
            'parent_job_id' => $parentPlan->id,
            'split_group'   => 'SPLIT-IF013',
            'session_no'    => 2,
            'ct_detik'      => 6.0,
            'start_time'    => '00:45',
            'finish_time'   => '01:06',
            'status'        => 'completed',
        ]);

        // Simulasikan kondisi historis: HANYA childPlan yang punya JobMaster & DailyProduction (parent tidak punya JobMaster)
        $childJm = JobMaster::create([
            'job_number'  => 'IF-013 BLK-'.$childPlan->id,
            'job_name'    => 'IF-013 BLK',
            'line'        => 'Line A',
            'target_qty'  => 210,
            'sequence_no' => 2,
            'status'      => 'complete',
            'started_at'  => Carbon::parse('2026-07-03 00:45:00'),
            'finished_at' => Carbon::parse('2026-07-03 01:06:00'),
        ]);

        DailyProduction::create([
            'job_master_id'   => $childJm->id,
            'work_date'       => $workDate,
            'line'            => 'Line A',
            'shift'           => 'Shift Malam',
            'target_qty'      => 210,
            'actual_ok'       => 295,
            'actual_qty'      => 295,
            'actual_repair'   => 3,
            'actual_reject'   => 2,
            'runtime_seconds' => 1260,
        ]);

        // Pastikan sebelum panggil LKH, parent memang tidak punya JobMaster
        $this->assertNull(JobMaster::where('job_number', 'IF-013 BLK-'.$parentPlan->id)->first());

        // 1. Cek LKH langsung (tanpa sync Input Harian terlebih dahulu, sehingga parent benar-benar tanpa JobMaster di DB)
        $lkhReq = Request::create('/supervisor/reports/daily-production', 'GET', [
            'date'  => $workDate,
            'shift' => 'Shift Malam',
            'line'  => 'Line A',
        ]);
        $lkhReq->setUserResolver(fn () => $this->leaderUser);

        $lkhData = app(ReportController::class)->dailyProduction($lkhReq)->getData();
        $lkhJobs = collect($lkhData['jobsData'])->where('row_type', 'job')->values();

        $this->assertCount(1, $lkhJobs);
        $this->assertSame(300, (int) $lkhJobs[0]['plan_qty']);
        $this->assertSame(295, (int) $lkhJobs[0]['actual_good']);
        $this->assertSame(3, (int) $lkhJobs[0]['actual_repair']);
        $this->assertSame(2, (int) $lkhJobs[0]['actual_reject']);
        $this->assertSame('00:45', $lkhJobs[0]['actual_start']->format('H:i'));
        $this->assertSame('01:06', $lkhJobs[0]['actual_finish']->format('H:i'));

        // 2. Cek Input Harian (yang akan men-sync parent JobMaster dan menggabungkan data childJm ke parentJm)
        $ihReq = Request::create('/operational/input-harian', 'GET', [
            'date'  => $workDate,
            'shift' => 'Shift Malam',
            'line'  => 'Line A',
        ]);
        $ihReq->setUserResolver(fn () => $this->leaderUser);
        $ihData = app(InputHarianController::class)->index($ihReq)->getData();
        $ihJobs = collect($ihData['jobs'])->where('row_type', 'job')->values();

        $this->assertCount(1, $ihJobs);
        $this->assertSame(300, (int) $ihJobs[0]->plan);
        $this->assertNotNull($ihJobs[0]->job_data);
        $this->assertNotNull($ihJobs[0]->job_data->dailyProduction);
        $this->assertSame(295, (int) $ihJobs[0]->job_data->dailyProduction->actual_ok);
        $this->assertSame(3, (int) $ihJobs[0]->job_data->dailyProduction->actual_repair);
        $this->assertSame(2, (int) $ihJobs[0]->job_data->dailyProduction->actual_reject);
    }

    /**
     * Skenario 7:
     * - LKH dan tanda tangan Shift Malam setelah pukul 07:30 pagi (ketika currentProductionDate() sudah berganti ke H+1).
     * - Memastikan SignatureController::pending() tetap mendeteksi LKH Shift Malam H-1 yang belum ditandatangani,
     *   dan tanda tangan tetap tersimpan pada (work_date = H-1, shift_name = Shift Malam), TIDAK masuk ke Shift Pagi.
     */
    public function test_lkh_and_signature_after_0730_stays_bound_to_night_shift_origin(): void
    {
        $nightWorkDate = '2026-07-02';
        $morningDate   = '2026-07-03';

        // Buat jadwal Shift Malam pada 2026-07-02 dan Shift Pagi pada 2026-07-03
        ProductionPlan::create([
            'line_master_id' => $this->pressA->id,
            'press_name'    => 'Line A',
            'plan_date'     => $nightWorkDate,
            'shift_name'    => 'Shift Malam',
            'row_no'        => 1,
            'row_type'      => 'job',
            'job_no'        => 'PB-016N FNS',
            'job_master'    => 'PB-016N/26',
            'plan'          => 180,
            'original_plan' => 180,
            'start_time'    => '23:36',
            'finish_time'   => '00:51',
            'status'        => 'completed',
        ]);

        ProductionPlan::create([
            'line_master_id' => $this->pressA->id,
            'press_name'    => 'Line A',
            'plan_date'     => $morningDate,
            'shift_name'    => 'Shift Pagi',
            'row_no'        => 1,
            'row_type'      => 'job',
            'job_no'        => 'Y-1561',
            'job_master'    => 'IF-013 FNS',
            'plan'          => 200,
            'original_plan' => 200,
            'start_time'    => '08:00',
            'finish_time'   => '09:00',
            'status'        => 'pending',
        ]);

        // Assign leaderUser ke Line A Shift Malam
        LineAssignment::create([
            'line_name'      => 'Line A',
            'shift_name'     => 'Shift Malam',
            'leader_user_id' => $this->leaderUser->id,
        ]);

        // Waktu sekarang: 2026-07-03 pukul 08:00 pagi (setelah cutoff 07:30!)
        Carbon::setTestNow(Carbon::parse('2026-07-03 08:00:00'));
        Auth::login($this->leaderUser);

        $sigController = app(SignatureController::class);

        // 1. Cek pending notification setelah pukul 07:30 pagi
        $pendingRes = $sigController->pending(Request::create('/signatures/pending', 'GET'));
        $pendingPayload = $pendingRes->getData(true);

        $this->assertTrue($pendingPayload['pending']);
        $this->assertSame('teamleader', $pendingPayload['role']);
        $this->assertSame($nightWorkDate, $pendingPayload['date']);
        $this->assertSame('Shift Malam', $pendingPayload['shiftName']);
        $this->assertStringContainsString('shift=Shift%20Malam', $pendingPayload['url']);
        $this->assertStringContainsString('date=2026-07-02', $pendingPayload['url']);

        // 2. Simpan tanda tangan Team Leader untuk Shift Malam (2026-07-02) pada pukul 08:05 pagi
        $saveReq = Request::create('/signatures/save', 'POST', [
            'role'       => 'teamleader',
            'work_date'  => $nightWorkDate,
            'line_name'  => 'Line A',
            'shift_name' => 'Shift Malam',
            'signature'  => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
        ]);
        $saveReq->setUserResolver(fn () => $this->leaderUser);
        $saveRes = $sigController->save($saveReq)->getData(true);
        $this->assertTrue($saveRes['success']);

        // Pastikan tanda tangan tersimpan di (2026-07-02, Shift Malam) dan TIDAK bocor ke Shift Pagi (2026-07-02 maupun 2026-07-03)
        $this->assertTrue(Signature::where('work_date', $nightWorkDate)->where('shift_name', 'Shift Malam')->where('role', 'teamleader')->exists());
        $this->assertFalse(Signature::where('shift_name', 'Shift Pagi')->exists());
    }

    /**
     * Skenario 8 (P2 Tahap 2):
     * - Dashboard & Monitoring TV (DashboardRealtimeService + DashboardDetailService) dibuka SEBELUM Input Harian.
     * - Schedule multi-break (95 + 335 + 70 = 500) + 2 schedule independen dengan job_no sama.
     * - Memastikan:
     *   1. DashboardRealtimeService dan DashboardDetailService HANYA membuat JobMaster untuk root schedule (!isChildSlice()).
     *   2. Child slices TIDAK dibuatkan JobMaster baru.
     *   3. Target QTY di KPI Dashboard, Rincian QTY, dan Stroke Plan menggunakan effectiveTargetQty() tepat 1x (tidak undercount 95 dan tidak double-count).
     *   4. Dua schedule independen dengan job_no sama tetap tampil sebagai 2 baris terpisah di Rincian QTY.
     */
    public function test_dashboard_and_monitoring_tv_opened_before_input_harian_never_creates_child_job_masters_and_preserves_independent_schedules(): void
    {
        $workDate = '2026-07-05';
        Carbon::setTestNow(Carbon::parse('2026-07-05 09:00:00'));
        \App\Services\DashboardDetailService::flushCache();

        // 1. Schedule 1: Multi-break (95 + 335 + 70 = 500) dengan job_no 'PART-DUP'
        $root1 = ProductionPlan::create([
            'line_master_id' => $this->pressA->id,
            'press_name'     => 'Line A',
            'plan_date'      => $workDate,
            'shift_name'     => 'Shift Pagi',
            'row_no'         => 1,
            'row_type'       => 'job',
            'job_no'         => 'PART-DUP',
            'job_master'     => 'PART-DUP FNS',
            'plan'           => 95,
            'original_plan'  => 500,
            'split_group'    => 'SPLIT-DUP-1',
            'session_no'     => 1,
            'a1'             => 1,
            'a2'             => 1,
            'total_mesin'    => 2,
            'start_time'     => '07:30',
            'finish_time'    => '09:30',
            'status'         => 'pending',
        ]);

        $slice1b = ProductionPlan::create([
            'line_master_id' => $this->pressA->id,
            'press_name'     => 'Line A',
            'plan_date'      => $workDate,
            'shift_name'     => 'Shift Pagi',
            'row_no'         => 2,
            'row_type'       => 'job',
            'job_no'         => 'PART-DUP',
            'job_master'     => 'PART-DUP FNS',
            'plan'           => 335,
            'original_plan'  => 500,
            'parent_job_id'  => $root1->id,
            'split_group'    => 'SPLIT-DUP-1',
            'session_no'     => 2,
            'a1'             => 1,
            'a2'             => 1,
            'total_mesin'    => 2,
            'start_time'     => '09:45',
            'finish_time'    => '11:45',
            'status'         => 'pending',
        ]);

        $slice1c = ProductionPlan::create([
            'line_master_id' => $this->pressA->id,
            'press_name'     => 'Line A',
            'plan_date'      => $workDate,
            'shift_name'     => 'Shift Pagi',
            'row_no'         => 3,
            'row_type'       => 'job',
            'job_no'         => 'PART-DUP',
            'job_master'     => 'PART-DUP FNS',
            'plan'           => 70,
            'original_plan'  => 500,
            'parent_job_id'  => $root1->id,
            'split_group'    => 'SPLIT-DUP-1',
            'session_no'     => 3,
            'a1'             => 1,
            'a2'             => 1,
            'total_mesin'    => 2,
            'start_time'     => '12:30',
            'finish_time'    => '13:15',
            'status'         => 'pending',
        ]);

        // 2. Schedule 2: Independen kedua pada hari & shift yang sama dengan job_no sama ('PART-DUP'), target 300
        $root2 = ProductionPlan::create([
            'line_master_id' => $this->pressA->id,
            'press_name'     => 'Line A',
            'plan_date'      => $workDate,
            'shift_name'     => 'Shift Pagi',
            'row_no'         => 4,
            'row_type'       => 'job',
            'job_no'         => 'PART-DUP',
            'job_master'     => 'PART-DUP FNS',
            'plan'           => 300,
            'original_plan'  => 300,
            'a1'             => 1,
            'a2'             => 1,
            'total_mesin'    => 2,
            'start_time'     => '13:30',
            'finish_time'    => '15:30',
            'status'         => 'pending',
        ]);

        // Pastikan belum ada JobMaster sama sekali sebelum Dashboard dibuka
        $this->assertSame(0, JobMaster::count());

        // Buka DashboardRealtimeService & DashboardDetailService (Monitoring TV & Rincian QTY) SEBELUM Input Harian
        $realtimeSvc = app(\App\Services\DashboardRealtimeService::class);
        $detailSvc   = app(\App\Services\DashboardDetailService::class);

        $rtPayload = $realtimeSvc->getLineMetrics('Line A', $workDate, 1);
        $detailRows = $detailSvc->getLineDetail('Line A', $workDate, 1);

        // WAJIB: Hanya 2 JobMaster yang terbuat (untuk $root1 dan $root2). Child slices ($slice1b, $slice1c) TIDAK dibuatkan JobMaster!
        $allJms = JobMaster::orderBy('id')->get();
        $this->assertCount(2, $allJms);
        $this->assertSame($root1->canonicalJobNumber(), $allJms[0]->job_number);
        $this->assertSame(500, (int) $allJms[0]->target_qty);
        $this->assertSame('13:15', Carbon::parse($allJms[0]->plan_end)->format('H:i'));

        $this->assertSame($root2->canonicalJobNumber(), $allJms[1]->job_number);
        $this->assertSame(300, (int) $allJms[1]->target_qty);

        $this->assertNull(JobMaster::where('job_number', 'PART-DUP-'.$slice1b->id)->first());
        $this->assertNull(JobMaster::where('job_number', 'PART-DUP-'.$slice1c->id)->first());

        // Cek KPI DashboardRealtimeService: Total Plan QTY = 500 + 300 = 800, Total Jobs = 2, Stroke Plan = (500*2) + (300*2) = 1600
        $qtyKpi = collect($rtPayload['kpi'])->firstWhere('desc', 'QTY');
        $this->assertSame('800', $qtyKpi['plan']);
        $this->assertSame('2', $rtPayload['meta']['jobPlan']);
        $this->assertSame('1600', $rtPayload['meta']['strokePlan']);
        $this->assertCount(2, $rtPayload['detailData']['QTY']['Line A']['rows']);

        // Cek Rincian QTY (DashboardDetailService): Tampil tepat 2 baris (1 untuk multi-break 500, 1 untuk schedule independen 300)
        $this->assertCount(2, $detailRows);
        $this->assertSame(500, (int) $detailRows[0]['plan_qty']);
        $this->assertSame('13:15', substr((string) $detailRows[0]['plan_finish'], 0, 5));
        $this->assertSame(300, (int) $detailRows[1]['plan_qty']);
        $this->assertSame('15:30', substr((string) $detailRows[1]['plan_finish'], 0, 5));
    }

    /**
     * Skenario 9 (P2 Tahap 2):
     * - Kompatibilitas data historis di DashboardRealtimeService dan DashboardDetailService:
     *   actual produksi, runtime, downtime, dan RepairRejectLog tersebar di parent JobMaster dan child JobMaster historis
     *   (termasuk Shift Malam lintas tengah malam D+1).
     * - Memastikan agregasi tepat 1 kali (tanpa double-counting, tanpa duplikasi baris 'Sisa Agregat' palsu).
     */
    public function test_dashboard_and_detail_aggregate_historical_child_job_masters_and_night_shift_once_without_duplication(): void
    {
        $workDate   = '2026-07-06';
        $nextDayStr = '2026-07-07';
        Carbon::setTestNow(Carbon::parse('2026-07-07 01:30:00'));
        \App\Services\DashboardDetailService::flushCache();

        $rootPlan = ProductionPlan::create([
            'line_master_id' => $this->pressA->id,
            'press_name'     => 'Line A',
            'plan_date'      => $workDate,
            'shift_name'     => 'Shift Malam',
            'row_no'         => 1,
            'row_type'       => 'job',
            'job_no'         => 'HIST-900',
            'job_master'     => 'HIST-900 FNS',
            'plan'           => 150,
            'original_plan'  => 400,
            'split_group'    => 'SPLIT-HIST-900',
            'session_no'     => 1,
            'start_time'     => '22:00',
            'finish_time'    => '00:00',
            'status'         => 'completed',
        ]);

        $childPlan = ProductionPlan::create([
            'line_master_id' => $this->pressA->id,
            'press_name'     => 'Line A',
            'plan_date'      => $workDate,
            'shift_name'     => 'Shift Malam',
            'row_no'         => 2,
            'row_type'       => 'job',
            'job_no'         => 'HIST-900',
            'job_master'     => 'HIST-900 FNS',
            'plan'           => 250,
            'original_plan'  => 400,
            'parent_job_id'  => $rootPlan->id,
            'split_group'    => 'SPLIT-HIST-900',
            'session_no'     => 2,
            'start_time'     => '00:45',
            'finish_time'    => '02:15',
            'status'         => 'running',
        ]);

        // Simulasikan data historis di mana parentJm dan childJm keduanya terlanjur ada di DB
        $parentJm = JobMaster::create([
            'job_number'  => $rootPlan->canonicalJobNumber(),
            'job_name'    => 'HIST-900 FNS',
            'line'        => 'Line A',
            'target_qty'  => 150,
            'sequence_no' => 1,
            'status'      => 'complete',
            'started_at'  => Carbon::parse('2026-07-06 22:05:00'),
            'finished_at' => Carbon::parse('2026-07-06 23:55:00'),
        ]);

        $childJm = JobMaster::create([
            'job_number'  => 'HIST-900-'.$childPlan->id,
            'job_name'    => 'HIST-900 FNS',
            'line'        => 'Line A',
            'target_qty'  => 250,
            'sequence_no' => 2,
            'status'      => 'running',
            'started_at'  => Carbon::parse('2026-07-07 00:50:00'),
        ]);

        // DailyProduction parent pada work_date (2026-07-06)
        DailyProduction::create([
            'job_master_id'    => $parentJm->id,
            'work_date'        => $workDate,
            'line'             => 'Line A',
            'shift'            => 'Shift Malam',
            'target_qty'       => 150,
            'actual_ok'        => 150,
            'actual_qty'       => 150,
            'actual_repair'    => 2,
            'actual_reject'    => 1,
            'runtime_seconds'  => 3600,
            'downtime_seconds' => 600,
        ]);

        // DailyProduction child dibuat setelah tengah malam (work_date D+1 = 2026-07-07 pada data legacy)
        DailyProduction::create([
            'job_master_id'    => $childJm->id,
            'work_date'        => $nextDayStr,
            'line'             => 'Line A',
            'shift'            => 'Shift Malam',
            'target_qty'       => 250,
            'actual_ok'        => 210,
            'actual_qty'       => 210,
            'actual_repair'    => 3,
            'actual_reject'    => 0,
            'runtime_seconds'  => 2400,
            'downtime_seconds' => 300,
        ]);

        // Downtime tercatat pada childJm setelah tengah malam (01:00)
        \App\Models\Downtime::create([
            'job_master_id'    => $childJm->id,
            'jenis_downtime'   => 'MACHINE',
            'problem'          => 'Sensor kendor',
            'penyebab'         => 'Getaran',
            'action'           => 'Kencangkan baut',
            'start_time'       => Carbon::parse('2026-07-07 01:00:00'),
            'finish_time'      => Carbon::parse('2026-07-07 01:05:00'),
            'duration_seconds' => 300,
        ]);

        // RepairRejectLog: 2 pcs di parentJm (23:10) dan 3 pcs di childJm (01:15) -> total 5 pcs = actual_repair (2+3)
        \App\Models\RepairRejectLog::create([
            'job_master_id'   => $parentJm->id,
            'type'            => 'repair',
            'defect_name'     => 'Baret',
            'qty_a'           => 2,
            'area_problem'    => 'Edge',
            'root_cause'      => 'Burr',
            'countermeasure'  => 'Buffing',
            'created_by'      => $this->leaderUser->id,
            'created_at'      => Carbon::parse('2026-07-06 23:10:00'),
        ]);
        \App\Models\RepairRejectLog::create([
            'job_master_id'   => $childJm->id,
            'type'            => 'repair',
            'defect_name'     => 'Penyok',
            'qty_a'           => 3,
            'area_problem'    => 'Corner',
            'root_cause'      => 'Handling',
            'countermeasure'  => 'Re-adjust chute',
            'created_by'      => $this->leaderUser->id,
            'created_at'      => Carbon::parse('2026-07-07 01:15:00'),
        ]);

        $realtimeSvc = app(\App\Services\DashboardRealtimeService::class);
        $detailSvc   = app(\App\Services\DashboardDetailService::class);

        $rt = $realtimeSvc->getLineMetrics('Line A', $workDate, 2);
        $rows = $detailSvc->getLineDetail('Line A', $workDate, 2);

        // 1. Verifikasi DashboardRealtimeService
        $qtyKpi = collect($rt['kpi'])->firstWhere('desc', 'QTY');
        $repKpi = collect($rt['kpi'])->firstWhere('desc', 'REPAIR');
        $rejKpi = collect($rt['kpi'])->firstWhere('desc', 'REJECT');

        $this->assertSame('400', $qtyKpi['plan']);
        $this->assertSame('360', $qtyKpi['actual']); // 150 + 210 tepat 1x
        $this->assertSame('360', $qtyKpi['current']); // status group running
        $this->assertSame('5 pcs', $repKpi['actual']); // 2 + 3
        $this->assertSame('1 pcs', $rejKpi['actual']);

        // Hanya 1 baris di popup QTY DashboardRealtimeService
        $this->assertCount(1, $rt['detailData']['QTY']['Line A']['rows']);
        $this->assertSame(360, $rt['detailData']['QTY']['Line A']['rows'][0]['ok']);

        // Popup REPAIR: karena 2 + 3 = 5 pcs sudah tercatat di RepairRejectLog dan dipetakan ke root job, TIDAK boleh ada baris 'Sisa Agregat' palsu!
        $rtRepairRows = $rt['detailData']['REPAIR']['Line A']['rows'];
        $this->assertCount(2, $rtRepairRows);
        $this->assertFalse(collect($rtRepairRows)->contains(fn ($r) => ! empty($r['is_aggregate'])));

        // 2. Verifikasi DashboardDetailService (Rincian QTY)
        $this->assertCount(1, $rows);
        $this->assertSame(400, $rows[0]['plan_qty']);
        $this->assertSame(360, $rows[0]['good']);
        $this->assertSame(5, $rows[0]['repair']);
        $this->assertSame(1, $rows[0]['reject']);
        $this->assertEqualsWithDelta(100.0, $rows[0]['press_time'], 0.1); // (3600 + 2400) / 60 = 100 menit
        $this->assertCount(2, $rows[0]['repair_list']);
    }

    /**
     * Skenario 10 (P2 Tahap 2):
     * - Next Item (nextList & nextProcess) melewati child slice internal dan menuju schedule independen berikutnya.
     * - Finalisasi Shift (submitShift) mengevaluasi satu schedule utuh (bukan per potongan terpisah) dan mendukung
     *   window Shift Malam setelah tengah malam (00:00–07:30).
     */
    public function test_next_item_skips_child_slices_and_submit_shift_evaluates_unified_root_schedule_on_night_shift(): void
    {
        $workDate = '2026-07-08';
        Carbon::setTestNow(Carbon::parse('2026-07-09 01:20:00')); // Shift Malam setelah tengah malam!
        Auth::login($this->leaderUser);

        // Schedule 1: terpotong istirahat (100 + 200 = 300)
        $plan1Root = ProductionPlan::create([
            'line_master_id' => $this->pressA->id,
            'press_name'     => 'Line A',
            'plan_date'      => $workDate,
            'shift_name'     => 'Shift Malam',
            'row_no'         => 1,
            'row_type'       => 'job',
            'job_no'         => 'JOB-S1',
            'job_master'     => 'JOB-S1 PART',
            'plan'           => 100,
            'original_plan'  => 300,
            'split_group'    => 'SPLIT-S1',
            'session_no'     => 1,
            'start_time'     => '22:00',
            'finish_time'    => '00:00',
            'status'         => 'running',
        ]);

        $plan1Child = ProductionPlan::create([
            'line_master_id' => $this->pressA->id,
            'press_name'     => 'Line A',
            'plan_date'      => $workDate,
            'shift_name'     => 'Shift Malam',
            'row_no'         => 2,
            'row_type'       => 'job',
            'job_no'         => 'JOB-S1',
            'job_master'     => 'JOB-S1 PART',
            'plan'           => 200,
            'original_plan'  => 300,
            'parent_job_id'  => $plan1Root->id,
            'split_group'    => 'SPLIT-S1',
            'session_no'     => 2,
            'start_time'     => '00:45',
            'finish_time'    => '02:00',
            'status'         => 'pending',
        ]);

        // Schedule 2: schedule independen berikutnya (target 250)
        $plan2Root = ProductionPlan::create([
            'line_master_id' => $this->pressA->id,
            'press_name'     => 'Line A',
            'plan_date'      => $workDate,
            'shift_name'     => 'Shift Malam',
            'row_no'         => 3,
            'row_type'       => 'job',
            'job_no'         => 'JOB-S2',
            'job_master'     => 'JOB-S2 PART',
            'plan'           => 250,
            'original_plan'  => 250,
            'start_time'     => '02:15',
            'finish_time'    => '04:00',
            'status'         => 'pending',
        ]);

        // Buat JobMaster (termasuk childJm historis untuk menguji ketahanan nextList & submitShift)
        $jm1Root = JobMaster::create([
            'job_number'  => $plan1Root->canonicalJobNumber(),
            'job_name'    => 'JOB-S1 PART',
            'line'        => 'Line A',
            'target_qty'  => 300,
            'sequence_no' => 1,
            'status'      => 'running',
            'started_at'  => Carbon::parse('2026-07-08 22:05:00'),
        ]);

        $jm1Child = JobMaster::create([
            'job_number'  => 'JOB-S1-'.$plan1Child->id,
            'job_name'    => 'JOB-S1 PART',
            'line'        => 'Line A',
            'target_qty'  => 200,
            'sequence_no' => 2,
            'status'      => 'pending',
        ]);

        $jm2Root = JobMaster::create([
            'job_number'  => $plan2Root->canonicalJobNumber(),
            'job_name'    => 'JOB-S2 PART',
            'line'        => 'Line A',
            'target_qty'  => 250,
            'sequence_no' => 3,
            'status'      => 'pending',
        ]);

        $ihController = app(InputHarianController::class);

        // 1. Uji nextList($jm1Root->id): TIDAK boleh menampilkan $jm1Child, hanya boleh menampilkan $jm2Root (250 pcs)
        $nextListRes = $ihController->nextList($jm1Root->id)->getData(true);
        $this->assertCount(1, $nextListRes);
        $this->assertSame($jm2Root->id, $nextListRes[0]['id']);
        $this->assertStringContainsString('250 pcs', $nextListRes[0]['label']);

        // 2. Uji nextProcess($jm1Root->id): menyelesaikan $jm1Root, melewati $jm1Child, dan menjalankan $jm2Root
        $nextProcReq = Request::create('/operational/next-process/'.$jm1Root->id, 'POST', [
            'date'  => $workDate,
            'shift' => 'Shift Malam',
        ]);
        $nextProcReq->setUserResolver(fn () => $this->leaderUser);
        $nextProcRes = $ihController->nextProcess($nextProcReq, $jm1Root->id)->getData(true);

        $this->assertTrue($nextProcRes['success']);
        $this->assertSame($jm2Root->id, $nextProcRes['next_id']);
        $this->assertSame('running', $jm2Root->fresh()->status);

        // 3. Uji submitShift() pada Shift Malam setelah tengah malam (01:20 pagi):
        // Simulasikan actual_repair tersebar: 2 pcs di $jm1Root dan 3 pcs di $jm1Child (total 5 pcs untuk Schedule 1),
        // sementara form RepairRejectLog sebesar 5 pcs dibuat seluruhnya di $jm1Root pada pukul 01:10 dini hari (tanggal kalender D+1 = 2026-07-09)
        // beserta foto bukti.
        DailyProduction::create([
            'job_master_id' => $jm1Root->id,
            'work_date'     => $workDate,
            'line'          => 'Line A',
            'shift'         => 'Shift Malam',
            'target_qty'    => 300,
            'actual_ok'     => 100,
            'actual_qty'    => 100,
            'actual_repair' => 2,
            'actual_reject' => 0,
        ]);
        DailyProduction::create([
            'job_master_id' => $jm1Child->id,
            'work_date'     => $workDate,
            'line'          => 'Line A',
            'shift'         => 'Shift Malam',
            'target_qty'    => 200,
            'actual_ok'     => 200,
            'actual_qty'    => 200,
            'actual_repair' => 3,
            'actual_reject' => 0,
        ]);

        $rrLog = \App\Models\RepairRejectLog::create([
            'job_master_id'  => $jm1Root->id,
            'type'           => 'repair',
            'defect_name'    => 'Gelombang',
            'qty_a'          => 5,
            'area_problem'   => 'Surface',
            'root_cause'     => 'Cushion pressure',
            'countermeasure' => 'Adjust pressure',
            'created_by'     => $this->leaderUser->id,
            'created_at'     => Carbon::parse('2026-07-09 01:10:00'),
        ]);
        \App\Models\RepairRejectImage::create([
            'repair_reject_log_id' => $rrLog->id,
            'image_path'           => 'repair_reject/test-evidence.jpg',
        ]);

        $submitReq = Request::create('/operational/submit-shift/'.$this->pressA->id, 'POST', [
            'date'  => $workDate,
            'shift' => 'Shift Malam',
        ]);
        $submitReq->setUserResolver(fn () => $this->leaderUser);
        $submitRes = $ihController->submitShift($submitReq, $this->pressA->id);
        $submitPayload = $submitRes->getData(true);

        // Karena dievaluasi sebagai SATU schedule utuh (repairQty = 2+3 = 5, logged = 5 pada pukul 01:10 D+1),
        // tidak boleh dianggap error 422 incomplete, melainkan masuk tahap review konfirmasi (status 200 needs_review = true, 1 item 'ok')
        $this->assertSame(200, $submitRes->getStatusCode());
        $this->assertTrue($submitPayload['needs_review']);
        $this->assertCount(1, $submitPayload['review']['repair']);
        $this->assertSame('ok', $submitPayload['review']['repair'][0]['status']);
        $this->assertSame(5, $submitPayload['review']['repair'][0]['qty']);
        $this->assertSame(5, $submitPayload['review']['repair'][0]['logged']);
    }
}
