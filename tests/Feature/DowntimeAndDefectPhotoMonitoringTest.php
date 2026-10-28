<?php

namespace Tests\Feature;

use App\Models\DailyProduction;
use App\Models\Downtime;
use App\Models\JobMaster;
use App\Models\LineMaster;
use App\Models\ProductionPlan;
use App\Models\ProductionSession;
use App\Models\User;
use App\Services\DashboardDetailService;
use App\Services\DashboardRealtimeService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DowntimeAndDefectPhotoMonitoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_downtime_update_accepts_and_stores_photo()
    {
        $date = '2026-10-23';
        Carbon::setTestNow(Carbon::parse($date . ' 10:30:00'));
        DashboardDetailService::flushCache();

        $user = User::factory()->create([
            'role' => 'operator',
        ]);
        $this->actingAs($user);

        $line = LineMaster::updateOrCreate(
            ['line_code' => 'PA'],
            ['line_name' => 'Line A', 'status' => 'active']
        );

        $plan = ProductionPlan::create([
            'line_master_id' => $line->id,
            'plan_date'      => $date,
            'shift_name'     => 'Shift Pagi',
            'press_name'     => 'PRESS A',
            'row_no'         => 1,
            'row_type'       => 'job',
            'job_no'         => 'GT-1001',
            'job_master'     => 'PART A1',
            'plan'           => 500,
            'status'         => 'running',
        ]);

        $jobIdentifier = 'GT-1001-' . $plan->id;

        $job = JobMaster::create([
            'job_number' => $jobIdentifier,
            'job_name'   => 'PART A1',
            'line'       => 'PRESS A',
            'status'     => 'running',
            'started_at' => Carbon::parse($date . ' 08:00:00'),
        ]);

        $session = ProductionSession::create([
            'job_master_id' => $job->id,
            'work_date'     => $date,
            'status'        => 'running',
            'start_time'    => Carbon::parse($date . ' 08:00:00'),
            'total_seconds' => 1800,
        ]);

        DailyProduction::create([
            'job_master_id'    => $job->id,
            'work_date'        => $date,
            'line'             => 'PRESS A',
            'shift'            => 'Shift Pagi',
            'actual_ok'        => 200,
            'actual_qty'       => 200,
            'runtime_seconds'  => 1200,
            'downtime_seconds' => 300,
        ]);

        $downtime = Downtime::create([
            'production_session_id' => $session->id,
            'job_master_id'         => $job->id,
            'jenis_downtime'        => 'dies',
            'start_time'            => Carbon::parse($date . ' 08:15:00'),
            'finish_time'           => Carbon::parse($date . ' 08:30:00'),
            'duration_seconds'      => 900,
            'duration_minutes'      => 15,
        ]);

        // Create a real dummy file with image extension without GD
        $tempPath = tempnam(sys_get_temp_dir(), 'test_img');
        file_put_contents($tempPath, 'fake-image-binary-content');
        $fakePhoto = new UploadedFile($tempPath, 'evidence.jpg', 'image/jpeg', null, true);

        $response = $this->postJson("/operational/downtime/{$downtime->id}/update", [
            'jenis_downtime' => 'dies',
            'foto'           => $fakePhoto,
            'problem'        => 'Dies retak',
            'penyebab'       => 'Tekanan berlebih',
            'tindakan'       => 'Ganti dies',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $downtime->refresh();
        $this->assertNotNull($downtime->image_path);
        $this->assertStringContainsString('downtime/' . $downtime->id, $downtime->image_path);
        $this->assertEquals('Dies retak', $downtime->problem);

        // Verify file was written to public/uploads
        $targetFile = public_path('uploads/' . $downtime->image_path);
        $this->assertTrue(File::exists($targetFile));

        // Clean up test file
        if (File::exists($targetFile)) {
            File::delete($targetFile);
            @rmdir(dirname($targetFile));
        }

        // Test that DashboardDetailService returns image_url
        $detailService = app(DashboardDetailService::class);
        $detailData = $detailService->getLineDetail('Line A', $date, 1);
        $this->assertNotEmpty($detailData);
        $firstItem = $detailData[0] ?? null;
        $this->assertNotNull($firstItem);
        $this->assertNotEmpty($firstItem['downtime_list']);
        $this->assertNotNull($firstItem['downtime_list'][0]['image_url']);
        $this->assertStringContainsString($downtime->image_path, $firstItem['downtime_list'][0]['image_url']);

        // Test that DashboardRealtimeService detailData returns image_url for downtime
        $realtimeService = app(DashboardRealtimeService::class);
        $realtimeData = $realtimeService->getLineMetrics('Line A', $date, 1);
        $dtDetail = $realtimeData['detailData']['DIES_T']['Line A']['rows'] ?? [];
        $this->assertNotEmpty($dtDetail);
        $this->assertNotNull($dtDetail[0]['image_url']);
        $this->assertStringContainsString($downtime->image_path, $dtDetail[0]['image_url']);
    }
}
