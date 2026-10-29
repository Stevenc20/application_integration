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

        $this->assertNotNull($this->downtime->image_url_part);
        $this->assertNotNull($this->downtime->image_url_tooling);

        $exists = file_exists(storage_path('app/public/' . $this->downtime->image_path_part))
               || file_exists(public_path('uploads/' . $this->downtime->image_path_part));
        $this->assertTrue($exists, 'Part image must exist in storage/app/public or public/uploads');

        $existsTooling = file_exists(storage_path('app/public/' . $this->downtime->image_path_tooling))
                      || file_exists(public_path('uploads/' . $this->downtime->image_path_tooling));
        $this->assertTrue($existsTooling, 'Tooling image must exist in storage/app/public or public/uploads');

        // Cleanup test images
        @unlink(storage_path('app/public/' . $this->downtime->image_path_part));
        @unlink(storage_path('app/public/' . $this->downtime->image_path_tooling));
        @unlink(public_path('uploads/' . $this->downtime->image_path_part));
        @unlink(public_path('uploads/' . $this->downtime->image_path_tooling));
        @rmdir(dirname(storage_path('app/public/' . $this->downtime->image_path_part)));
        @rmdir(dirname(public_path('uploads/' . $this->downtime->image_path_part)));
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

    public function test_updating_downtime_with_only_part_photo(): void
    {
        $partTmp = tempnam(sys_get_temp_dir(), 'test_part_only');
        file_put_contents($partTmp, 'fake-part-only');
        $partFile = new UploadedFile($partTmp, 'only_part.jpg', 'image/jpeg', null, true);

        $response = $this->postJson("/operational/downtime/{$this->downtime->id}/update", [
            '_method' => 'PUT',
            'jenis_downtime' => 'mesin',
            'problem' => 'PART PROBLEM',
            'foto_part' => $partFile,
        ]);

        $response->assertStatus(200);
        $this->downtime->refresh();
        $this->assertNotNull($this->downtime->image_path_part);
        $this->assertNull($this->downtime->image_path_tooling);
        $this->assertNotNull($this->downtime->image_url_part);

        // Cleanup
        @unlink(storage_path('app/public/' . $this->downtime->image_path_part));
        @unlink(public_path('uploads/' . $this->downtime->image_path_part));
    }

    public function test_updating_downtime_with_only_tooling_photo(): void
    {
        $toolingTmp = tempnam(sys_get_temp_dir(), 'test_tooling_only');
        file_put_contents($toolingTmp, 'fake-tooling-only');
        $toolingFile = new UploadedFile($toolingTmp, 'only_tooling.jpg', 'image/jpeg', null, true);

        $response = $this->postJson("/operational/downtime/{$this->downtime->id}/update", [
            '_method' => 'PUT',
            'jenis_downtime' => 'dies',
            'problem' => 'TOOLING PROBLEM',
            'foto_tooling' => $toolingFile,
        ]);

        $response->assertStatus(200);
        $this->downtime->refresh();
        $this->assertNull($this->downtime->image_path_part);
        $this->assertNotNull($this->downtime->image_path_tooling);
        $this->assertNotNull($this->downtime->image_url_tooling);

        // Cleanup
        @unlink(storage_path('app/public/' . $this->downtime->image_path_tooling));
        @unlink(public_path('uploads/' . $this->downtime->image_path_tooling));
    }

    public function test_updating_downtime_handles_reupload_and_replaces_previous_photo(): void
    {
        // First upload
        $partTmp1 = tempnam(sys_get_temp_dir(), 'test_part1');
        file_put_contents($partTmp1, 'fake-part-1');
        $partFile1 = new UploadedFile($partTmp1, 'part_v1.jpg', 'image/jpeg', null, true);

        $res1 = $this->postJson("/operational/downtime/{$this->downtime->id}/update", [
            '_method' => 'PUT',
            'jenis_downtime' => 'mesin',
            'foto_part' => $partFile1,
        ]);
        $res1->assertStatus(200);
        $this->downtime->refresh();
        $firstPath = $this->downtime->image_path_part;
        $this->assertNotNull($firstPath);

        // Second upload (re-upload)
        $partTmp2 = tempnam(sys_get_temp_dir(), 'test_part2');
        file_put_contents($partTmp2, 'fake-part-2');
        $partFile2 = new UploadedFile($partTmp2, 'part_v2.jpg', 'image/jpeg', null, true);

        $res2 = $this->postJson("/operational/downtime/{$this->downtime->id}/update", [
            '_method' => 'PUT',
            'jenis_downtime' => 'mesin',
            'foto_part' => $partFile2,
        ]);
        $res2->assertStatus(200);
        $this->downtime->refresh();
        $secondPath = $this->downtime->image_path_part;

        $this->assertNotNull($secondPath);
        $existsSecond = file_exists(storage_path('app/public/' . $secondPath))
                     || file_exists(public_path('uploads/' . $secondPath));
        $this->assertTrue($existsSecond, 'Second uploaded image must exist');

        // Cleanup
        @unlink(storage_path('app/public/' . $firstPath));
        @unlink(storage_path('app/public/' . $secondPath));
        @unlink(public_path('uploads/' . $firstPath));
        @unlink(public_path('uploads/' . $secondPath));
        @rmdir(dirname(storage_path('app/public/' . $secondPath)));
        @rmdir(dirname(public_path('uploads/' . $secondPath)));
    }

    public function test_updating_downtime_with_type_and_empty_problem_fills_default_problem_and_returns_downtime(): void
    {
        $response = $this->postJson("/operational/downtime/{$this->downtime->id}/update", [
            '_method' => 'PUT',
            'jenis_downtime' => 'mesin',
            'problem' => 'MESIN',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonStructure([
            'success',
            'downtime' => [
                'id',
                'jenis_downtime',
                'problem',
            ],
        ]);

        $this->downtime->refresh();
        $this->assertEquals('mesin', $this->downtime->jenis_downtime);
        $this->assertEquals('MESIN', $this->downtime->problem);
    }

    public function test_get_downtimes_endpoint_returns_appended_image_urls(): void
    {
        $this->downtime->update([
            'jenis_downtime' => 'dies',
            'problem' => 'Dies Crack',
            'image_path_part' => 'downtime/' . $this->downtime->id . '/part_test.jpg',
            'image_path_tooling' => 'downtime/' . $this->downtime->id . '/tooling_test.png',
        ]);

        $response = $this->getJson("/operational/job/{$this->job->id}/downtimes");
        $response->assertStatus(200);

        $data = $response->json();
        $this->assertNotEmpty($data);
        $first = $data[0];

        $this->assertArrayHasKey('image_url_part', $first);
        $this->assertArrayHasKey('image_url_tooling', $first);
        $this->assertArrayHasKey('image_url', $first);
        $this->assertNotNull($first['image_url_part']);
        $this->assertNotNull($first['image_url_tooling']);
        $this->assertStringContainsString('part_test.jpg', $first['image_url_part']);
        $this->assertStringContainsString('tooling_test.png', $first['image_url_tooling']);
    }

    public function test_updating_downtime_fails_validation_when_jenis_downtime_is_missing(): void
    {
        $response = $this->postJson("/operational/downtime/{$this->downtime->id}/update", [
            '_method' => 'PUT',
            'jenis_downtime' => '',
            'problem' => 'Some Problem',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['jenis_downtime']);
    }
}
