<?php

namespace Tests\Feature;

use App\Models\DailyProduction;
use App\Models\Downtime;
use App\Models\JobMaster;
use App\Models\ProductionSession;
use App\Services\ProductionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ConcurrencyDowntimeHardeningTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'cache.default' => 'array',
            'session.driver' => 'array'
        ]);

        \Illuminate\Support\Facades\Schema::dropAllTables();

        \Illuminate\Support\Facades\Schema::create('job_masters', function ($t) {
            $t->id();
            $t->string('job_number')->unique();
            $t->string('job_name')->nullable();
            $t->string('line')->nullable();
            $t->string('status')->default('pending');
            $t->integer('target_qty')->default(0);
            $t->integer('capacity')->default(0);
            $t->integer('sequence_no')->default(0);
            $t->timestamp('started_at')->nullable();
            $t->timestamp('finished_at')->nullable();
            $t->timestamps();
        });

        \Illuminate\Support\Facades\Schema::create('production_sessions', function ($t) {
            $t->id();
            $t->unsignedBigInteger('job_master_id');
            $t->date('work_date');
            $t->string('status')->default('running');
            $t->timestamp('start_time')->nullable();
            $t->timestamp('pause_time')->nullable();
            $t->timestamp('finish_time')->nullable();
            $t->integer('total_seconds')->default(0);
            $t->timestamps();
        });

        \Illuminate\Support\Facades\Schema::create('daily_productions', function ($t) {
            $t->id();
            $t->unsignedBigInteger('job_master_id');
            $t->date('work_date');
            $t->string('line')->nullable();
            $t->string('shift')->nullable();
            $t->integer('target_qty')->default(0);
            $t->integer('actual_qty')->default(0);
            $t->integer('actual_ok')->default(0);
            $t->integer('actual_repair')->default(0);
            $t->integer('actual_reject')->default(0);
            $t->integer('repair_qty')->default(0);
            $t->integer('reject_qty')->default(0);
            $t->integer('runtime_seconds')->default(0);
            $t->integer('downtime_seconds')->default(0);
            $t->decimal('efficiency', 5, 2)->default(0);
            $t->text('remarks')->nullable();
            $t->unsignedBigInteger('saved_by')->nullable();
            $t->string('status')->default('complete');
            $t->timestamps();
        });

        \Illuminate\Support\Facades\Schema::create('downtimes', function ($t) {
            $t->id();
            $t->unsignedBigInteger('job_master_id');
            $t->string('jenis_downtime');
            $t->text('problem')->nullable();
            $t->text('penyebab')->nullable();
            $t->text('action')->nullable();
            $t->string('pic')->nullable();
            $t->timestamp('start_time')->nullable();
            $t->timestamp('finish_time')->nullable();
            $t->integer('duration_seconds')->nullable();
            $t->string('source')->nullable();
            $t->timestamps();
        });

        \Illuminate\Support\Facades\Schema::create('hambatan_jalur', function ($t) {
            $t->id();
            $t->unsignedBigInteger('downtime_id')->nullable();
            $t->string('line_name')->nullable();
            $t->string('mesin')->nullable();
            $t->string('job_no')->nullable();
            $t->string('nama_part')->nullable();
            $t->string('jenis_hambatan')->nullable();
            $t->string('sub_jenis')->nullable();
            $t->text('problem')->nullable();
            $t->text('penyebab')->nullable();
            $t->text('penanggulangan')->nullable();
            $t->string('pic_hambatan')->nullable();
            $t->timestamp('waktu')->nullable();
            $t->string('status')->default('open');
            $t->timestamps();
        });
    }

    public function test_find01_two_concurrent_auto_break_start_requests_are_idempotent()
    {
        $job = JobMaster::create([
            'job_number' => 'JOB-A-01',
            'job_name' => 'AES-028',
            'line' => 'Press A',
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

        $service = app(ProductionService::class);

        // Simulate Tab 1 calling startDowntime for Auto Break
        $dt1 = $service->startDowntime($job->id, [
            'jenis_downtime' => 'break time',
            'problem' => 'AUTO BREAK',
            'penyebab' => '-',
            'action' => '-',
            'pic' => 'AUTO BREAK',
        ]);

        // Simulate Tab 2 concurrently calling startDowntime for the same Auto Break
        $dt2 = $service->startDowntime($job->id, [
            'jenis_downtime' => 'break time',
            'problem' => 'AUTO BREAK',
            'penyebab' => '-',
            'action' => '-',
            'pic' => 'AUTO BREAK',
        ]);

        $this->assertEquals($dt1->id, $dt2->id);
        $this->assertEquals(1, Downtime::where('job_master_id', $job->id)->where('jenis_downtime', 'break time')->count());
        $this->assertNull($dt1->finish_time);
    }

    public function test_manual_downtime_is_not_overwritten_by_auto_break()
    {
        $job = JobMaster::create([
            'job_number' => 'JOB-A-02',
            'job_name' => 'AES-028',
            'line' => 'Press A',
            'status' => 'running',
            'started_at' => now()->subMinutes(10)
        ]);

        $service = app(ProductionService::class);

        // Operator starts manual downtime 'mesin'
        $manualDt = $service->startDowntime($job->id, [
            'jenis_downtime' => 'mesin',
            'problem' => 'Sensor Error',
            'penyebab' => 'Kotor',
            'action' => 'Bersihkan',
            'pic' => 'TEKNISI',
        ]);

        $this->assertEquals('mesin', $manualDt->jenis_downtime);
        $this->assertNull($manualDt->finish_time);

        // Auto Break triggers while manual downtime is active
        $autoBreakDt = $service->startDowntime($job->id, [
            'jenis_downtime' => 'break time',
            'problem' => 'AUTO BREAK',
            'penyebab' => '-',
            'action' => '-',
            'pic' => 'AUTO BREAK',
        ]);

        // Guard returns null: Auto Break is NOT started, manual downtime is NOT altered
        $this->assertNull($autoBreakDt);
        $this->assertEquals(0, Downtime::where('job_master_id', $job->id)->where('jenis_downtime', 'break time')->count());
        $this->assertEquals(1, Downtime::where('job_master_id', $job->id)->count());
        $this->assertNull(Downtime::find($manualDt->id)->finish_time);
    }

    public function test_find02_night_shift_crossing_midnight_preserves_work_date_and_aggregates_all_downtimes()
    {
        $workDate = '2026-10-12';
        $job = JobMaster::create([
            'job_number' => 'JOB-NIGHT-01',
            'job_name' => 'AES-029',
            'line' => 'Press A',
            'status' => 'running',
            'started_at' => Carbon::parse('2026-10-12 21:00:00')
        ]);

        // Session created with work_date 2026-10-12
        ProductionSession::create([
            'job_master_id' => $job->id,
            'work_date' => $workDate,
            'status' => 'running',
            'start_time' => Carbon::parse('2026-10-12 21:00:00'),
            'total_seconds' => 7200
        ]);

        $service = app(ProductionService::class);

        // Downtime 1 started before midnight (23:30) and finished at 23:50 (1200 seconds)
        $dt1 = Downtime::create([
            'job_master_id' => $job->id,
            'jenis_downtime' => 'mesin',
            'problem' => 'Motor trip',
            'start_time' => Carbon::parse('2026-10-12 23:30:00'),
            'finish_time' => Carbon::parse('2026-10-12 23:50:00'),
            'duration_seconds' => 1200
        ]);

        // Downtime 2 started after midnight on next calendar day (2026-10-13 01:10:00)
        Carbon::setTestNow(Carbon::parse('2026-10-13 01:10:00'));
        $dt2 = $service->startDowntime($job->id, [
            'jenis_downtime' => 'dies',
            'problem' => 'Dies macet',
            'penyebab' => 'Pelumasan kurang',
            'action' => 'Semprot oli',
            'pic' => 'OPERATOR'
        ]);

        // Finish downtime 2 at 01:30 (1200 seconds)
        Carbon::setTestNow(Carbon::parse('2026-10-13 01:30:00'));
        $closedDt2 = $service->finishDowntime($dt2->id);

        $this->assertEquals(1200, $closedDt2->duration_seconds);

        // Verify DailyProduction record is tied strictly to the work_date of the session (2026-10-12), NOT the calendar day 2026-10-13
        $daily = DailyProduction::where('job_master_id', $job->id)->first();
        $this->assertNotNull($daily);
        $this->assertEquals($workDate, $daily->work_date);

        // Total downtime must aggregate both dt1 (1200s) + dt2 (1200s) = 2400 seconds
        $this->assertEquals(2400, $daily->downtime_seconds);

        Carbon::setTestNow(); // reset
    }

    public function test_finish_downtime_retry_is_idempotent()
    {
        $job = JobMaster::create([
            'job_number' => 'JOB-RETRY-01',
            'job_name' => 'AES-030',
            'line' => 'Press A',
            'status' => 'running',
            'started_at' => now()->subMinutes(15)
        ]);

        ProductionSession::create([
            'job_master_id' => $job->id,
            'work_date' => now()->toDateString(),
            'status' => 'running',
            'start_time' => now()->subMinutes(15),
            'total_seconds' => 900
        ]);

        $service = app(ProductionService::class);

        $dt = $service->startDowntime($job->id, [
            'jenis_downtime' => 'break time',
            'problem' => 'AUTO BREAK',
            'penyebab' => '-',
            'action' => '-',
            'pic' => 'AUTO BREAK',
        ]);

        $res1 = $service->finishDowntime($dt->id);
        $duration1 = $res1->duration_seconds;

        // Second retry call for finishDowntime
        $res2 = $service->finishDowntime($dt->id);

        $this->assertEquals($res1->id, $res2->id);
        $this->assertEquals($duration1, $res2->duration_seconds);
        $this->assertEquals(1, Downtime::where('job_master_id', $job->id)->count());
    }

    public function test_isolation_between_lines_a_b_c()
    {
        $jobA = JobMaster::create(['job_number' => 'JOB-LINE-A', 'job_name' => 'PART-A', 'line' => 'Press A', 'status' => 'running']);
        $jobB = JobMaster::create(['job_number' => 'JOB-LINE-B', 'job_name' => 'PART-B', 'line' => 'Press B', 'status' => 'running']);
        $jobC = JobMaster::create(['job_number' => 'JOB-LINE-C', 'job_name' => 'PART-C', 'line' => 'Press C', 'status' => 'running']);

        foreach ([$jobA, $jobB, $jobC] as $j) {
            ProductionSession::create([
                'job_master_id' => $j->id,
                'work_date' => now()->toDateString(),
                'status' => 'running',
                'start_time' => now(),
                'total_seconds' => 100
            ]);
        }

        $service = app(ProductionService::class);

        $dtA = $service->startDowntime($jobA->id, ['jenis_downtime' => 'break time', 'problem' => 'BREAK A']);
        $dtB = $service->startDowntime($jobB->id, ['jenis_downtime' => 'mesin', 'problem' => 'MT LINE B']);

        // Check Job A downtime
        $this->assertEquals(1, Downtime::where('job_master_id', $jobA->id)->count());
        $this->assertEquals('break time', Downtime::where('job_master_id', $jobA->id)->first()->jenis_downtime);

        // Check Job B downtime
        $this->assertEquals(1, Downtime::where('job_master_id', $jobB->id)->count());
        $this->assertEquals('mesin', Downtime::where('job_master_id', $jobB->id)->first()->jenis_downtime);

        // Line C must have 0 downtimes
        $this->assertEquals(0, Downtime::where('job_master_id', $jobC->id)->count());

        // Close A, verify B and C are untouched
        $service->finishDowntime($dtA->id);
        $this->assertNotNull(Downtime::find($dtA->id)->finish_time);
        $this->assertNull(Downtime::find($dtB->id)->finish_time);
    }

    public function test_multi_session_downtimes_are_strictly_scoped_to_their_respective_work_dates()
    {
        $job = JobMaster::create(['job_number' => 'JOB-MULTI-SESS', 'job_name' => 'MULTI SESS PART', 'line' => 'Press A', 'status' => 'running']);

        // Session 1: Day 1 (2026-10-10)
        ProductionSession::create([
            'job_master_id' => $job->id,
            'work_date' => '2026-10-10',
            'status' => 'finished',
            'start_time' => Carbon::parse('2026-10-10 08:00:00'),
            'total_seconds' => 3600
        ]);
        // Downtime on Day 1: 500s
        Downtime::create([
            'job_master_id' => $job->id,
            'jenis_downtime' => 'mesin',
            'problem' => 'DT Day 1',
            'start_time' => Carbon::parse('2026-10-10 10:00:00'),
            'finish_time' => Carbon::parse('2026-10-10 10:08:20'),
            'duration_seconds' => 500,
            'created_at' => Carbon::parse('2026-10-10 10:00:00')
        ]);
        DailyProduction::create([
            'job_master_id' => $job->id,
            'work_date' => '2026-10-10',
            'downtime_seconds' => 500
        ]);

        // Session 2: Day 2 (2026-10-11)
        ProductionSession::create([
            'job_master_id' => $job->id,
            'work_date' => '2026-10-11',
            'status' => 'running',
            'start_time' => Carbon::parse('2026-10-11 08:00:00'),
            'total_seconds' => 1800
        ]);

        $service = app(ProductionService::class);

        // Start and finish downtime on Day 2: 300s
        Carbon::setTestNow(Carbon::parse('2026-10-11 09:00:00'));
        $dtDay2 = $service->startDowntime($job->id, [
            'jenis_downtime' => 'material',
            'problem' => 'DT Day 2'
        ]);

        Carbon::setTestNow(Carbon::parse('2026-10-11 09:05:00'));
        $service->finishDowntime($dtDay2->id);

        // Verify Day 2 DailyProduction has ONLY 300s, not 500s + 300s = 800s
        $dailyDay2 = DailyProduction::where('job_master_id', $job->id)->where('work_date', '2026-10-11')->first();
        $this->assertNotNull($dailyDay2);
        $this->assertEquals(300, $dailyDay2->downtime_seconds);

        // Verify Day 1 DailyProduction remains 500s
        $dailyDay1 = DailyProduction::where('job_master_id', $job->id)->where('work_date', '2026-10-10')->first();
        $this->assertEquals(500, $dailyDay1->downtime_seconds);

        Carbon::setTestNow();
    }

    public function test_partial_retry_preserves_existing_finish_time_and_calculates_duration()
    {
        $job = JobMaster::create(['job_number' => 'JOB-PARTIAL-1', 'job_name' => 'PARTIAL 1', 'line' => 'Press A', 'status' => 'running']);
        ProductionSession::create([
            'job_master_id' => $job->id,
            'work_date' => '2026-10-12',
            'status' => 'running',
            'start_time' => Carbon::parse('2026-10-12 10:00:00'),
            'total_seconds' => 3600
        ]);

        $startTime = Carbon::parse('2026-10-12 10:10:00');
        $fixedFinishTime = Carbon::parse('2026-10-12 10:25:00'); // 15 mins = 900 seconds

        // Downtime where finish_time was set by an interrupted request, but duration_seconds is NULL
        $dt = Downtime::create([
            'job_master_id' => $job->id,
            'jenis_downtime' => 'mesin',
            'problem' => 'Partial test 1',
            'start_time' => $startTime,
            'finish_time' => $fixedFinishTime,
            'duration_seconds' => null,
        ]);

        // Simulated later retry call (current time is 11:30:00)
        Carbon::setTestNow(Carbon::parse('2026-10-12 11:30:00'));
        $service = app(ProductionService::class);
        $res = $service->finishDowntime($dt->id);

        // Verify finish_time was NOT overwritten with now() (11:30:00)
        $this->assertEquals($fixedFinishTime->toDateTimeString(), Carbon::parse($res->finish_time)->toDateTimeString());
        // Verify duration was correctly computed between start_time and existing finish_time (900 seconds)
        $this->assertEquals(900, $res->duration_seconds);

        $daily = DailyProduction::where('job_master_id', $job->id)->where('work_date', '2026-10-12')->first();
        $this->assertEquals(900, $daily->downtime_seconds);

        Carbon::setTestNow();
    }

    public function test_partial_retry_preserves_existing_duration_seconds_and_backfills_finish_time()
    {
        $job = JobMaster::create(['job_number' => 'JOB-PARTIAL-2', 'job_name' => 'PARTIAL 2', 'line' => 'Press A', 'status' => 'running']);
        ProductionSession::create([
            'job_master_id' => $job->id,
            'work_date' => '2026-10-12',
            'status' => 'running',
            'start_time' => Carbon::parse('2026-10-12 10:00:00'),
            'total_seconds' => 3600
        ]);

        $startTime = Carbon::parse('2026-10-12 10:10:00');
        $fixedDuration = 600; // 10 minutes

        // Downtime where duration_seconds was set, but finish_time is NULL
        $dt = Downtime::create([
            'job_master_id' => $job->id,
            'jenis_downtime' => 'dies',
            'problem' => 'Partial test 2',
            'start_time' => $startTime,
            'finish_time' => null,
            'duration_seconds' => $fixedDuration,
        ]);

        Carbon::setTestNow(Carbon::parse('2026-10-12 11:45:00'));
        $service = app(ProductionService::class);
        $res = $service->finishDowntime($dt->id);

        $this->assertEquals(600, $res->duration_seconds);
        $expectedFinish = $startTime->copy()->addSeconds(600);
        $this->assertEquals($expectedFinish->toDateTimeString(), Carbon::parse($res->finish_time)->toDateTimeString());

        Carbon::setTestNow();
    }

    public function test_downtime_boundary_scenarios_for_night_and_morning()
    {
        // Job on work_date 2026-10-12
        $job = JobMaster::create(['job_number' => 'JOB-BOUND-01', 'job_name' => 'BOUNDARY TEST', 'line' => 'Press A', 'status' => 'running']);
        ProductionSession::create([
            'job_master_id' => $job->id,
            'work_date' => '2026-10-12',
            'status' => 'running',
            'start_time' => Carbon::parse('2026-10-12 21:00:00'),
            'total_seconds' => 7200
        ]);

        $service = app(ProductionService::class);

        // Scenario 1: Downtime 23:30 - 01:30 (crosses 00:00) -> 7200s
        $dt1 = Downtime::create([
            'job_master_id' => $job->id,
            'jenis_downtime' => 'mesin',
            'start_time' => Carbon::parse('2026-10-12 23:30:00'),
        ]);

        // Scenario 2: Downtime 06:50 - 07:20 (starts before 07:00, ends after 07:00) -> 1800s
        $dt2 = Downtime::create([
            'job_master_id' => $job->id,
            'jenis_downtime' => 'dies',
            'start_time' => Carbon::parse('2026-10-13 06:50:00'),
        ]);

        // Finish downtime triggers aggregation
        Carbon::setTestNow(Carbon::parse('2026-10-13 01:30:00'));
        $service->finishDowntime($dt1->id);

        Carbon::setTestNow(Carbon::parse('2026-10-13 07:20:00'));
        $service->finishDowntime($dt2->id);

        Carbon::setTestNow();

        $daily = DailyProduction::where('job_master_id', $job->id)->where('work_date', '2026-10-12')->first();
        $this->assertNotNull($daily);
        // Total duration is 7200 + 1800 = 9000 seconds
        $this->assertEquals(9000, $daily->downtime_seconds);
    }
}
