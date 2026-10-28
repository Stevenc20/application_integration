<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\ProductionPlan;
use App\Models\MasterShift;
use App\Models\LineMaster;
use App\Models\JobMaster;
use App\Models\DailyProduction;
use App\Models\ProductionLog;
use App\Models\RepairRejectLog;
use App\Models\User;

class ShiftReviewConfirmationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        LineMaster::create(['line_code' => 'PRESS_A', 'line_name' => 'Press A']);
        MasterShift::firstOrCreate(
            ['name' => 'Shift Pagi'],
            ['planned_start_time' => '07:30', 'planned_end_time' => '21:00']
        );
        $this->user = User::factory()->create(['role' => 'teamleader']);
    }

    private function seedJobAndPlan(string $jobNo, string $date): JobMaster
    {
        $line = LineMaster::where('line_name', 'Press A')->first();
        $shiftId = MasterShift::first()->id;
        $job = JobMaster::create(['job_number' => $jobNo, 'job_name' => 'Part A', 'line' => 'Press A']);
        ProductionPlan::create([
            'line_master_id' => $line->id,
            'plan_date' => $date,
            'shift_name' => 'Shift Pagi',
            'shift_master_id' => $shiftId,
            'press_name' => 'Press A',
            'row_type' => 'job',
            'job_no' => $jobNo,
            'job_master' => 'Part A',
            'plan' => 100,
            'ok' => 45,
        ]);
        return $job;
    }

    private function seedRepair(int $jobId, string $date): void
    {
        ProductionLog::create(['job_master_id' => $jobId, 'ok_qty' => 0, 'repair_qty' => 55, 'reject_qty' => 0, 'created_at' => now()]);
        DailyProduction::updateOrCreate(
            ['job_master_id' => $jobId, 'work_date' => $date],
            ['actual_repair' => 55, 'actual_ok' => 0]
        );
    }

    private function submit(array $extra = []): \Illuminate\Testing\TestResponse
    {
        $lineId = LineMaster::where('line_name', 'Press A')->first()->id;
        $shiftId = MasterShift::first()->id;
        return $this->actingAs($this->user)
            ->postJson("/operational/shift/{$lineId}/submit", array_merge([
                'date' => now()->format('Y-m-d'),
                'shift_master_id' => $shiftId,
            ], $extra));
    }

    public function test_complete_repair_form_returns_review_then_confirms(): void
    {
        $date = now()->format('Y-m-d');
        $jm = $this->seedJobAndPlan('JOB-COMPLETE', $date);
        $this->seedRepair($jm->id, $date);
        $rr = RepairRejectLog::create([
            'job_master_id' => $jm->id,
            'type' => 'repair',
            'defect_name' => 'Ceacat',
            'qty_a' => 55,
            'area_problem' => 'sp1',
            'root_cause' => 'rc',
            'countermeasure' => 'cm',
        ]);
        \App\Models\RepairRejectImage::create([
            'repair_reject_log_id' => $rr->id,
            'image_path' => 'repair_reject/test.webp',
            'image_type' => 'before',
        ]);

        $resp = $this->submit();

        $this->assertEquals(200, $resp->status());
        $this->assertFalse($resp->json('success'));
        $this->assertTrue($resp->json('needs_review'));
        $this->assertCount(1, $resp->json('review.repair'));
        $this->assertEquals(55, $resp->json('review.repair.0.qty'));
        $this->assertEquals('ok', $resp->json('review.repair.0.status'));
        $this->assertCount(0, $resp->json('review.reject'));

        $resp2 = $this->submit(['confirm_review' => true]);
        $this->assertEquals(200, $resp2->status());
        $this->assertTrue($resp2->json('success'));
    }

    public function test_form_filled_but_no_daily_still_shows_review(): void
    {
        $date = now()->format('Y-m-d');
        $jm = $this->seedJobAndPlan('JOB-NODAILY', $date);
        ProductionLog::create(['job_master_id' => $jm->id, 'ok_qty' => 0, 'repair_qty' => 55, 'reject_qty' => 0, 'created_at' => now()]);
        $rr = RepairRejectLog::create([
            'job_master_id' => $jm->id,
            'type' => 'repair',
            'defect_name' => 'Ceacat',
            'qty_a' => 55,
            'area_problem' => 'sp1',
            'root_cause' => 'rc',
            'countermeasure' => 'cm',
        ]);
        \App\Models\RepairRejectImage::create([
            'repair_reject_log_id' => $rr->id,
            'image_path' => 'repair_reject/test.webp',
            'image_type' => 'before',
        ]);

        $resp = $this->submit();

        $this->assertEquals(200, $resp->status());
        $this->assertTrue($resp->json('needs_review'));
        $this->assertCount(1, $resp->json('review.repair'));
        $this->assertEquals(55, $resp->json('review.repair.0.logged'));
    }

    public function test_no_repair_activity_submits_directly(): void
    {
        $this->seedJobAndPlan('JOB-CLEAN', now()->format('Y-m-d'));

        $resp = $this->submit();

        $this->assertEquals(200, $resp->status());
        $this->assertArrayNotHasKey('needs_review', $resp->json());
    }
}