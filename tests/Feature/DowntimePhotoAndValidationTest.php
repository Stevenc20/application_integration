<?php

namespace Tests\Feature;

use App\Models\Downtime;
use App\Models\JobMaster;
use App\Models\LineMaster;
use App\Models\ProductionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class DowntimePhotoAndValidationTest extends TestCase
{
    use RefreshDatabase;

    protected JobMaster $job;
    protected Downtime $downtime;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'operator',
        ]);
        $this->actingAs($this->user);

        $line = LineMaster::updateOrCreate(['line_code' => 'PA'], ['line_name' => 'Line A', 'status' => 'active']);

        $plan = ProductionPlan::create([
            'line_master_id' => $line->id,
            'plan_date' => '2026-10-28',
            'shift_name' => 'Shift Pagi',
            'press_name' => 'Line A',
            'row_no' => 1,
            'row_type' => 'job',
            'job_no' => 'AES-DT-01',
            'job_master' => 'AES-DT-01',
            'plan' => 100,
            'status' => 'running',
        ]);

        $this->job = JobMaster::create([
            'job_number' => 'AES-DT-01-' . $plan->id,
            'job_name' => 'Part DT',
            'line' => 'Line A',
            'status' => 'running',
            'target_qty' => 100,
        ]);

        $this->downtime = Downtime::create([
            'job_master_id' => $this->job->id,
            'jenis_downtime' => 'downtime',
            'problem' => '-',
            'start_time' => now()->subMinutes(10),
        ]);
    }

    public function test_updating_downtime_saves_part_and_tooling_photos_safely_and_creates_directory(): void
    {
        $partTmp = tempnam(sys_get_temp_dir(), 'test_part');
        file_put_contents($partTmp, 'fake-part-image-content');
        $partFile = new UploadedFile($partTmp, 'part_evidence.jpg', 'image/jpeg', null, true);

        $toolingTmp = tempnam(sys_get_temp_dir(), 'test_tooling');
        file_put_contents($toolingTmp, 'fake-tooling-image-content');
        $toolingFile = new UploadedFile($toolingTmp, 'tooling_evidence.png', 'image/png', null, true);

        $response = $this->postJson("/operational/downtime/{$this->downtime->id}/update", [
            '_method' => 'PUT',
            'jenis_downtime' => 'mesin',
            'problem' => 'SENSOR MACET',
            'penyebab' => 'Debu menumpuk',
            'action' => 'Pembersihan sensor',
            'pic' => 'Budi',
            'foto_part' => $partFile,
            'foto_tooling' => $toolingFile,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->downtime->refresh();
        $this->assertEquals('mesin', $this->downtime->jenis_downtime);
        $this->assertEquals('SENSOR MACET', $this->downtime->problem);
        $this->assertNotNull($this->downtime->image_path_part);
        $this->assertNotNull($this->downtime->image_path_tooling);
        $this->assertEquals($this->downtime->image_path_part, $this->downtime->image_path);

        $absPartPath = public_path('uploads/' . $this->downtime->image_path_part);
        $absToolingPath = public_path('uploads/' . $this->downtime->image_path_tooling);

        $this->assertFileExists($absPartPath);
        $this->assertFileExists($absToolingPath);

        // Cleanup test images
        @unlink($absPartPath);
        @unlink($absToolingPath);
        @rmdir(dirname($absPartPath));
    }

    public function test_updating_downtime_allows_empty_photos_when_closing_or_filling_later(): void
    {
        $response = $this->postJson("/operational/downtime/{$this->downtime->id}/update", [
            '_method' => 'PUT',
            'jenis_downtime' => 'dies',
            'problem' => 'DIES GORES',
            'penyebab' => 'Baut kendor',
            'action' => 'Kencangkan baut',
            'pic' => 'Agus',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->downtime->refresh();
        $this->assertEquals('dies', $this->downtime->jenis_downtime);
        $this->assertNull($this->downtime->image_path_part);
        $this->assertNull($this->downtime->image_path_tooling);
    }
}
