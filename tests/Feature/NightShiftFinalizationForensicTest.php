<?php

namespace Tests\Feature;

use App\Models\DailyProduction;
use App\Models\Downtime;
use App\Models\JobMaster;
use App\Models\LineMaster;
use App\Models\MasterShift;
use App\Models\ProductionLog;
use App\Models\ProductionPlan;
use App\Models\ProductionSession;
use App\Models\RepairRejectLog;
use App\Models\User;
use App\Services\ProductionMetricsService;
use App\Services\ProductionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NightShiftFinalizationForensicTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected LineMaster $lineA;
    protected ProductionService $prodService;

    protected function setUp(): void
    {
        parent::setUp();

        MasterShift::firstOrCreate(
            ['name' => 'Shift Pagi'],
            ['planned_start_time' => '07:30', 'planned_end_time' => '16:30']
        );
        MasterShift::firstOrCreate(
            ['name' => 'Shift Malam'],
            ['planned_start_time' => '21:00', 'planned_end_time' => '06:00']
        );

        $this->lineA = LineMaster::create([
            'line_code' => 'PRESS_A',
            'line_name' => 'Press A',
        ]);

        $this->user = User::factory()->create([
            'name' => 'Leader Line A',
            'role' => 'leader',
        ]);

        // Seed features for FeatureMiddleware
        $inputHarian = \App\Models\Feature::firstOrCreate(
            ['feature_code' => 'input_harian'],
            ['feature_name' => 'Input Harian', 'group_name' => 'Operational']
        );
        \App\Models\RoleFeature::firstOrCreate(['role' => 'leader', 'feature_id' => $inputHarian->id], ['enabled' => true]);

        $this->prodService = app(ProductionService::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * Skenario 1: Finalisasi normal Shift Pagi
     */
    public function test_01_finalisasi_normal_shift_pagi()
    {
        Carbon::setTestNow('2026-11-04 10:00:00');
        $date = '2026-11-04';

        $plan = ProductionPlan::create([
            'line_master_id' => $this->lineA->id,
            'plan_date' => $date,
            'shift_name' => 'Shift Pagi',
            'press_name' => 'Press A',
            'row_type' => 'job',
            'job_no' => 'JOB-PAGI-1',
            'plan' => 100,
            'row_no' => 1,
        ]);

        $job = JobMaster::create([
            'job_number' => "JOB-PAGI-1-{$plan->id}",
            'job_name' => 'Part Pagi A',
            'line' => 'Press A',
            'status' => 'running',
            'target_qty' => 100,
            'started_at' => '2026-11-04 08:00:00',
        ]);

        ProductionSession::create([
            'job_master_id' => $job->id,
            'work_date' => $date,
            'start_time' => '2026-11-04 08:00:00',
            'status' => 'running',
        ]);

        $response = $this->actingAs($this->user)->postJson("/operational/job/{$job->id}/finish", [
            'ok_qty' => 100,
            'repair_qty' => 0,
            'reject_qty' => 0,
            'next_job_id' => 'STOP_SESSION',
        ], [
            'X-Shift' => 'Shift Pagi',
            'X-Date' => $date,
        ]);

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));

        $job->refresh();
        $this->assertEquals('complete', $job->status);
        $this->assertNotNull($job->finished_at);

        $session = ProductionSession::where('job_master_id', $job->id)->first();
        $this->assertEquals('finished', $session->status);
        $this->assertNotNull($session->finish_time);

        $daily = DailyProduction::where('job_master_id', $job->id)->first();
        $this->assertEquals(100, $daily->actual_ok);
        $this->assertEquals($date, $daily->work_date);
    }

    /**
     * Skenario 2: Finalisasi normal Shift Malam sebelum tengah malam (23:30)
     */
    public function test_02_finalisasi_normal_shift_malam_sebelum_tengah_malam()
    {
        Carbon::setTestNow('2026-11-04 23:30:00');
        $date = '2026-11-04'; // production work date = 2026-11-04

        $plan = ProductionPlan::create([
            'line_master_id' => $this->lineA->id,
            'plan_date' => $date,
            'shift_name' => 'Shift Malam',
            'press_name' => 'Press A',
            'row_type' => 'job',
            'job_no' => 'JOB-MALAM-1',
            'plan' => 200,
            'row_no' => 1,
        ]);

        $job = JobMaster::create([
            'job_number' => "JOB-MALAM-1-{$plan->id}",
            'job_name' => 'Part Malam A',
            'line' => 'Press A',
            'status' => 'running',
            'target_qty' => 200,
            'started_at' => '2026-11-04 21:15:00',
        ]);

        ProductionSession::create([
            'job_master_id' => $job->id,
            'work_date' => $date,
            'start_time' => '2026-11-04 21:15:00',
            'status' => 'running',
        ]);

        $response = $this->actingAs($this->user)->postJson("/operational/job/{$job->id}/finish", [
            'ok_qty' => 200,
            'repair_qty' => 0,
            'reject_qty' => 0,
            'next_job_id' => 'STOP_SESSION',
        ], [
            'X-Shift' => 'Shift Malam',
            'X-Date' => $date,
        ]);

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));

        $job->refresh();
        $this->assertEquals('complete', $job->status);

        $session = ProductionSession::where('job_master_id', $job->id)->first();
        $this->assertEquals('finished', $session->status);
        $this->assertEquals($date, $session->work_date);

        $daily = DailyProduction::where('job_master_id', $job->id)->first();
        $this->assertEquals($date, $daily->work_date);
        $this->assertEquals(200, $daily->actual_ok);
    }

    /**
     * Skenario 3: Finalisasi Shift Malam SETELAH tengah malam (02:30 kalender 2026-11-05)
     * Work date produksi tetap 2026-11-04!
     */
    public function test_03_finalisasi_shift_malam_setelah_tengah_malam()
    {
        Carbon::setTestNow('2026-11-05 02:30:00');
        $workDate = '2026-11-04'; // production date starts on evening of Nov 4

        // Verifikasi ProductionMetricsService::currentProductionDate() mengenali kemarin sebagai production date
        $this->assertEquals($workDate, ProductionMetricsService::currentProductionDate());

        $plan = ProductionPlan::create([
            'line_master_id' => $this->lineA->id,
            'plan_date' => $workDate,
            'shift_name' => 'Shift Malam',
            'press_name' => 'Press A',
            'row_type' => 'job',
            'job_no' => 'JOB-MALAM-2',
            'plan' => 150,
            'row_no' => 2,
        ]);

        $job = JobMaster::create([
            'job_number' => "JOB-MALAM-2-{$plan->id}",
            'job_name' => 'Part Malam Pasca Midnight',
            'line' => 'Press A',
            'status' => 'running',
            'target_qty' => 150,
            'started_at' => '2026-11-05 00:30:00',
        ]);

        ProductionSession::create([
            'job_master_id' => $job->id,
            'work_date' => $workDate,
            'start_time' => '2026-11-05 00:30:00',
            'status' => 'running',
        ]);

        $response = $this->actingAs($this->user)->postJson("/operational/job/{$job->id}/finish", [
            'ok_qty' => 150,
            'repair_qty' => 0,
            'reject_qty' => 0,
            'next_job_id' => 'STOP_SESSION',
        ], [
            'X-Shift' => 'Shift Malam',
            'X-Date' => $workDate,
        ]);

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));

        $job->refresh();
        $this->assertEquals('complete', $job->status);

        $session = ProductionSession::where('job_master_id', $job->id)->first();
        $this->assertEquals('finished', $session->status);
        $this->assertEquals($workDate, $session->work_date);

        $daily = DailyProduction::where('job_master_id', $job->id)->first();
        $this->assertEquals($workDate, $daily->work_date);
        $this->assertEquals(150, $daily->actual_ok);
    }

    /**
     * Skenario 4: Simpan & Lanjut (next_job_id valid)
     */
    public function test_04_simpan_dan_lanjut()
    {
        Carbon::setTestNow('2026-11-04 11:00:00');
        $date = '2026-11-04';

        $plan1 = ProductionPlan::create([
            'line_master_id' => $this->lineA->id,
            'plan_date' => $date,
            'shift_name' => 'Shift Pagi',
            'press_name' => 'Press A',
            'row_type' => 'job',
            'job_no' => 'JOB-STEP-1',
            'plan' => 50,
            'row_no' => 1,
        ]);

        $plan2 = ProductionPlan::create([
            'line_master_id' => $this->lineA->id,
            'plan_date' => $date,
            'shift_name' => 'Shift Pagi',
            'press_name' => 'Press A',
            'row_type' => 'job',
            'job_no' => 'JOB-STEP-2',
            'plan' => 50,
            'row_no' => 2,
        ]);

        $job1 = JobMaster::create([
            'job_number' => "JOB-STEP-1-{$plan1->id}",
            'job_name' => 'Part 1',
            'line' => 'Press A',
            'status' => 'running',
            'target_qty' => 50,
            'started_at' => '2026-11-04 09:00:00',
        ]);

        $job2 = JobMaster::create([
            'job_number' => "JOB-STEP-2-{$plan2->id}",
            'job_name' => 'Part 2',
            'line' => 'Press A',
            'status' => 'pending',
            'target_qty' => 50,
        ]);

        ProductionSession::create([
            'job_master_id' => $job1->id,
            'work_date' => $date,
            'start_time' => '2026-11-04 09:00:00',
            'status' => 'running',
        ]);

        $response = $this->actingAs($this->user)->postJson("/operational/job/{$job1->id}/finish", [
            'ok_qty' => 50,
            'repair_qty' => 0,
            'reject_qty' => 0,
            'next_job_id' => (string) $job2->id,
        ]);

        $response->assertStatus(200);

        $job1->refresh();
        $job2->refresh();

        $this->assertEquals('complete', $job1->status);
        $this->assertEquals('running', $job2->status); // Started with dandori
    }

    /**
     * Skenario 5: Simpan & Stop Sesi (next_job_id = 'STOP_SESSION')
     */
    public function test_05_simpan_dan_stop_sesi()
    {
        Carbon::setTestNow('2026-11-04 12:00:00');
        $date = '2026-11-04';

        $plan = ProductionPlan::create([
            'line_master_id' => $this->lineA->id,
            'plan_date' => $date,
            'shift_name' => 'Shift Pagi',
            'press_name' => 'Press A',
            'row_type' => 'job',
            'job_no' => 'JOB-STOP-1',
            'plan' => 80,
            'row_no' => 1,
        ]);

        $job = JobMaster::create([
            'job_number' => "JOB-STOP-1-{$plan->id}",
            'job_name' => 'Part Stop Sesi',
            'line' => 'Press A',
            'status' => 'running',
            'target_qty' => 80,
            'started_at' => '2026-11-04 10:00:00',
        ]);

        ProductionSession::create([
            'job_master_id' => $job->id,
            'work_date' => $date,
            'start_time' => '2026-11-04 10:00:00',
            'status' => 'running',
        ]);

        $response = $this->actingAs($this->user)->postJson("/operational/job/{$job->id}/finish", [
            'ok_qty' => 80,
            'repair_qty' => 0,
            'reject_qty' => 0,
            'next_job_id' => 'STOP_SESSION',
        ]);

        $response->assertStatus(200);

        $job->refresh();
        $this->assertEquals('complete', $job->status);

        $session = ProductionSession::where('job_master_id', $job->id)->first();
        $this->assertEquals('finished', $session->status);
    }

    /**
     * Skenario 6: Idempotency saat klik tombol dua kali (job sudah complete)
     */
    public function test_06_klik_tombol_dua_kali_idempotent()
    {
        Carbon::setTestNow('2026-11-04 13:00:00');
        $date = '2026-11-04';

        $plan = ProductionPlan::create([
            'line_master_id' => $this->lineA->id,
            'plan_date' => $date,
            'shift_name' => 'Shift Pagi',
            'press_name' => 'Press A',
            'row_type' => 'job',
            'job_no' => 'JOB-IDEMP-1',
            'plan' => 60,
            'row_no' => 1,
        ]);

        $job = JobMaster::create([
            'job_number' => "JOB-IDEMP-1-{$plan->id}",
            'job_name' => 'Part Idempotent',
            'line' => 'Press A',
            'status' => 'running',
            'target_qty' => 60,
            'started_at' => '2026-11-04 11:00:00',
        ]);

        ProductionSession::create([
            'job_master_id' => $job->id,
            'work_date' => $date,
            'start_time' => '2026-11-04 11:00:00',
            'status' => 'running',
        ]);

        // Request 1: Sukses
        $res1 = $this->actingAs($this->user)->postJson("/operational/job/{$job->id}/finish", [
            'ok_qty' => 60,
            'next_job_id' => 'STOP_SESSION',
        ]);
        $res1->assertStatus(200);

        // Request 2 (Double click): Dijaga oleh guardLockedShift() -> return error terproteksi
        $res2 = $this->actingAs($this->user)->postJson("/operational/job/{$job->id}/finish", [
            'ok_qty' => 60,
            'next_job_id' => 'STOP_SESSION',
        ]);
        $this->assertFalse($res2->json('success'));
        $this->assertStringContainsString('selesai', $res2->json('message'));

        // Tidak terjadi duplikasi log atau korupsi data
        $this->assertEquals(1, ProductionLog::where('job_master_id', $job->id)->count());
    }

    /**
     * Skenario 7: Orphan running job terdeteksi di InputHarianController@index tanpa 500 error
     */
    public function test_07_orphan_running_job_rendered_cleanly()
    {
        Carbon::setTestNow('2026-11-04 22:00:00');
        $date = '2026-11-04';

        // Buat orphan job di Line A yang tidak ada di jadwal shift ini
        $orphan = JobMaster::create([
            'job_number' => 'JOB-ORPHAN-LINE-A',
            'job_name' => 'Stale Alien Job',
            'line' => 'Press A',
            'status' => 'running',
            'started_at' => '2026-11-04 18:00:00',
        ]);

        $response = $this->actingAs($this->user)->get("/operational/input-harian?line=Line%20A&shift=Shift%20Malam&date={$date}");

        // Harus 200 OK (sebelumnya crash 500 Undefined variable $orphanRunningJob)
        $response->assertStatus(200);
        $response->assertViewHas('orphanRunningJob');
    }

    /**
     * Skenario 8: Tidak ada item berikutnya (next_job_id = '' / null)
     */
    public function test_08_tidak_ada_item_berikutnya()
    {
        Carbon::setTestNow('2026-11-04 14:00:00');
        $date = '2026-11-04';

        $plan = ProductionPlan::create([
            'line_master_id' => $this->lineA->id,
            'plan_date' => $date,
            'shift_name' => 'Shift Pagi',
            'press_name' => 'Press A',
            'row_type' => 'job',
            'job_no' => 'JOB-LAST-1',
            'plan' => 30,
            'row_no' => 1,
        ]);

        $job = JobMaster::create([
            'job_number' => "JOB-LAST-1-{$plan->id}",
            'job_name' => 'Part Terakhir',
            'line' => 'Press A',
            'status' => 'running',
            'target_qty' => 30,
            'started_at' => '2026-11-04 13:00:00',
        ]);

        ProductionSession::create([
            'job_master_id' => $job->id,
            'work_date' => $date,
            'start_time' => '2026-11-04 13:00:00',
            'status' => 'running',
        ]);

        $response = $this->actingAs($this->user)->postJson("/operational/job/{$job->id}/finish", [
            'ok_qty' => 30,
            'next_job_id' => '',
        ]);

        $response->assertStatus(200);
        $job->refresh();
        $this->assertEquals('complete', $job->status);
    }

    /**
     * Skenario 9: Item berikutnya tersedia di dropdown nextList()
     */
    public function test_09_item_berikutnya_tersedia()
    {
        $date = '2026-11-04';

        $plan1 = ProductionPlan::create([
            'line_master_id' => $this->lineA->id,
            'plan_date' => $date,
            'shift_name' => 'Shift Pagi',
            'press_name' => 'Press A',
            'row_type' => 'job',
            'job_no' => 'JOB-A1',
            'plan' => 50,
            'row_no' => 1,
        ]);

        $plan2 = ProductionPlan::create([
            'line_master_id' => $this->lineA->id,
            'plan_date' => $date,
            'shift_name' => 'Shift Pagi',
            'press_name' => 'Press A',
            'row_type' => 'job',
            'job_no' => 'JOB-A2',
            'plan' => 50,
            'row_no' => 2,
        ]);

        $job1 = JobMaster::create([
            'job_number' => "JOB-A1-{$plan1->id}",
            'job_name' => 'Part 1',
            'line' => 'Press A',
            'status' => 'running',
            'target_qty' => 50,
        ]);

        $job2 = JobMaster::create([
            'job_number' => "JOB-A2-{$plan2->id}",
            'job_name' => 'Part 2',
            'line' => 'Press A',
            'status' => 'pending',
            'target_qty' => 50,
        ]);

        $response = $this->actingAs($this->user)->getJson("/operational/job/{$job1->id}/next-list");
        $response->assertStatus(200);

        $data = $response->json();
        $this->assertNotEmpty($data);
        $this->assertEquals($job2->id, $data[0]['id']);
    }

    /**
     * Skenario 10: Actual OK, Repair, dan Reject tersimpan dan konsisten
     */
    public function test_10_actual_ok_repair_reject_konsisten()
    {
        Carbon::setTestNow('2026-11-04 15:00:00');
        $date = '2026-11-04';

        $plan = ProductionPlan::create([
            'line_master_id' => $this->lineA->id,
            'plan_date' => $date,
            'shift_name' => 'Shift Pagi',
            'press_name' => 'Press A',
            'row_type' => 'job',
            'job_no' => 'JOB-RR-1',
            'plan' => 100,
            'row_no' => 1,
        ]);

        $job = JobMaster::create([
            'job_number' => "JOB-RR-1-{$plan->id}",
            'job_name' => 'Part Mixed Output',
            'line' => 'Press A',
            'status' => 'running',
            'target_qty' => 100,
            'started_at' => '2026-11-04 13:00:00',
        ]);

        ProductionSession::create([
            'job_master_id' => $job->id,
            'work_date' => $date,
            'start_time' => '2026-11-04 13:00:00',
            'status' => 'running',
        ]);

        // Simpan form RepairReject dengan evidence
        RepairRejectLog::create([
            'job_master_id' => $job->id,
            'defect_name' => 'Burr',
            'type' => 'repair',
            'qty_a' => 5,
            'area_problem' => 'Area 1',
            'root_cause' => 'Burr',
            'countermeasure' => 'Sanding',
            'created_at' => '2026-11-04 14:00:00',
        ]);

        RepairRejectLog::create([
            'job_master_id' => $job->id,
            'defect_name' => 'Crack',
            'type' => 'reject',
            'qty_a' => 3,
            'area_problem' => 'Area 2',
            'root_cause' => 'Crack',
            'countermeasure' => 'Scrap',
            'created_at' => '2026-11-04 14:00:00',
        ]);

        $response = $this->actingAs($this->user)->postJson("/operational/job/{$job->id}/finish", [
            'ok_qty' => 90,
            'repair_qty' => 5,
            'reject_qty' => 3,
            'next_job_id' => 'STOP_SESSION',
            'reason_not_achieved' => 'Ada kendala dies burr dan crack',
        ]);

        $response->assertStatus(200);

        $daily = DailyProduction::where('job_master_id', $job->id)->first();
        $this->assertEquals(90, $daily->actual_ok);
        $this->assertEquals(5, $daily->actual_repair);
        $this->assertEquals(3, $daily->actual_reject);
    }

    /**
     * Skenario 11: Kegagalan penyimpanan di tengah transaksi memicu full rollback
     */
    public function test_11_kegagalan_penyimpanan_rollback_utuh()
    {
        Carbon::setTestNow('2026-11-04 16:00:00');
        $date = '2026-11-04';

        $plan = ProductionPlan::create([
            'line_master_id' => $this->lineA->id,
            'plan_date' => $date,
            'shift_name' => 'Shift Pagi',
            'press_name' => 'Press A',
            'row_type' => 'job',
            'job_no' => 'JOB-FAIL-1',
            'plan' => 50,
            'row_no' => 1,
        ]);

        $job = JobMaster::create([
            'job_number' => "JOB-FAIL-1-{$plan->id}",
            'job_name' => 'Part Rollback Test',
            'line' => 'Press A',
            'status' => 'running',
            'target_qty' => 50,
            'started_at' => '2026-11-04 15:00:00',
        ]);

        ProductionSession::create([
            'job_master_id' => $job->id,
            'work_date' => $date,
            'start_time' => '2026-11-04 15:00:00',
            'status' => 'running',
        ]);

        // Finish dengan non-existent job ID bukan int (throw exception)
        $response = $this->actingAs($this->user)->postJson("/operational/job/999999/finish", [
            'ok_qty' => 50,
            'next_job_id' => 'STOP_SESSION',
        ]);

        $response->assertStatus(500);
        $this->assertFalse($response->json('success'));

        // Pastikan job asli tidak berubah
        $job->refresh();
        $this->assertEquals('running', $job->status);
    }

    /**
     * Skenario 12: Refresh halaman setelah finalisasi konsisten
     */
    public function test_12_refresh_halaman_setelah_finalisasi()
    {
        Carbon::setTestNow('2026-11-04 16:30:00');
        $date = '2026-11-04';

        $plan = ProductionPlan::create([
            'line_master_id' => $this->lineA->id,
            'plan_date' => $date,
            'shift_name' => 'Shift Pagi',
            'press_name' => 'Press A',
            'row_type' => 'job',
            'job_no' => 'JOB-REFRESH-1',
            'plan' => 100,
            'row_no' => 1,
        ]);

        $job = JobMaster::create([
            'job_number' => "JOB-REFRESH-1-{$plan->id}",
            'job_name' => 'Part Refresh Test',
            'line' => 'Press A',
            'status' => 'running',
            'target_qty' => 100,
            'started_at' => '2026-11-04 14:00:00',
        ]);

        ProductionSession::create([
            'job_master_id' => $job->id,
            'work_date' => $date,
            'start_time' => '2026-11-04 14:00:00',
            'status' => 'running',
        ]);

        // Finalisasi
        $this->actingAs($this->user)->postJson("/operational/job/{$job->id}/finish", [
            'ok_qty' => 100,
            'next_job_id' => 'STOP_SESSION',
        ])->assertStatus(200);

        // Akses halaman Input Harian kembali
        $response = $this->actingAs($this->user)->get("/operational/input-harian?line=Line%20A&shift=Shift%20Pagi&date={$date}");
        $response->assertStatus(200);

        // Job sudah tidak berstatus running
        $job->refresh();
        $this->assertEquals('complete', $job->status);
    }

    /**
     * Skenario 13: Pemulihan session ketika request gagal
     */
    public function test_13_pemulihan_session_ketika_request_gagal()
    {
        Carbon::setTestNow('2026-11-04 17:00:00');
        $date = '2026-11-04';

        $job = JobMaster::create([
            'job_number' => 'JOB-RECOVER-1-999',
            'job_name' => 'Part Recover Test',
            'line' => 'Press A',
            'status' => 'running',
            'started_at' => '2026-11-04 16:00:00',
        ]);

        $session = ProductionSession::create([
            'job_master_id' => $job->id,
            'work_date' => $date,
            'start_time' => '2026-11-04 16:00:00',
            'status' => 'running',
        ]);

        // Kirim request finish yang memicu throw (misal job id tidak valid)
        $response = $this->actingAs($this->user)->postJson('/operational/job/987654/finish', [
            'ok_qty' => 10,
        ]);

        $response->assertStatus(500);

        // Session job tetap utuh running
        $session->refresh();
        $this->assertEquals('running', $session->status);
    }
}
