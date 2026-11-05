<?php

namespace Tests\Feature;

use App\Models\DailyProduction;
use App\Models\Dandori;
use App\Models\Downtime;
use App\Models\JobMaster;
use App\Models\ProductionLog;
use App\Models\ProductionPlan;
use App\Models\ProductionSession;
use App\Services\ProductionTimelineService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductionAnalyticsTimelineConsistencyTest extends TestCase
{
    protected ProductionTimelineService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'cache.default' => 'array',
            'session.driver' => 'array'
        ]);

        Schema::dropAllTables();

        Schema::create('job_masters', function ($t) {
            $t->id();
            $t->string('job_number')->unique();
            $t->string('job_name')->nullable();
            $t->string('line')->nullable();
            $t->string('status')->default('pending');
            $t->integer('target_qty')->default(0);
            $t->integer('capacity')->default(0);
            $t->timestamp('started_at')->nullable();
            $t->timestamp('finished_at')->nullable();
            $t->timestamps();
        });

        Schema::create('production_plans', function ($t) {
            $t->id();
            $t->string('job_no')->nullable();
            $t->string('job_master')->nullable();
            $t->string('press_name')->nullable();
            $t->string('shift_name')->nullable();
            $t->string('row_type')->default('job');
            $t->date('plan_date')->nullable();
            $t->string('start_time')->nullable();
            $t->string('finish_time')->nullable();
            $t->integer('plan')->default(0);
            $t->string('status')->default('pending');
            $t->timestamps();
        });

        Schema::create('daily_productions', function ($t) {
            $t->id();
            $t->unsignedBigInteger('job_master_id');
            $t->date('work_date');
            $t->string('line')->nullable();
            $t->string('shift')->nullable();
            $t->integer('actual_ok')->default(0);
            $t->integer('actual_repair')->default(0);
            $t->integer('actual_reject')->default(0);
            $t->integer('actual_qty')->default(0);
            $t->integer('runtime_seconds')->default(0);
            $t->integer('downtime_seconds')->default(0);
            $t->integer('target_qty')->default(0);
            $t->timestamps();
        });

        Schema::create('production_sessions', function ($t) {
            $t->id();
            $t->unsignedBigInteger('job_master_id');
            $t->date('work_date');
            $t->timestamp('start_time')->nullable();
            $t->timestamp('pause_time')->nullable();
            $t->timestamp('finish_time')->nullable();
            $t->integer('total_seconds')->default(0);
            $t->string('status')->default('running');
            $t->timestamps();
        });

        Schema::create('downtimes', function ($t) {
            $t->id();
            $t->unsignedBigInteger('job_master_id');
            $t->string('jenis_downtime')->nullable();
            $t->string('problem')->nullable();
            $t->string('penyebab')->nullable();
            $t->string('action')->nullable();
            $t->string('pic')->nullable();
            $t->string('image_path')->nullable();
            $t->string('image_path_part')->nullable();
            $t->string('image_path_tooling')->nullable();
            $t->timestamp('start_time')->nullable();
            $t->timestamp('finish_time')->nullable();
            $t->integer('duration_seconds')->default(0);
            $t->timestamps();
        });

        Schema::create('dandoris', function ($t) {
            $t->id();
            $t->unsignedBigInteger('previous_job_id')->nullable();
            $t->unsignedBigInteger('next_job_id')->nullable();
            $t->string('line')->nullable();
            $t->string('shift')->nullable();
            $t->string('activity')->nullable();
            $t->string('jenis_dandori')->nullable();
            $t->timestamp('start_time')->nullable();
            $t->timestamp('finish_time')->nullable();
            $t->decimal('duration_minutes', 8, 2)->default(0);
            $t->date('work_date')->nullable();
            $t->string('created_by')->nullable();
            $t->timestamps();
        });

        Schema::create('production_logs', function ($t) {
            $t->id();
            $t->unsignedBigInteger('job_master_id');
            $t->integer('ok_qty')->default(0);
            $t->integer('repair_qty')->default(0);
            $t->integer('reject_qty')->default(0);
            $t->timestamps();
        });

        $this->service = new ProductionTimelineService();
    }

    /**
     * 1. Production saja (Pure Production)
     */
    public function test_timeline_pure_production()
    {
        $start = Carbon::parse('2026-11-05 08:00:00');
        $finish = Carbon::parse('2026-11-05 10:00:00');

        $job = JobMaster::create([
            'job_number' => 'JOB-PROD-001-1',
            'job_name' => 'Part Test Prod Only',
            'line' => 'PRESS A',
            'status' => 'complete',
            'started_at' => $start,
            'finished_at' => $finish,
        ]);

        $job->load(['downtimes', 'dandoris']);

        $plan = ProductionPlan::create([
            'job_no' => 'JOB-PROD-001',
            'plan_date' => '2026-11-05',
            'start_time' => '08:00',
            'finish_time' => '10:00',
            'plan' => 500,
        ]);

        $timeline = $this->service->buildJobTimeline($job, $plan, '2026-11-05', $finish);

        $this->assertCount(1, $timeline);
        $this->assertEquals('production', $timeline[0]['type']);
        $this->assertEquals('Production', $timeline[0]['label']);
        $this->assertEquals(7200, $timeline[0]['duration_sec']);
        $this->assertEquals('2026-11-05 08:00:00', $timeline[0]['start']->format('Y-m-d H:i:s'));
        $this->assertEquals('2026-11-05 10:00:00', $timeline[0]['end']->format('Y-m-d H:i:s'));
    }

    /**
     * 2. Production + Dandori
     */
    public function test_timeline_production_and_dandori()
    {
        $dandoriStart = Carbon::parse('2026-11-05 07:30:00');
        $dandoriEnd = Carbon::parse('2026-11-05 08:00:00');
        $prodStart = Carbon::parse('2026-11-05 08:00:00');
        $prodEnd = Carbon::parse('2026-11-05 09:30:00');

        $job = JobMaster::create([
            'job_number' => 'JOB-DANDORI-001-1',
            'job_name' => 'Part Dandori + Prod',
            'status' => 'complete',
            'started_at' => $prodStart,
            'finished_at' => $prodEnd,
        ]);

        Dandori::create([
            'next_job_id' => $job->id,
            'activity' => 'Dies Setup',
            'jenis_dandori' => 'setup',
            'start_time' => $dandoriStart,
            'finish_time' => $dandoriEnd,
            'duration_minutes' => 30,
            'work_date' => '2026-11-05',
        ]);

        $job->load(['downtimes', 'dandoris']);

        $plan = ProductionPlan::create([
            'job_no' => 'JOB-DANDORI-001',
            'plan_date' => '2026-11-05',
            'start_time' => '07:30',
            'finish_time' => '09:30',
            'plan' => 400,
        ]);

        $timeline = $this->service->buildJobTimeline($job, $plan, '2026-11-05', $prodEnd);

        $this->assertCount(2, $timeline);
        $this->assertEquals('dandori', $timeline[0]['type']);
        $this->assertEquals(1800, $timeline[0]['duration_sec']);
        $this->assertEquals('production', $timeline[1]['type']);
        $this->assertEquals(5400, $timeline[1]['duration_sec']);
    }

    /**
     * 3. Multiple Dandori (tidak saling menimpa atau hilang)
     */
    public function test_timeline_multiple_dandori()
    {
        $d1Start = Carbon::parse('2026-11-05 07:00:00');
        $d1End = Carbon::parse('2026-11-05 07:30:00');
        $d2Start = Carbon::parse('2026-11-05 07:30:00');
        $d2End = Carbon::parse('2026-11-05 08:00:00');
        $prodStart = Carbon::parse('2026-11-05 08:00:00');
        $prodEnd = Carbon::parse('2026-11-05 10:00:00');

        $job = JobMaster::create([
            'job_number' => 'JOB-MULTI-DANDORI-1',
            'status' => 'complete',
            'started_at' => $prodStart,
            'finished_at' => $prodEnd,
        ]);

        Dandori::create([
            'next_job_id' => $job->id,
            'activity' => 'Dandori Dies 1',
            'jenis_dandori' => 'setup',
            'start_time' => $d1Start,
            'finish_time' => $d1End,
            'duration_minutes' => 30,
        ]);

        Dandori::create([
            'next_job_id' => $job->id,
            'activity' => 'Dandori Dies 2',
            'jenis_dandori' => 'setup',
            'start_time' => $d2Start,
            'finish_time' => $d2End,
            'duration_minutes' => 30,
        ]);

        $job->load(['downtimes', 'dandoris']);

        $timeline = $this->service->buildJobTimeline($job, null, '2026-11-05', $prodEnd);

        $this->assertCount(3, $timeline);
        $this->assertEquals('dandori', $timeline[0]['type']);
        $this->assertEquals(1800, $timeline[0]['duration_sec']);
        $this->assertEquals('dandori', $timeline[1]['type']);
        $this->assertEquals(1800, $timeline[1]['duration_sec']);
        $this->assertEquals('production', $timeline[2]['type']);
        $this->assertEquals(7200, $timeline[2]['duration_sec']);
    }

    /**
     * 4. Dandori + 1st Check (terpisah dan teridentifikasi jelas)
     */
    public function test_timeline_dandori_and_first_check()
    {
        $dStart = Carbon::parse('2026-11-05 07:30:00');
        $dEnd = Carbon::parse('2026-11-05 08:00:00');
        $fcStart = Carbon::parse('2026-11-05 08:00:00');
        $fcEnd = Carbon::parse('2026-11-05 08:15:00');
        $prodStart = Carbon::parse('2026-11-05 08:15:00');
        $prodEnd = Carbon::parse('2026-11-05 10:00:00');

        $job = JobMaster::create([
            'job_number' => 'JOB-FC-001-1',
            'status' => 'complete',
            'started_at' => $prodStart,
            'finished_at' => $prodEnd,
        ]);

        Dandori::create([
            'next_job_id' => $job->id,
            'activity' => 'Setup Dies',
            'jenis_dandori' => 'setup',
            'start_time' => $dStart,
            'finish_time' => $dEnd,
            'duration_minutes' => 30,
        ]);

        Dandori::create([
            'next_job_id' => $job->id,
            'activity' => '1st Check QC',
            'jenis_dandori' => '1st_check',
            'start_time' => $fcStart,
            'finish_time' => $fcEnd,
            'duration_minutes' => 15,
        ]);

        $job->load(['downtimes', 'dandoris']);

        $timeline = $this->service->buildJobTimeline($job, null, '2026-11-05', $prodEnd);

        $this->assertCount(3, $timeline);
        $this->assertEquals('dandori', $timeline[0]['type']);
        $this->assertEquals('1st_check', $timeline[1]['type']);
        $this->assertEquals(900, $timeline[1]['duration_sec']);
        $this->assertEquals('production', $timeline[2]['type']);
    }

    /**
     * 5. Production + Breaktime (Breaktime tidak ditelan oleh Production)
     */
    public function test_timeline_production_and_breaktime()
    {
        $prodStart = Carbon::parse('2026-11-05 08:00:00');
        $breakStart = Carbon::parse('2026-11-05 09:30:00');
        $breakEnd = Carbon::parse('2026-11-05 09:45:00');
        $prodEnd = Carbon::parse('2026-11-05 11:00:00');

        $job = JobMaster::create([
            'job_number' => 'JOB-BREAK-001-1',
            'status' => 'complete',
            'started_at' => $prodStart,
            'finished_at' => $prodEnd,
        ]);

        Downtime::create([
            'job_master_id' => $job->id,
            'jenis_downtime' => 'break time',
            'problem' => 'ISTIRAHAT PAGI',
            'start_time' => $breakStart,
            'finish_time' => $breakEnd,
            'duration_seconds' => 900,
        ]);

        $job->load(['downtimes', 'dandoris']);

        $timeline = $this->service->buildJobTimeline($job, null, '2026-11-05', $prodEnd);

        $this->assertCount(3, $timeline);
        $this->assertEquals('production', $timeline[0]['type']);
        $this->assertEquals(5400, $timeline[0]['duration_sec']); // 08:00 to 09:30 = 90m
        $this->assertEquals('break', $timeline[1]['type']);
        $this->assertEquals(900, $timeline[1]['duration_sec']);  // 09:30 to 09:45 = 15m
        $this->assertEquals('production', $timeline[2]['type']);
        $this->assertEquals(4500, $timeline[2]['duration_sec']); // 09:45 to 11:00 = 75m
    }

    /**
     * 6. Production + Downtime
     */
    public function test_timeline_production_and_downtime()
    {
        $prodStart = Carbon::parse('2026-11-05 08:00:00');
        $dtStart = Carbon::parse('2026-11-05 09:00:00');
        $dtEnd = Carbon::parse('2026-11-05 09:20:00');
        $prodEnd = Carbon::parse('2026-11-05 10:00:00');

        $job = JobMaster::create([
            'job_number' => 'JOB-DT-001-1',
            'status' => 'complete',
            'started_at' => $prodStart,
            'finished_at' => $prodEnd,
        ]);

        Downtime::create([
            'job_master_id' => $job->id,
            'jenis_downtime' => 'mesin',
            'problem' => 'Sensor Error',
            'start_time' => $dtStart,
            'finish_time' => $dtEnd,
            'duration_seconds' => 1200,
        ]);

        $job->load(['downtimes', 'dandoris']);

        $timeline = $this->service->buildJobTimeline($job, null, '2026-11-05', $prodEnd);

        $this->assertCount(3, $timeline);
        $this->assertEquals('production', $timeline[0]['type']);
        $this->assertEquals('downtime', $timeline[1]['type']);
        $this->assertEquals(1200, $timeline[1]['duration_sec']);
        $this->assertEquals('production', $timeline[2]['type']);
    }

    /**
     * 7. Production + Tryout (variasi nama: 'try out' / 'tryout' tidak hilang)
     */
    public function test_timeline_production_and_tryout()
    {
        $prodStart = Carbon::parse('2026-11-05 08:00:00');
        $toStart = Carbon::parse('2026-11-05 08:45:00');
        $toEnd = Carbon::parse('2026-11-05 09:15:00');
        $prodEnd = Carbon::parse('2026-11-05 10:30:00');

        $job = JobMaster::create([
            'job_number' => 'JOB-TO-001-1',
            'status' => 'complete',
            'started_at' => $prodStart,
            'finished_at' => $prodEnd,
        ]);

        Downtime::create([
            'job_master_id' => $job->id,
            'jenis_downtime' => 'try out',
            'problem' => 'Trial Dies Baru',
            'start_time' => $toStart,
            'finish_time' => $toEnd,
            'duration_seconds' => 1800,
        ]);

        $job->load(['downtimes', 'dandoris']);

        $timeline = $this->service->buildJobTimeline($job, null, '2026-11-05', $prodEnd);

        $this->assertCount(3, $timeline);
        $this->assertEquals('production', $timeline[0]['type']);
        $this->assertEquals('tryout', $timeline[1]['type']);
        $this->assertEquals('Try Out', $timeline[1]['label']);
        $this->assertEquals('bg-orange-500', $timeline[1]['color']);
        $this->assertEquals(1800, $timeline[1]['duration_sec']);
        $this->assertEquals('production', $timeline[2]['type']);
    }

    /**
     * 8. Kombinasi seluruh aktivitas: Dandori + 1st Check + Production + Breaktime + Tryout + Downtime + Overtime
     */
    public function test_timeline_all_activities_combination()
    {
        $t0 = Carbon::parse('2026-11-05 07:00:00'); // Dandori start
        $t1 = Carbon::parse('2026-11-05 07:30:00'); // Dandori end
        $t2 = Carbon::parse('2026-11-05 07:45:00'); // 1st check end, Prod start
        $t3 = Carbon::parse('2026-11-05 08:30:00'); // Break start
        $t4 = Carbon::parse('2026-11-05 08:45:00'); // Break end, Prod resume
        $t5 = Carbon::parse('2026-11-05 09:30:00'); // Tryout start
        $t6 = Carbon::parse('2026-11-05 10:00:00'); // Tryout end, Prod resume
        $t7 = Carbon::parse('2026-11-05 10:30:00'); // Downtime start
        $t8 = Carbon::parse('2026-11-05 10:45:00'); // Downtime end, Prod resume
        $t9 = Carbon::parse('2026-11-05 11:30:00'); // Job Finish

        $job = JobMaster::create([
            'job_number' => 'JOB-ALL-COMBO-1',
            'status' => 'complete',
            'started_at' => $t2,
            'finished_at' => $t9,
        ]);

        Dandori::create([
            'next_job_id' => $job->id,
            'activity' => 'Dandori Mold',
            'jenis_dandori' => 'setup',
            'start_time' => $t0,
            'finish_time' => $t1,
            'duration_minutes' => 30,
        ]);

        Dandori::create([
            'next_job_id' => $job->id,
            'activity' => '1st Check Mold',
            'jenis_dandori' => '1st_check',
            'start_time' => $t1,
            'finish_time' => $t2,
            'duration_minutes' => 15,
        ]);

        Downtime::create([
            'job_master_id' => $job->id,
            'jenis_downtime' => 'break time',
            'start_time' => $t3,
            'finish_time' => $t4,
            'duration_seconds' => 900,
        ]);

        Downtime::create([
            'job_master_id' => $job->id,
            'jenis_downtime' => 'tryout',
            'start_time' => $t5,
            'finish_time' => $t6,
            'duration_seconds' => 1800,
        ]);

        Downtime::create([
            'job_master_id' => $job->id,
            'jenis_downtime' => 'dies',
            'problem' => 'Dies Retak',
            'start_time' => $t7,
            'finish_time' => $t8,
            'duration_seconds' => 900,
        ]);

        $job->load(['downtimes', 'dandoris']);

        $plan = ProductionPlan::create([
            'job_no' => 'JOB-ALL-COMBO',
            'plan_date' => '2026-11-05',
            'start_time' => '07:00',
            'finish_time' => '11:00',
            'plan' => 600,
        ]);

        $timeline = $this->service->buildJobTimeline($job, $plan, '2026-11-05', $t9);

        $types = array_map(fn($seg) => $seg['type'], $timeline);
        $expectedOrder = [
            'dandori',    // t0 - t1
            '1st_check',  // t1 - t2
            'production', // t2 - t3
            'break',      // t3 - t4
            'production', // t4 - t5
            'tryout',     // t5 - t6
            'production', // t6 - t7
            'downtime',   // t7 - t8
            'production', // t8 - 11:00 (plan finish)
            'overtime',   // 11:00 - t9 (past deadline)
        ];

        $this->assertEquals($expectedOrder, $types);
    }

    /**
     * 9. Shift Malam sebelum dan sesudah tengah malam (Midnight Crossing)
     * Plan date 2026-11-05, events fall into 2026-11-06 (01:00 - 04:00)
     */
    public function test_timeline_night_shift_midnight_crossing()
    {
        $nightStart = Carbon::parse('2026-11-05 20:00:00');
        $dandoriEnd = Carbon::parse('2026-11-05 20:30:00');
        $midnightBreakStart = Carbon::parse('2026-11-05 23:45:00');
        $midnightBreakEnd = Carbon::parse('2026-11-06 00:30:00');
        $afterMidnightDT = Carbon::parse('2026-11-06 02:00:00');
        $afterMidnightDTEnd = Carbon::parse('2026-11-06 02:30:00');
        $nightFinish = Carbon::parse('2026-11-06 05:00:00');

        $job = JobMaster::create([
            'job_number' => 'JOB-NIGHT-001-1',
            'line' => 'PRESS B',
            'status' => 'complete',
            'started_at' => $dandoriEnd,
            'finished_at' => $nightFinish,
        ]);

        Dandori::create([
            'next_job_id' => $job->id,
            'activity' => 'Dandori Night',
            'jenis_dandori' => 'setup',
            'start_time' => $nightStart,
            'finish_time' => $dandoriEnd,
            'duration_minutes' => 30,
            'work_date' => '2026-11-05',
        ]);

        Downtime::create([
            'job_master_id' => $job->id,
            'jenis_downtime' => 'break time',
            'problem' => 'ISHOMA TENGAH MALAM',
            'start_time' => $midnightBreakStart,
            'finish_time' => $midnightBreakEnd,
            'duration_seconds' => 2700,
        ]);

        Downtime::create([
            'job_master_id' => $job->id,
            'jenis_downtime' => 'mesin',
            'problem' => 'Trouble Hidrolik',
            'start_time' => $afterMidnightDT,
            'finish_time' => $afterMidnightDTEnd,
            'duration_seconds' => 1800,
        ]);

        $job->load(['downtimes', 'dandoris']);

        $plan = ProductionPlan::create([
            'job_no' => 'JOB-NIGHT-001',
            'plan_date' => '2026-11-05',
            'shift_name' => 'Shift Malam',
            'start_time' => '20:00',
            'finish_time' => '05:00',
            'plan' => 800,
        ]);

        $timeline = $this->service->buildJobTimeline($job, $plan, '2026-11-05', $nightFinish);

        $types = array_map(fn($seg) => $seg['type'], $timeline);
        $this->assertContains('dandori', $types);
        $this->assertContains('break', $types);
        $this->assertContains('downtime', $types);
        $this->assertContains('production', $types);

        // Verify the break time crosses midnight and duration is exactly 45 mins (2700s)
        $breakSeg = collect($timeline)->firstWhere('type', 'break');
        $this->assertNotNull($breakSeg);
        $this->assertEquals(2700, $breakSeg['duration_sec']);
        $this->assertEquals('2026-11-05 23:45:00', $breakSeg['start']->format('Y-m-d H:i:s'));
        $this->assertEquals('2026-11-06 00:30:00', $breakSeg['end']->format('Y-m-d H:i:s'));

        // Verify after midnight downtime occurs on 2026-11-06
        $dtSeg = collect($timeline)->firstWhere('type', 'downtime');
        $this->assertNotNull($dtSeg);
        $this->assertEquals(1800, $dtSeg['duration_sec']);
        $this->assertEquals('2026-11-06 02:00:00', $dtSeg['start']->format('Y-m-d H:i:s'));
        $this->assertEquals('2026-11-06 02:30:00', $dtSeg['end']->format('Y-m-d H:i:s'));
    }

    /**
     * 10. Job Completed: seluruh histori tetap tersimpan dan tidak terpotong
     */
    public function test_timeline_completed_job_retains_history()
    {
        $start = Carbon::parse('2026-11-04 08:00:00');
        $dtStart = Carbon::parse('2026-11-04 09:00:00');
        $dtEnd = Carbon::parse('2026-11-04 09:30:00');
        $finish = Carbon::parse('2026-11-04 12:00:00');

        $job = JobMaster::create([
            'job_number' => 'JOB-HIST-001-1',
            'status' => 'complete',
            'started_at' => $start,
            'finished_at' => $finish,
        ]);

        Downtime::create([
            'job_master_id' => $job->id,
            'jenis_downtime' => 'repair',
            'problem' => 'Perbaikan Tooling',
            'start_time' => $dtStart,
            'finish_time' => $dtEnd,
            'duration_seconds' => 1800,
        ]);

        $job->load(['downtimes', 'dandoris']);

        // Evaluation today (2026-11-05) on yesterday's completed job:
        $timeline = $this->service->buildJobTimeline($job, null, '2026-11-04', Carbon::parse('2026-11-05 12:00:00'));

        $this->assertCount(3, $timeline);
        $this->assertEquals('production', $timeline[0]['type']);
        $this->assertEquals('downtime', $timeline[1]['type']);
        $this->assertEquals(1800, $timeline[1]['duration_sec']);
        $this->assertEquals('production', $timeline[2]['type']);
        $this->assertEquals('2026-11-04 12:00:00', $timeline[2]['end']->format('Y-m-d H:i:s'));
    }

    /**
     * 11. Tidak ada double counting saat Dandori tercatat di tabel dandoris dan downtimes
     */
    public function test_timeline_reconciles_duplicate_dandori_without_double_counting()
    {
        $start = Carbon::parse('2026-11-05 08:00:00');
        $end = Carbon::parse('2026-11-05 08:30:00');

        $job = JobMaster::create([
            'job_number' => 'JOB-DEDUP-001-1',
            'status' => 'running',
            'started_at' => $end,
        ]);

        // Record in dandoris
        Dandori::create([
            'next_job_id' => $job->id,
            'jenis_dandori' => 'setup',
            'activity' => 'Dandori Mold',
            'start_time' => $start,
            'finish_time' => $end,
            'duration_minutes' => 30,
        ]);

        // Same record synced in downtimes
        Downtime::create([
            'job_master_id' => $job->id,
            'jenis_downtime' => 'dandori',
            'problem' => 'Dandori Mold',
            'start_time' => $start,
            'finish_time' => $end,
            'duration_seconds' => 1800,
        ]);

        $job->load(['downtimes', 'dandoris']);

        $timeline = $this->service->buildJobTimeline($job, null, '2026-11-05', Carbon::parse('2026-11-05 09:30:00'));

        $dandoriSegments = collect($timeline)->where('type', 'dandori');
        // Must be exactly 1 segment of 1800s, not duplicated to 3600s
        $this->assertCount(1, $dandoriSegments);
        $this->assertEquals(1800, $dandoriSegments->first()['duration_sec']);
    }

    /**
     * 12. Tidak ada event yang tertimpa (Event preservation check)
     */
    public function test_timeline_ensures_no_event_overwritten()
    {
        $start = Carbon::parse('2026-11-05 08:00:00');
        $dt1 = Carbon::parse('2026-11-05 08:30:00');
        $dt1End = Carbon::parse('2026-11-05 08:45:00');
        $dt2 = Carbon::parse('2026-11-05 09:15:00');
        $dt2End = Carbon::parse('2026-11-05 09:30:00');
        $finish = Carbon::parse('2026-11-05 10:00:00');

        $job = JobMaster::create([
            'job_number' => 'JOB-NO-OVERWRITE-1',
            'status' => 'complete',
            'started_at' => $start,
            'finished_at' => $finish,
        ]);

        Downtime::create([
            'job_master_id' => $job->id,
            'jenis_downtime' => 'break time',
            'start_time' => $dt1,
            'finish_time' => $dt1End,
            'duration_seconds' => 900,
        ]);

        Downtime::create([
            'job_master_id' => $job->id,
            'jenis_downtime' => 'try out',
            'start_time' => $dt2,
            'finish_time' => $dt2End,
            'duration_seconds' => 900,
        ]);

        $job->load(['downtimes', 'dandoris']);

        $timeline = $this->service->buildJobTimeline($job, null, '2026-11-05', $finish);

        // Sum of all durations in segments must equal exact total job span (2 hours = 7200s)
        $totalSegmentDuration = array_sum(array_column($timeline, 'duration_sec'));
        $this->assertEquals(7200, $totalSegmentDuration);

        // Breakdown must precisely preserve events
        $this->assertEquals(900, collect($timeline)->where('type', 'break')->sum('duration_sec'));
        $this->assertEquals(900, collect($timeline)->where('type', 'tryout')->sum('duration_sec'));
        $this->assertEquals(5400, collect($timeline)->where('type', 'production')->sum('duration_sec'));
    }

    /**
     * 13. Konsistensi Timeline Explorer, Overview, dan Job Detail
     */
    public function test_timeline_consistency_across_views()
    {
        $start = Carbon::parse('2026-11-05 08:00:00');
        $toStart = Carbon::parse('2026-11-05 08:45:00');
        $toEnd = Carbon::parse('2026-11-05 09:15:00');
        $finish = Carbon::parse('2026-11-05 10:00:00');

        $job = JobMaster::create([
            'job_number' => 'JOB-CONSISTENT-1',
            'line' => 'PRESS A',
            'status' => 'complete',
            'started_at' => $start,
            'finished_at' => $finish,
        ]);

        $downtime = Downtime::create([
            'job_master_id' => $job->id,
            'jenis_downtime' => 'tryout',
            'problem' => 'Trial Mold',
            'start_time' => $toStart,
            'finish_time' => $toEnd,
            'duration_seconds' => 1800,
        ]);

        $job->load(['downtimes', 'dandoris']);

        // 1. Service segments (used by Overview and Job Detail)
        $segments = $this->service->buildJobTimeline($job, null, '2026-11-05', $finish);
        $tryoutSeg = collect($segments)->firstWhere('type', 'tryout');

        $this->assertNotNull($tryoutSeg);
        $this->assertEquals(1800, $tryoutSeg['duration_sec']);

        // 2. Timeline Explorer query representation
        $controller = new \App\Http\Controllers\Analytics\ProductionAnalyticsController();
        $reflect = new \ReflectionClass($controller);
        $method = $reflect->getMethod('timelineData');
        $method->setAccessible(true);

        $tlData = $method->invoke($controller, '2026-11-05', '2026-11-05', 'PRESS A', 'Shift Pagi');

        $this->assertTrue($tlData['downtimes']->contains('id', $downtime->id));
        $this->assertEquals('tryout', $tlData['downtimes']->firstWhere('id', $downtime->id)->jenis_downtime);
    }

    /**
     * 14. PENDING Job tanpa aktivitas aktual harus menampilkan Planned Schedule bar (bukan Dandori / Production bar)
     */
    public function test_pending_job_without_activity_renders_planned_schedule()
    {
        $plan = ProductionPlan::create([
            'job_no' => 'GT-6246 FNS',
            'job_master' => 'GT-6246 FNS',
            'press_name' => 'PRESS A',
            'shift_name' => 'Shift 1',
            'plan_date' => '2026-11-05',
            'start_time' => '07:30',
            'finish_time' => '16:00',
            'plan' => 0,
        ]);

        $job = JobMaster::create([
            'job_number' => 'GT-6246 FNS-'.$plan->id,
            'job_name' => 'GT-6246 FNS',
            'line' => 'PRESS A',
            'status' => 'pending',
            'started_at' => null,
            'finished_at' => null,
            'target_qty' => 0,
        ]);

        $job->load(['downtimes', 'dandoris']);

        $timeline = $this->service->buildJobTimeline($job, $plan, '2026-11-05');

        $this->assertCount(1, $timeline);
        $this->assertEquals('planned', $timeline[0]['type']);
        $this->assertEquals(0, collect($timeline)->where('type', 'dandori')->sum('duration_sec'));
        $this->assertEquals(0, collect($timeline)->where('type', 'production')->sum('duration_sec'));
    }

    /**
     * 15. PENDING Job dengan Dandori historis valid (30m) HANYA menampilkan 30m Dandori diikuti Planned Schedule
     * dan TIDAK menutupi seluruh timeline ke now().
     */
    public function test_pending_job_with_valid_historical_dandori_does_not_expand_to_full_timeline()
    {
        $plan = ProductionPlan::create([
            'job_no' => 'GT-6246 FNS',
            'job_master' => 'GT-6246 FNS',
            'press_name' => 'PRESS A',
            'shift_name' => 'Shift 1',
            'plan_date' => '2026-11-05',
            'start_time' => '07:30',
            'finish_time' => '16:00',
            'plan' => 500,
        ]);

        $job = JobMaster::create([
            'job_number' => 'GT-6246 FNS-'.$plan->id,
            'job_name' => 'GT-6246 FNS',
            'line' => 'PRESS A',
            'status' => 'pending',
            'started_at' => null,
            'finished_at' => null,
            'target_qty' => 500,
        ]);

        // Historical dandori 07:30 - 08:00 (1800 detik / 30 menit)
        Dandori::create([
            'next_job_id' => $job->id,
            'line' => 'PRESS A',
            'shift' => 'Shift 1',
            'activity' => 'Dandori Mold GT-6246',
            'jenis_dandori' => 'dandori',
            'start_time' => Carbon::parse('2026-11-05 07:30:00'),
            'finish_time' => Carbon::parse('2026-11-05 08:00:00'),
            'duration_minutes' => 30,
            'work_date' => '2026-11-05',
        ]);

        $job->load(['downtimes', 'dandoris']);

        $timeline = $this->service->buildJobTimeline($job, $plan, '2026-11-05', Carbon::parse('2026-11-05 18:00:00'));

        $dandoriSegs = collect($timeline)->where('type', 'dandori');
        $this->assertEquals(1800, $dandoriSegs->sum('duration_sec'));

        // Trailing part must be planned schedule, NOT production
        $this->assertEquals(0, collect($timeline)->where('type', 'production')->sum('duration_sec'));
        $plannedSegs = collect($timeline)->where('type', 'planned');
        $this->assertTrue($plannedSegs->isNotEmpty());
    }

    /**
     * 16. PENDING Job dengan Dandori tanpa finish_time dan durasi 0 tidak expand ke now()
     */
    public function test_pending_job_with_uncompleted_zero_dandori_does_not_expand_to_now()
    {
        $plan = ProductionPlan::create([
            'job_no' => 'GT-6246 FNS',
            'job_master' => 'GT-6246 FNS',
            'press_name' => 'PRESS A',
            'shift_name' => 'Shift 1',
            'plan_date' => '2026-11-05',
            'start_time' => '07:30',
            'finish_time' => '16:00',
            'plan' => 0,
        ]);

        $job = JobMaster::create([
            'job_number' => 'GT-6246 FNS-'.$plan->id,
            'job_name' => 'GT-6246 FNS',
            'line' => 'PRESS A',
            'status' => 'pending',
            'started_at' => null,
            'finished_at' => null,
            'target_qty' => 0,
        ]);

        // Uncompleted / stale record with start_time but no finish_time and 0 duration
        Dandori::create([
            'next_job_id' => $job->id,
            'line' => 'PRESS A',
            'shift' => 'Shift 1',
            'activity' => 'Dandori Mold',
            'jenis_dandori' => 'dandori',
            'start_time' => Carbon::parse('2026-11-05 07:30:00'),
            'finish_time' => null,
            'duration_minutes' => 0,
            'work_date' => '2026-11-05',
        ]);

        $job->load(['downtimes', 'dandoris']);

        $timeline = $this->service->buildJobTimeline($job, $plan, '2026-11-05', Carbon::parse('2026-11-05 18:00:00'));

        // Zero-duration open dandori on pending job must not create a massive bar
        $this->assertEquals(0, collect($timeline)->where('type', 'dandori')->sum('duration_sec'));
        $this->assertEquals('planned', $timeline[0]['type']);
    }

    /**
     * 17. RUNNING Job: Active ongoing Dandori (finish_time is null) expands to now(),
     * sedangkan completed events retain their exact historical duration.
     */
    public function test_running_job_active_ongoing_dandori_expands_to_now_while_finished_remains_exact()
    {
        $start = Carbon::parse('2026-11-05 08:00:00');
        $d1Start = Carbon::parse('2026-11-05 08:00:00');
        $d1Finish = Carbon::parse('2026-11-05 08:30:00'); // Completed 30m
        $d2Start = Carbon::parse('2026-11-05 09:00:00'); // Active ongoing, no finish_time
        $currentTime = Carbon::parse('2026-11-05 09:45:00'); // 45m ongoing

        $job = JobMaster::create([
            'job_number' => 'JOB-RUNNING-ACTIVE-DANDORI',
            'line' => 'PRESS A',
            'status' => 'running',
            'started_at' => $start,
            'finished_at' => null,
        ]);

        Dandori::create([
            'next_job_id' => $job->id,
            'line' => 'PRESS A',
            'shift' => 'Shift 1',
            'activity' => 'Initial Setup',
            'jenis_dandori' => 'dandori',
            'start_time' => $d1Start,
            'finish_time' => $d1Finish,
            'duration_minutes' => 30,
            'work_date' => '2026-11-05',
        ]);

        Dandori::create([
            'next_job_id' => $job->id,
            'line' => 'PRESS A',
            'shift' => 'Shift 1',
            'activity' => 'Second Setup Ongoing',
            'jenis_dandori' => 'dandori',
            'start_time' => $d2Start,
            'finish_time' => null,
            'duration_minutes' => 0,
            'work_date' => '2026-11-05',
        ]);

        $job->load(['downtimes', 'dandoris']);

        $timeline = $this->service->buildJobTimeline($job, null, '2026-11-05', $currentTime);

        // First completed dandori must remain 1800s (30m)
        $firstDandori = collect($timeline)->where('type', 'dandori')->firstWhere('start', $d1Start);
        $this->assertNotNull($firstDandori);
        $this->assertEquals(1800, $firstDandori['duration_sec']);

        // Second ongoing dandori must expand to $currentTime (09:00:00 to 09:45:00 = 2700s)
        $secondDandori = collect($timeline)->where('type', 'dandori')->firstWhere('start', $d2Start);
        $this->assertNotNull($secondDandori);
        $this->assertEquals(2700, $secondDandori['duration_sec']);

        // Production gap between 08:30 and 09:00 = 1800s
        $prodSeg = collect($timeline)->firstWhere('type', 'production');
        $this->assertNotNull($prodSeg);
        $this->assertEquals(1800, $prodSeg['duration_sec']);
    }

    /**
     * 18. COMPLETED Job: All historical activities (Production, Dandori, 1st Check, Tryout, Break, Downtime)
     * are perfectly preserved up to finished_at without being stretched to now().
     */
    public function test_completed_job_preserves_all_historical_activities_intact()
    {
        $start = Carbon::parse('2026-11-05 07:30:00');
        $dandoriEnd = Carbon::parse('2026-11-05 08:00:00'); // 30m Dandori
        $fcEnd = Carbon::parse('2026-11-05 08:15:00');      // 15m 1st Check
        $breakStart = Carbon::parse('2026-11-05 09:30:00');
        $breakEnd = Carbon::parse('2026-11-05 10:00:00');   // 30m Break
        $dtStart = Carbon::parse('2026-11-05 10:30:00');
        $dtEnd = Carbon::parse('2026-11-05 10:50:00');      // 20m Downtime
        $tryStart = Carbon::parse('2026-11-05 11:10:00');
        $tryEnd = Carbon::parse('2026-11-05 11:30:00');     // 20m Tryout
        $finish = Carbon::parse('2026-11-05 12:00:00');     // 4.5 hours total = 16200s
        $afterFinishNow = Carbon::parse('2026-11-05 17:00:00');

        $job = JobMaster::create([
            'job_number' => 'JOB-COMPLETED-FULL-HISTORICAL',
            'line' => 'PRESS A',
            'status' => 'complete',
            'started_at' => $start,
            'finished_at' => $finish,
        ]);

        Dandori::create([
            'next_job_id' => $job->id,
            'line' => 'PRESS A',
            'shift' => 'Shift 1',
            'activity' => 'Dandori Mold',
            'jenis_dandori' => 'dandori',
            'start_time' => $start,
            'finish_time' => $dandoriEnd,
            'duration_minutes' => 30,
            'work_date' => '2026-11-05',
        ]);

        Dandori::create([
            'next_job_id' => $job->id,
            'line' => 'PRESS A',
            'shift' => 'Shift 1',
            'activity' => '1st Check Quality',
            'jenis_dandori' => '1st_check',
            'start_time' => $dandoriEnd,
            'finish_time' => $fcEnd,
            'duration_minutes' => 15,
            'work_date' => '2026-11-05',
        ]);

        Downtime::create([
            'job_master_id' => $job->id,
            'jenis_downtime' => 'break',
            'problem' => 'Istirahat Pagi',
            'start_time' => $breakStart,
            'finish_time' => $breakEnd,
            'duration_seconds' => 1800,
        ]);

        Downtime::create([
            'job_master_id' => $job->id,
            'jenis_downtime' => 'repair',
            'problem' => 'Dies Jammed',
            'start_time' => $dtStart,
            'finish_time' => $dtEnd,
            'duration_seconds' => 1200,
        ]);

        Downtime::create([
            'job_master_id' => $job->id,
            'jenis_downtime' => 'tryout',
            'problem' => 'Trial Sample',
            'start_time' => $tryStart,
            'finish_time' => $tryEnd,
            'duration_seconds' => 1200,
        ]);

        $job->load(['downtimes', 'dandoris']);

        $timeline = $this->service->buildJobTimeline($job, null, '2026-11-05', $afterFinishNow);

        // Timeline MUST NOT extend to $afterFinishNow (17:00:00). Max end must be $finish (12:00:00)
        $latestEnd = collect($timeline)->max('end');
        $this->assertEquals($finish->format('Y-m-d H:i:s'), $latestEnd->format('Y-m-d H:i:s'));

        // All activities must exist and have accurate durations
        $this->assertEquals(1800, collect($timeline)->where('type', 'dandori')->sum('duration_sec'));
        $this->assertEquals(900, collect($timeline)->where('type', '1st_check')->sum('duration_sec'));
        $this->assertEquals(1800, collect($timeline)->where('type', 'break')->sum('duration_sec'));
        $this->assertEquals(1200, collect($timeline)->where('type', 'downtime')->sum('duration_sec'));
        $this->assertEquals(1200, collect($timeline)->where('type', 'tryout')->sum('duration_sec'));

        // Total sum of timeline must equal 16,200s (07:30 to 12:00)
        $totalSpan = array_sum(array_column($timeline, 'duration_sec'));
        $this->assertEquals(16200, $totalSpan);
    }

    /**
     * 19. Kasus Aktual Server IPPI: Overlapping Dandori dan 1st Check
     * Dandori: 11:06 - 11:12 (344 detik / 5m 44s)
     * 1st Check: 11:08 - 11:12 (220 detik / 3m 40s)
     * Keduanya harus tetap muncul terpisah, dengan durasi tepat, multi-lane, dan identitas berbeda.
     */
    public function test_actual_case_dandori_and_first_check_overlapping_rendered_separately()
    {
        $dStart = Carbon::parse('2026-11-05 11:06:16');
        $dFinish = Carbon::parse('2026-11-05 11:12:00'); // 344 detik (5m 44s)
        $fcStart = Carbon::parse('2026-11-05 11:08:20');
        $fcFinish = Carbon::parse('2026-11-05 11:12:00'); // 220 detik (3m 40s)

        $job = JobMaster::create([
            'job_number' => 'JOB-DANDORI-FC-OVERLAP',
            'line' => 'PRESS A',
            'status' => 'complete',
            'started_at' => $dStart,
            'finished_at' => $dFinish,
        ]);

        Dandori::create([
            'next_job_id' => $job->id,
            'line' => 'PRESS A',
            'shift' => 'Shift 1',
            'activity' => 'Dandori Dies Mold',
            'jenis_dandori' => 'dandori',
            'start_time' => $dStart,
            'finish_time' => $dFinish,
            'duration_minutes' => 5.73,
            'work_date' => '2026-11-05',
        ]);

        Dandori::create([
            'next_job_id' => $job->id,
            'line' => 'PRESS A',
            'shift' => 'Shift 1',
            'activity' => '1ST CHECK',
            'jenis_dandori' => '1st_check',
            'start_time' => $fcStart,
            'finish_time' => $fcFinish,
            'duration_minutes' => 3.67,
            'work_date' => '2026-11-05',
        ]);

        $job->load(['downtimes', 'dandoris']);

        $timeline = $this->service->buildJobTimeline($job, null, '2026-11-05', $dFinish);

        // 1. Both segments MUST be present
        $dandoriSegs = collect($timeline)->where('type', 'dandori');
        $fcSegs = collect($timeline)->where('type', '1st_check');

        $this->assertTrue($dandoriSegs->isNotEmpty(), 'Segmen Dandori harus ada');
        $this->assertTrue($fcSegs->isNotEmpty(), 'Segmen 1st Check harus ada dan tidak boleh tereliminasi');

        // 2. Exact total durations must match source timestamps
        $this->assertEquals(344, $dandoriSegs->sum('duration_sec'));
        $this->assertEquals(220, $fcSegs->sum('duration_sec'));

        // 3. Multi-lane verification: First check is in lane 1 during overlap
        $this->assertTrue($dandoriSegs->pluck('lane')->contains(0));
        $this->assertTrue($fcSegs->pluck('lane')->contains(1));

        // 4. Colors and labels
        $this->assertEquals('bg-amber-400', $dandoriSegs->first()['color']);
        $this->assertEquals('bg-purple-500', $fcSegs->first()['color']);
        $this->assertEquals('Dandori', $dandoriSegs->first()['label']);
        $this->assertEquals('1st Check', $fcSegs->first()['label']);
    }

    /**
     * 20. Overlap lebih dari 2 event secara bersamaan (misal Dandori, 1st Check, dan Downtime Quality Inspection).
     * Masing-masing harus mendapatkan lane terpisah (0, 1, 2) dan tidak saling menimpa.
     */
    public function test_three_concurrent_overlapping_events_receive_distinct_lanes()
    {
        $start = Carbon::parse('2026-11-05 10:00:00');
        $finish = Carbon::parse('2026-11-05 10:30:00');

        $job = JobMaster::create([
            'job_number' => 'JOB-THREE-WAY-OVERLAP',
            'line' => 'PRESS A',
            'status' => 'complete',
            'started_at' => $start,
            'finished_at' => $finish,
        ]);

        // Event 1: Dandori 10:00 - 10:30 (1800s)
        Dandori::create([
            'next_job_id' => $job->id,
            'line' => 'PRESS A',
            'shift' => 'Shift 1',
            'activity' => 'Changeover',
            'jenis_dandori' => 'dandori',
            'start_time' => $start,
            'finish_time' => $finish,
            'duration_minutes' => 30,
            'work_date' => '2026-11-05',
        ]);

        // Event 2: 1st Check 10:05 - 10:20 (900s)
        Dandori::create([
            'next_job_id' => $job->id,
            'line' => 'PRESS A',
            'shift' => 'Shift 1',
            'activity' => '1ST CHECK',
            'jenis_dandori' => '1st_check',
            'start_time' => Carbon::parse('2026-11-05 10:05:00'),
            'finish_time' => Carbon::parse('2026-11-05 10:20:00'),
            'duration_minutes' => 15,
            'work_date' => '2026-11-05',
        ]);

        // Event 3: Downtime trial inspection 10:10 - 10:15 (300s)
        Downtime::create([
            'job_master_id' => $job->id,
            'jenis_downtime' => 'tryout',
            'problem' => 'Trial Sample Check',
            'start_time' => Carbon::parse('2026-11-05 10:10:00'),
            'finish_time' => Carbon::parse('2026-11-05 10:15:00'),
            'duration_seconds' => 300,
        ]);

        $job->load(['downtimes', 'dandoris']);

        $timeline = $this->service->buildJobTimeline($job, null, '2026-11-05', $finish);

        $dandoriSegs = collect($timeline)->where('type', 'dandori');
        $fcSegs = collect($timeline)->where('type', '1st_check');
        $tryoutSegs = collect($timeline)->where('type', 'tryout');

        $this->assertTrue($dandoriSegs->isNotEmpty());
        $this->assertTrue($fcSegs->isNotEmpty());
        $this->assertTrue($tryoutSegs->isNotEmpty());

        // Verify distinct lanes (0, 1, 2)
        $this->assertTrue($dandoriSegs->pluck('lane')->contains(0));
        $this->assertTrue($fcSegs->pluck('lane')->contains(1));
        $this->assertTrue($tryoutSegs->pluck('lane')->contains(2));

        // Verify exact total durations
        $this->assertEquals(1800, $dandoriSegs->sum('duration_sec'));
        $this->assertEquals(900, $fcSegs->sum('duration_sec'));
        $this->assertEquals(300, $tryoutSegs->sum('duration_sec'));
    }

    /**
     * 21. Verifikasi Partisi Lane Interval Spatially:
     * - Sebelum overlap (08:00 - 08:10): Dandori berada di lane 0 dengan total_lanes = 1 (tinggi 100%).
     * - Selama overlap (08:10 - 08:25): Dandori dan 1st Check berbagi lane dengan total_lanes = 2 (tinggi 50% & 50%).
     * - Setelah overlap (08:25 - 08:30): Dandori kembali memiliki total_lanes = 1 (tinggi 100%).
     * - Setelah Dandori selesai (08:30 - 09:30): Produksi tunggal memiliki total_lanes = 1 (tinggi 100%).
     */
    public function test_individual_activity_after_overlap_recovers_full_height()
    {
        $dStart = Carbon::parse('2026-11-05 08:00:00');
        $dFinish = Carbon::parse('2026-11-05 08:30:00'); // Dandori 30m
        $fcStart = Carbon::parse('2026-11-05 08:10:00');
        $fcFinish = Carbon::parse('2026-11-05 08:25:00'); // 1st Check 15m (overlap dalam Dandori)
        $pFinish = Carbon::parse('2026-11-05 09:30:00'); // Produksi 08:30 - 09:30

        $job = JobMaster::create([
            'job_number' => 'JOB-LANE-RECOVERY-TEST',
            'line' => 'PRESS A',
            'status' => 'complete',
            'started_at' => $dStart,
            'finished_at' => $pFinish,
        ]);

        Dandori::create([
            'next_job_id' => $job->id,
            'line' => 'PRESS A',
            'shift' => 'Shift 1',
            'activity' => 'Changeover',
            'jenis_dandori' => 'dandori',
            'start_time' => $dStart,
            'finish_time' => $dFinish,
            'duration_minutes' => 30,
            'work_date' => '2026-11-05',
        ]);

        Dandori::create([
            'next_job_id' => $job->id,
            'line' => 'PRESS A',
            'shift' => 'Shift 1',
            'activity' => '1ST CHECK',
            'jenis_dandori' => '1st_check',
            'start_time' => $fcStart,
            'finish_time' => $fcFinish,
            'duration_minutes' => 15,
            'work_date' => '2026-11-05',
        ]);

        $job->load(['downtimes', 'dandoris']);

        $timeline = $this->service->buildJobTimeline($job, null, '2026-11-05', $pFinish);

        $dandoriSegs = collect($timeline)->where('type', 'dandori')->values();
        $fcSegs = collect($timeline)->where('type', '1st_check')->values();
        $prodSegs = collect($timeline)->where('type', 'production')->values();

        $this->assertCount(3, $dandoriSegs); // 08:00-08:10, 08:10-08:25, 08:25-08:30
        $this->assertCount(1, $fcSegs);      // 08:10-08:25
        $this->assertCount(1, $prodSegs);    // 08:30-09:30

        // 1. Sebelum overlap (08:00 - 08:10): Dandori total_lanes = 1 (100% tinggi penuh)
        $this->assertEquals(1, $dandoriSegs[0]['total_lanes']);
        $this->assertEquals(600, $dandoriSegs[0]['duration_sec']);

        // 2. Selama overlap (08:10 - 08:25): Dandori & 1st Check total_lanes = 2 (50% & 50%)
        $this->assertEquals(2, $dandoriSegs[1]['total_lanes']);
        $this->assertEquals(2, $fcSegs[0]['total_lanes']);
        $this->assertEquals(900, $dandoriSegs[1]['duration_sec']);
        $this->assertEquals(900, $fcSegs[0]['duration_sec']);

        // 3. Setelah 1st Check selesai (08:25 - 08:30): Dandori kembali total_lanes = 1 (100% tinggi penuh)
        $this->assertEquals(1, $dandoriSegs[2]['total_lanes']);
        $this->assertEquals(300, $dandoriSegs[2]['duration_sec']);

        // 4. Setelah Dandori selesai (08:30 - 09:30): Produksi tunggal total_lanes = 1 (100% tinggi penuh)
        $this->assertEquals(1, $prodSegs[0]['total_lanes']);
        $this->assertEquals(3600, $prodSegs[0]['duration_sec']);

        // Total akumulasi durasi tetap presisi
        $this->assertEquals(1800, $dandoriSegs->sum('duration_sec'));
        $this->assertEquals(900, $fcSegs->sum('duration_sec'));
        $this->assertEquals(3600, $prodSegs->sum('duration_sec'));
    }
}
