<?php

namespace Tests\Feature;

use App\Models\DailyProduction;
use App\Models\JobMaster;
use App\Models\LineMaster;
use App\Models\ProductionLog;
use App\Models\ProductionPlan;
use App\Models\ProductionSession;
use App\Models\RepairRejectImage;
use App\Models\RepairRejectLog;
use App\Models\User;
use App\Services\DashboardDetailService;
use App\Services\DashboardRealtimeService;
use App\Services\ProductionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ShiftMalamRepairRejectPhotoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DashboardDetailService::flushCache();
        DashboardRealtimeService::flushCache();
        Storage::fake('uploads');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_after_midnight_shift_malam_repair_photo_is_visible_in_monitoring_tv(): void
    {
        $workDate = '2026-11-04';

        // 02:30 on the next calendar day is still Shift Malam for work date 2026-11-04.
        Carbon::setTestNow('2026-11-05 02:30:00');

        $line = LineMaster::create([
            'line_code' => 'PA',
            'line_name' => 'Line A',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'name' => 'Operator Malam',
            'role' => 'superadmin',
        ]);
        $this->actingAs($user);

        $plan = ProductionPlan::create([
            'line_master_id' => $line->id,
            'plan_date' => $workDate,
            'shift_name' => 'Shift Malam',
            'press_name' => 'Line A',
            'row_no' => 1,
            'row_type' => 'job',
            'job_no' => 'JOB-MALAM-PHOTO',
            'job_master' => 'PART MALAM',
            'plan' => 100,
            'status' => 'running',
        ]);

        $job = JobMaster::create([
            'job_number' => 'JOB-MALAM-PHOTO-'.$plan->id,
            'job_name' => 'PART MALAM',
            'line' => 'Line A',
            'status' => 'running',
            'started_at' => Carbon::parse('2026-11-04 22:00:00'),
            'target_qty' => 100,
        ]);

        ProductionSession::create([
            'job_master_id' => $job->id,
            'work_date' => $workDate,
            'start_time' => Carbon::parse('2026-11-04 22:00:00'),
            'status' => 'running',
        ]);

        $photo = UploadedFile::fake()->create('malam-evidence.webp', 150, 'image/webp');

        $response = $this->postJson('/operational/repair-reject', [
            'job_master_id' => $job->id,
            'type' => 'repair',
            'defect_name' => 'Dent Malam',
            'qty_a' => 5,
            'area_problem' => 'OP 10',
            'root_cause' => 'Die kotor',
            'countermeasure' => 'Bersihkan die',
            'date' => $workDate,
            'images' => [$photo],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $log = RepairRejectLog::where('job_master_id', $job->id)->firstOrFail();
        $image = RepairRejectImage::where('repair_reject_log_id', $log->id)->firstOrFail();
        $expectedImageUrl = '/uploads/'.$image->image_path;

        $this->assertSame($expectedImageUrl, $image->url);

        $daily = DailyProduction::where('job_master_id', $job->id)
            ->where('work_date', $workDate)
            ->firstOrFail();

        $this->assertSame(5, (int) $daily->actual_repair, 'After-midnight Shift Malam repair must be included in the work date totals.');

        $detail = app(DashboardDetailService::class)->getLineDetail('Line A', $workDate, 2);
        $this->assertNotEmpty($detail);
        $this->assertSame(5, (int) $detail[0]['repair']);
        $this->assertCount(1, $detail[0]['repair_list']);
        $this->assertSame('Dent Malam', $detail[0]['repair_list'][0]['defect_name']);
        $this->assertSame([$expectedImageUrl], $detail[0]['repair_list'][0]['images']);

        $metrics = app(DashboardRealtimeService::class)->getLineMetrics('Line A', $workDate, 2);
        $this->assertSame('5', (string) $metrics['detailData']['REPAIR']['Line A']['total']);
        $this->assertCount(1, $metrics['detailData']['REPAIR']['Line A']['rows']);
        $this->assertSame([$expectedImageUrl], $metrics['detailData']['REPAIR']['Line A']['rows'][0]['images']);

        $this->getJson('/supervisor/dashboard/detail?date='.$workDate.'&shift=2')
            ->assertOk()
            ->assertJsonPath('detail.Line A.0.repair_list.0.images.0', $expectedImageUrl);

        $this->getJson('/supervisor/dashboard/api-data?date='.$workDate.'&shift=2')
            ->assertOk()
            ->assertJsonPath('detail_data.REPAIR.Line A.rows.0.images.0', $expectedImageUrl);
    }

    public function test_after_midnight_shift_malam_finalization_keeps_logged_repair_photo(): void
    {
        $workDate = '2026-11-04';
        Carbon::setTestNow('2026-11-05 02:30:00');

        $line = LineMaster::create([
            'line_code' => 'PB',
            'line_name' => 'Line B',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'name' => 'Operator Malam B',
            'role' => 'superadmin',
        ]);
        $this->actingAs($user);

        $plan = ProductionPlan::create([
            'line_master_id' => $line->id,
            'plan_date' => $workDate,
            'shift_name' => 'Shift Malam',
            'press_name' => 'Line B',
            'row_no' => 1,
            'row_type' => 'job',
            'job_no' => 'JOB-MALAM-FINISH',
            'job_master' => 'PART MALAM FINISH',
            'plan' => 100,
            'status' => 'running',
        ]);

        $job = JobMaster::create([
            'job_number' => 'JOB-MALAM-FINISH-'.$plan->id,
            'job_name' => 'PART MALAM FINISH',
            'line' => 'Line B',
            'status' => 'running',
            'started_at' => Carbon::parse('2026-11-04 22:00:00'),
            'target_qty' => 100,
        ]);

        ProductionSession::create([
            'job_master_id' => $job->id,
            'work_date' => $workDate,
            'start_time' => Carbon::parse('2026-11-04 22:00:00'),
            'status' => 'running',
        ]);

        $log = RepairRejectLog::create([
            'job_master_id' => $job->id,
            'type' => 'repair',
            'defect_name' => 'Dent Finalisasi',
            'qty_a' => 7,
            'area_problem' => 'OP 20',
            'root_cause' => 'Material',
            'countermeasure' => 'Sortir',
            'created_by' => $user->id,
        ]);

        RepairRejectImage::create([
            'repair_reject_log_id' => $log->id,
            'image_path' => 'repair_reject/'.$log->id.'/finalization.webp',
            'image_type' => 'before',
        ]);

        app(ProductionService::class)->finishJob($job->id, 'STOP_SESSION', false, 100, 0, 0);

        $daily = DailyProduction::where('job_master_id', $job->id)
            ->where('work_date', $workDate)
            ->firstOrFail();

        $this->assertSame(7, (int) $daily->actual_repair);

        $detail = app(DashboardDetailService::class)->getLineDetail('Line B', $workDate, 2);
        $this->assertNotEmpty($detail);
        $this->assertSame(7, (int) $detail[0]['repair']);
        $this->assertCount(1, $detail[0]['repair_list']);
        $this->assertNotEmpty($detail[0]['repair_list'][0]['images']);
    }

    public function test_previous_and_next_shift_logs_are_not_counted_in_shift_malam_totals(): void
    {
        $workDate = '2026-11-04';
        Carbon::setTestNow('2026-11-05 02:30:00');

        $job = $this->createMalamAuditJob($workDate);

        $previousPagi = $this->createProductionLogAt($job, '2026-11-04 10:00:00', 100);
        $this->createProductionLogAt($job, '2026-11-04 22:00:00', 10);
        $this->createProductionLogAt($job, '2026-11-05 02:00:00', 5);
        $nextPagi = $this->createProductionLogAt($job, '2026-11-05 10:00:00', 200);

        app(ProductionService::class)->saveProductionLog($job->id, [
            'ok_qty' => 1,
            'repair_qty' => 0,
            'reject_qty' => 0,
        ], $workDate);

        $daily = DailyProduction::where('job_master_id', $job->id)
            ->where('work_date', $workDate)
            ->firstOrFail();

        $this->assertSame(16, (int) $daily->actual_ok);
        $this->assertDatabaseHas('production_logs', ['id' => $previousPagi->id, 'ok_qty' => 100]);
        $this->assertDatabaseHas('production_logs', ['id' => $nextPagi->id, 'ok_qty' => 200]);
    }

    public function test_finish_job_only_deletes_and_aggregates_current_shift_window(): void
    {
        $workDate = '2026-11-04';
        Carbon::setTestNow('2026-11-05 02:30:00');

        $job = $this->createMalamAuditJob($workDate);

        $priorMalam = $this->createProductionLogAt($job, '2026-11-03 23:00:00', 30);
        $previousPagi = $this->createProductionLogAt($job, '2026-11-04 10:00:00', 100);
        $this->createProductionLogAt($job, '2026-11-04 22:00:00', 10);
        $this->createProductionLogAt($job, '2026-11-05 02:00:00', 5);
        $nextPagi = $this->createProductionLogAt($job, '2026-11-05 10:00:00', 200);

        $previousShiftRepair = $this->createRepairRejectLogAt($job, '2026-11-04 10:00:00', 'repair', 99);
        $this->createRepairRejectLogAt($job, '2026-11-04 22:00:00', 'repair', 4);
        $this->createRepairRejectLogAt($job, '2026-11-05 02:00:00', 'repair', 3);
        $nextShiftReject = $this->createRepairRejectLogAt($job, '2026-11-05 10:00:00', 'reject', 50);

        app(ProductionService::class)->finishJob($job->id, 'STOP_SESSION', false, 100, 0, 0);

        $daily = DailyProduction::where('job_master_id', $job->id)
            ->where('work_date', $workDate)
            ->firstOrFail();

        $this->assertSame(100, (int) $daily->actual_ok);
        $this->assertSame(7, (int) $daily->actual_repair);

        $this->assertDatabaseHas('production_logs', ['id' => $priorMalam->id, 'ok_qty' => 30]);
        $this->assertDatabaseHas('production_logs', ['id' => $previousPagi->id, 'ok_qty' => 100]);
        $this->assertDatabaseHas('production_logs', ['id' => $nextPagi->id, 'ok_qty' => 200]);

        $currentWindowLogs = ProductionLog::where('job_master_id', $job->id)
            ->where('created_at', '>=', Carbon::parse('2026-11-04 21:00:00'))
            ->where('created_at', '<', Carbon::parse('2026-11-05 07:30:00'))
            ->get();

        $this->assertCount(1, $currentWindowLogs);
        $this->assertSame(100, (int) $currentWindowLogs[0]->ok_qty);
        $this->assertSame(7, (int) $currentWindowLogs[0]->repair_qty);
        $this->assertSame(0, (int) $currentWindowLogs[0]->reject_qty);

        $this->assertSame(4, RepairRejectLog::count());
        $this->assertDatabaseHas('repair_reject_logs', ['id' => $previousShiftRepair->id, 'qty_a' => 99]);
        $this->assertDatabaseHas('repair_reject_logs', ['id' => $nextShiftReject->id, 'qty_a' => 50]);

        $metrics = app(DashboardRealtimeService::class)->getLineMetrics('Line Audit', $workDate, 2);
        $this->assertSame('7', (string) $metrics['detailData']['REPAIR']['Line Audit']['total']);
    }

    private function createMalamAuditJob(string $workDate): JobMaster
    {
        $line = LineMaster::create([
            'line_code' => 'PAUD',
            'line_name' => 'Line Audit',
            'status' => 'active',
        ]);

        $plan = ProductionPlan::create([
            'line_master_id' => $line->id,
            'plan_date' => $workDate,
            'shift_name' => 'Shift Malam',
            'press_name' => 'Line Audit',
            'row_no' => 1,
            'row_type' => 'job',
            'job_no' => 'JOB-AUDIT',
            'job_master' => 'PART AUDIT',
            'plan' => 100,
            'status' => 'running',
        ]);

        $job = JobMaster::create([
            'job_number' => 'JOB-AUDIT-'.$plan->id,
            'job_name' => 'PART AUDIT',
            'line' => 'Line Audit',
            'status' => 'running',
            'started_at' => Carbon::parse($workDate.' 22:00:00'),
            'target_qty' => 100,
        ]);

        ProductionSession::create([
            'job_master_id' => $job->id,
            'work_date' => $workDate,
            'start_time' => Carbon::parse($workDate.' 22:00:00'),
            'status' => 'running',
        ]);

        return $job;
    }

    private function createProductionLogAt(JobMaster $job, string $createdAt, int $okQty): ProductionLog
    {
        return ProductionLog::create([
            'job_master_id' => $job->id,
            'ok_qty' => $okQty,
            'repair_qty' => 0,
            'reject_qty' => 0,
            'created_at' => Carbon::parse($createdAt),
            'updated_at' => Carbon::parse($createdAt),
        ]);
    }

    private function createRepairRejectLogAt(JobMaster $job, string $createdAt, string $type, int $qty): RepairRejectLog
    {
        $log = RepairRejectLog::create([
            'job_master_id' => $job->id,
            'type' => $type,
            'defect_name' => 'Audit '.$type,
            'qty_a' => $qty,
        ]);

        DB::table('repair_reject_logs')->where('id', $log->id)->update([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        return $log->refresh();
    }
}
