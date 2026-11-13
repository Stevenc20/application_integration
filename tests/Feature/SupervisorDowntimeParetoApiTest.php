<?php

use App\Models\Downtime;
use App\Models\JobMaster;
use App\Models\LineMaster;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupervisorDowntimeParetoApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $supervisor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->supervisor = User::create([
            'name' => 'Supervisor Test',
            'username' => 'spv_test',
            'nrp' => '123456',
            'role' => 'supervisor',
            'password' => bcrypt('password'),
        ]);
    }

    public function test_downtime_type_api_returns_correct_pareto_structure(): void
    {
        $line = LineMaster::create([
            'line_name' => 'Line 1',
            'line_code' => 'L1',
        ]);
        $job = JobMaster::create([
            'line' => $line->line_name,
            'job_number' => 'JOB-001',
            'job_name' => 'Part Test',
            'target_qty' => 100,
        ]);

        $now = Carbon::parse('2026-11-15 10:00:00');

        // Create downtimes across valid categories
        Downtime::create([
            'job_master_id' => $job->id,
            'jenis_downtime' => 'mesin',
            'duration_seconds' => 6000, // 100 min (50%)
            'start_time' => $now,
        ]);

        Downtime::create([
            'job_master_id' => $job->id,
            'jenis_downtime' => 'Dies',
            'duration_seconds' => 3600, // 60 min (30%)
            'start_time' => $now,
        ]);

        Downtime::create([
            'job_master_id' => $job->id,
            'jenis_downtime' => 'other', // Singular should map to Others
            'duration_seconds' => 1200, // 20 min (10%)
            'start_time' => $now,
        ]);

        Downtime::create([
            'job_master_id' => $job->id,
            'jenis_downtime' => 'tryout', // Should map to Try Out
            'duration_seconds' => 1200, // 20 min (10%)
            'start_time' => $now,
        ]);

        // Excluded planned activities
        Downtime::create([
            'job_master_id' => $job->id,
            'jenis_downtime' => 'dandori',
            'duration_seconds' => 2400,
            'start_time' => $now,
        ]);

        Downtime::create([
            'job_master_id' => $job->id,
            'jenis_downtime' => 'break time',
            'duration_seconds' => 3600,
            'start_time' => $now,
        ]);

        $response = $this->actingAs($this->supervisor)
            ->getJson('/supervisor/api/grafik/downtime-type?month=2026-11');

        $response->assertStatus(200);

        $data = $response->json();

        // Verify grand totals: 100 + 60 + 20 + 20 = 200 min
        $this->assertEquals(200.0, $data['totalMinutes']);
        $this->assertEquals(4, $data['totalEvents']);
        $this->assertEquals('Mesin', $data['topCategory']);

        // Verify Pareto order descending
        $this->assertEquals(['Mesin', 'Dies', 'Others', 'Try Out'], $data['labels']);
        $this->assertEquals([100.0, 60.0, 20.0, 20.0], $data['data']);
        $this->assertEquals([50.0, 30.0, 10.0, 10.0], $data['percentages']);
        $this->assertEquals([50.0, 80.0, 90.0, 100.0], $data['cumulative']);

        // 80% boundary: Mesin (50%) + Dies (30%) = 80%
        $this->assertEquals(['Mesin', 'Dies'], $data['top_categories']);

        // Items array validation
        $this->assertCount(4, $data['items']);
        $this->assertTrue($data['items'][0]['in_pareto_80']);
        $this->assertTrue($data['items'][1]['in_pareto_80']);
        $this->assertFalse($data['items'][2]['in_pareto_80']);
        $this->assertFalse($data['items'][3]['in_pareto_80']);
    }

    public function test_downtime_type_api_handles_empty_month_cleanly(): void
    {
        $response = $this->actingAs($this->supervisor)
            ->getJson('/supervisor/api/grafik/downtime-type?month=2025-01');

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertEquals(0, $data['totalMinutes']);
        $this->assertEquals(0, $data['totalEvents']);
        $this->assertEquals('-', $data['topCategory']);
        $this->assertEmpty($data['labels']);
        $this->assertEmpty($data['items']);
    }
}
