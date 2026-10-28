<?php

namespace Tests\Feature;

use App\Models\DailyProduction;
use App\Models\Downtime;
use App\Models\JobMaster;
use App\Models\MasterBreakTime;
use App\Models\ProductionSession;
use App\Services\ProductionMetricsService;
use App\Services\ProductionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PpcBreakTimeAutoPauseResumeTest extends TestCase
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

        Schema::dropAllTables();

        Schema::create('job_masters', function ($t) {
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

        Schema::create('production_sessions', function ($t) {
            $t->id();
            $t->unsignedBigInteger('job_master_id');
            $t->date('work_date');
            $t->string('shift')->nullable();
            $t->string('status')->default('running');
            $t->integer('total_seconds')->default(0);
            $t->timestamp('start_time')->nullable();
            $t->timestamp('pause_time')->nullable();
            $t->timestamp('finish_time')->nullable();
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
            $t->string('source')->nullable();
            $t->timestamp('start_time')->nullable();
            $t->timestamp('finish_time')->nullable();
            $t->integer('duration_seconds')->default(0);
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
            $t->float('efficiency')->default(0);
            $t->timestamps();
        });

        Schema::create('master_break_times', function ($t) {
            $t->id();
            $t->string('hari')->default('semua');
            $t->string('waktu_mulai', 10);
            $t->string('waktu_selesai', 10);
            $t->string('type')->default('istirahat');
            $t->string('label');
            $t->string('shift')->nullable();
            $t->boolean('is_active')->default(true);
            $t->integer('sort_order')->default(0);
            $t->timestamps();
        });

        Schema::create('production_plans', function ($t) {
            $t->id();
            $t->date('plan_date')->nullable();
            $t->string('shift_name')->nullable();
            $t->string('press_name')->nullable();
            $t->string('line')->nullable();
            $t->string('job_no')->nullable();
            $t->string('job_master')->nullable();
            $t->string('row_type')->default('job');
            $t->string('status')->nullable();
            $t->integer('plan')->default(0);
            $t->integer('ok')->default(0);
            $t->integer('repair')->default(0);
            $t->integer('reject')->default(0);
            $t->string('start_time')->nullable();
            $t->string('finish_time')->nullable();
            $t->string('hari')->nullable();
            $t->integer('row_no')->nullable();
            $t->timestamps();
        });
    }

    public function test_master_break_time_get_applicable_breaks_resolves_ppc_config_accurately()
    {
        // Setup Friday vs non-Friday breaks
        MasterBreakTime::create([
            'hari' => 'jumat',
            'waktu_mulai' => '11:45',
            'waktu_selesai' => '12:45',
            'label' => 'ISTIRAHAT JUMAT',
            'shift' => 'Shift Pagi',
            'is_active' => true,
        ]);

        MasterBreakTime::create([
            'hari' => 'senin',
            'waktu_mulai' => '12:00',
            'waktu_selesai' => '12:45',
            'label' => 'ISTIRAHAT SIANG',
            'shift' => 'Shift Pagi',
            'is_active' => true,
        ]);

        MasterBreakTime::create([
            'hari' => 'semua',
            'waktu_mulai' => '15:15',
            'waktu_selesai' => '15:30',
            'label' => 'CINGKORAK',
            'shift' => 'Shift Pagi',
            'is_active' => true,
        ]);

        MasterBreakTime::create([
            'hari' => 'semua',
            'waktu_mulai' => '00:00',
            'waktu_selesai' => '00:45',
            'label' => 'ISTIRAHAT MALAM',
            'shift' => 'Shift Malam',
            'is_active' => true,
        ]);

        // 1. Check Friday 2026-10-23
        $fridayBreaks = MasterBreakTime::getApplicableBreaks('2026-10-23', 'Shift Pagi');
        $this->assertTrue($fridayBreaks->contains('label', 'ISTIRAHAT JUMAT'));
        $this->assertFalse($fridayBreaks->contains('label', 'ISTIRAHAT SIANG'));
        $this->assertTrue($fridayBreaks->contains('label', 'CINGKORAK'));
        $this->assertFalse($fridayBreaks->contains('label', 'ISTIRAHAT MALAM'));

        // 2. Check Monday 2026-10-26
        $mondayBreaks = MasterBreakTime::getApplicableBreaks('2026-10-26', 'Shift Pagi');
        $this->assertFalse($mondayBreaks->contains('label', 'ISTIRAHAT JUMAT'));
        $this->assertTrue($mondayBreaks->contains('label', 'ISTIRAHAT SIANG'));
        $this->assertTrue($mondayBreaks->contains('label', 'CINGKORAK'));

        // 3. Check Shift Malam
        $nightBreaks = MasterBreakTime::getApplicableBreaks('2026-10-26', 'Shift Malam');
        $this->assertTrue($nightBreaks->contains('label', 'ISTIRAHAT MALAM'));
        $this->assertFalse($nightBreaks->contains('label', 'ISTIRAHAT SIANG'));
    }

    public function test_aes_012_case_auto_pause_and_resumes_without_double_counting_production_time()
    {
        Carbon::setTestNow(Carbon::parse('2026-10-28 11:30:00'));

        // Create PPC break configuration: 12:00 - 12:45
        MasterBreakTime::create([
            'hari' => 'semua',
            'waktu_mulai' => '12:00',
            'waktu_selesai' => '12:45',
            'type' => 'istirahat',
            'label' => 'ISTIRAHAT SIANG',
            'shift' => 'Shift Pagi',
            'is_active' => true,
            'sort_order' => 10,
        ]);

        $job = JobMaster::create([
            'job_number' => 'AES-012-JOB',
            'job_name' => 'AES-012 FNS',
            'line' => 'PRESS A',
            'status' => 'running',
            'target_qty' => 300,
            'started_at' => Carbon::parse('2026-10-28 11:30:00'),
        ]);

        $session = ProductionSession::create([
            'job_master_id' => $job->id,
            'work_date' => '2026-10-28',
            'shift' => 'Shift Pagi',
            'status' => 'running',
            'total_seconds' => 0,
            'start_time' => Carbon::parse('2026-10-28 11:30:00'),
        ]);

        // Run break command at 11:45 (before break) -> should still be running
        Carbon::setTestNow(Carbon::parse('2026-10-28 11:45:00'));
        Artisan::call('break:auto');

        $this->assertEquals('running', $job->fresh()->status);
        $this->assertEquals('running', $session->fresh()->status);
        $this->assertNull(Downtime::where('job_master_id', $job->id)->first());

        // Fast-forward to 12:05 (INSIDE break window 12:00 - 12:45)
        Carbon::setTestNow(Carbon::parse('2026-10-28 12:05:00'));
        Artisan::call('break:auto');

        // 1. Session and JobMaster must be paused
        $this->assertEquals('paused', $job->fresh()->status);
        $this->assertEquals('paused', $session->fresh()->status);
        $this->assertNotNull($session->fresh()->pause_time);

        // Production runtime up to 12:05 should be recorded in total_seconds (35 mins = 2100 s)
        $this->assertEquals(2100, $session->fresh()->total_seconds);

        // Downtime break record must be created
        $dt = Downtime::where('job_master_id', $job->id)
            ->where('jenis_downtime', 'break time')
            ->whereNull('finish_time')
            ->first();
        $this->assertNotNull($dt);
        $this->assertEquals('ISTIRAHAT SIANG', $dt->problem);

        // Fast-forward to 12:30 (still inside break) -> should NOT duplicate break
        Carbon::setTestNow(Carbon::parse('2026-10-28 12:30:00'));
        Artisan::call('break:auto');
        $this->assertEquals(1, Downtime::where('job_master_id', $job->id)->count());
        $this->assertEquals('paused', $job->fresh()->status);

        // Fast-forward to 12:45 (Break finishes)
        Carbon::setTestNow(Carbon::parse('2026-10-28 12:45:00'));
        Artisan::call('break:auto');

        // 2. Session and JobMaster must resume running automatically
        $this->assertEquals('running', $job->fresh()->status);
        $this->assertEquals('running', $session->fresh()->status);
        $this->assertEquals('2026-10-28 12:45:00', $session->fresh()->start_time);

        // Break downtime must be closed with duration (40 mins from 12:05 to 12:45 = 2400 s)
        $closedDt = Downtime::where('job_master_id', $job->id)->first();
        $this->assertNotNull($closedDt->finish_time);
        $this->assertEquals(2400, $closedDt->duration_seconds);

        // DailyProduction must record downtime without inflating runtime
        $daily = DailyProduction::where('job_master_id', $job->id)->where('work_date', '2026-10-28')->first();
        $this->assertEquals(2400, $daily->downtime_seconds);
        $this->assertEquals(2100, $daily->runtime_seconds);

        // After running 15 minutes after break (13:00)
        Carbon::setTestNow(Carbon::parse('2026-10-28 13:00:00'));
        $prodService = app(ProductionService::class);
        $currentRuntime = $prodService->calculateRuntime($job->id);

        // Runtime = 2100s (before break) + 900s (12:45 - 13:00) = 3000s (50 minutes)
        // Notice the 40-minute break between 12:05 and 12:45 is NOT included!
        $this->assertEquals(3000, $currentRuntime);

        Carbon::setTestNow();
    }
}
