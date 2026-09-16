<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\JobMaster;
use App\Models\ProductionSession;
use App\Models\Downtime;
use App\Models\DailyProduction;
use App\Models\LineMaster;
use App\Services\ProductionService;
use App\Services\LineStatusService;
use Carbon\Carbon;

class BreaktimeSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        LineMaster::create(['line_name' => 'Press A', 'status' => 'active', 'urut' => 1]);
        LineMaster::create(['line_name' => 'Press B', 'status' => 'active', 'urut' => 2]);
    }

    private function createRunningJob($line = 'Press A')
    {
        $job = JobMaster::create([
            'job_number' => 'JOB-TEST-' . uniqid(),
            'line' => $line,
            'status' => 'running',
            'started_at' => now()->subMinutes(10)
        ]);

        ProductionSession::create([
            'job_master_id' => $job->id,
            'work_date' => now()->toDateString(),
            'status' => 'running',
            'start_time' => now()->subMinutes(10),
            'total_seconds' => 600
        ]);

        return $job;
    }

    public function test_resume_job_closes_active_breaktime_and_syncs_status()
    {
        $job = $this->createRunningJob();
        
        $job->update(['status' => 'paused']);
        ProductionSession::where('job_master_id', $job->id)->update(['status' => 'paused']);
        
        $downtime = Downtime::create([
            'job_master_id' => $job->id,
            'jenis_downtime' => 'break time',
            'source' => 'MANUAL',
            'start_time' => now()->subMinutes(5)
        ]);

        $statuses = LineStatusService::getStatuses(1);
        $this->assertEquals('BREAKTIME', $statuses['Press A']['label']);

        app(ProductionService::class)->resumeJob($job->id);

        $this->assertEquals('running', $job->fresh()->status);
        $this->assertNotNull($downtime->fresh()->finish_time);
        $this->assertGreaterThan(0, $downtime->fresh()->duration_seconds);

        $statuses = LineStatusService::getStatuses(1);
        $this->assertEquals('PRODUCTION', $statuses['Press A']['label']);
    }

    public function test_multi_line_isolation_when_resuming()
    {
        $jobA = $this->createRunningJob('Press A');
        $jobB = $this->createRunningJob('Press B');

        $jobA->update(['status' => 'paused']);
        $jobB->update(['status' => 'paused']);
        
        $dtA = Downtime::create(['job_master_id' => $jobA->id, 'jenis_downtime' => 'break time', 'start_time' => now()->subMinutes(5)]);
        $dtB = Downtime::create(['job_master_id' => $jobB->id, 'jenis_downtime' => 'break time', 'start_time' => now()->subMinutes(5)]);

        app(ProductionService::class)->resumeJob($jobA->id);

        $this->assertNotNull($dtA->fresh()->finish_time, 'Downtime A should be closed');
        $this->assertNull($dtB->fresh()->finish_time, 'Downtime B should remain open');

        $statuses = LineStatusService::getStatuses(1);
        $this->assertEquals('PRODUCTION', $statuses['Press A']['label']);
        $this->assertEquals('BREAKTIME', $statuses['Press B']['label']);
    }
}
