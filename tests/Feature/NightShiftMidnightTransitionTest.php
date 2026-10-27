<?php

namespace Tests\Feature;

use App\Models\DailyProduction;
use App\Models\Downtime;
use App\Models\JobMaster;
use App\Models\MasterBreakTime;
use App\Models\ProductionLog;
use App\Models\ProductionPlan;
use App\Models\ProductionSession;
use App\Services\ProductionMetricsService;
use App\Services\ProductionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NightShiftMidnightTransitionTest extends TestCase
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
            $t->string('work_date');
            $t->string('status')->default('running');
            $t->timestamp('start_time')->nullable();
            $t->timestamp('pause_time')->nullable();
            $t->timestamp('finish_time')->nullable();
            $t->integer('total_seconds')->default(0);
            $t->integer('downtime_seconds')->default(0);
            $t->timestamps();
        });

        Schema::create('daily_productions', function ($t) {
            $t->id();
            $t->unsignedBigInteger('job_master_id');
            $t->string('work_date');
            $t->string('line')->nullable();
            $t->string('shift')->nullable();
            $t->integer('actual_ok')->default(0);
            $t->integer('actual_qty')->default(0);
            $t->integer('actual_repair')->default(0);
            $t->integer('actual_reject')->default(0);
            $t->integer('runtime_seconds')->default(0);
            $t->integer('downtime_seconds')->default(0);
            $t->float('efficiency')->default(0);
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

        Schema::create('production_logs', function ($t) {
            $t->id();
            $t->unsignedBigInteger('job_master_id');
            $t->integer('ok_qty')->default(0);
            $t->integer('repair_qty')->default(0);
            $t->integer('reject_qty')->default(0);
            $t->timestamps();
        });

        Schema::create('production_plans', function ($t) {
            $t->id();
            $t->date('plan_date')->nullable();
            $t->string('shift_name')->nullable();
            $t->string('line_name')->nullable();
            $t->string('press_name')->nullable();
            $t->string('job_no')->nullable();
            $t->string('job_master')->nullable();
            $t->string('row_type')->default('job');
            $t->integer('plan')->default(0);
            $t->integer('target_qty')->default(0);
            $t->float('ok')->default(0);
            $t->float('repair')->default(0);
            $t->float('reject')->default(0);
            $t->string('act_start')->nullable();
            $t->string('act_finish')->nullable();
            $t->string('status')->default('pending');
            $t->timestamp('skipped_at')->nullable();
            $t->timestamps();
        });

        Schema::create('master_break_times', function ($t) {
            $t->id();
            $t->string('nama_break')->nullable();
            $t->string('hari')->default('semua');
            $t->string('shift')->nullable();
            $t->string('waktu_mulai');
            $t->string('waktu_selesai');
            $t->string('label')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
    }

    public function test_production_date_and_shift_resolution_across_boundaries()
    {
        // 20:59 - Still Shift Pagi
        $t1 = Carbon::parse('2026-10-26 20:59:00');
        $this->assertEquals('2026-10-26', ProductionMetricsService::currentProductionDate($t1));
        $this->assertEquals('Shift Pagi', ProductionMetricsService::currentProductionShift($t1));

        // 21:00 - Shift Malam starts
        $t2 = Carbon::parse('2026-10-26 21:00:00');
        $this->assertEquals('2026-10-26', ProductionMetricsService::currentProductionDate($t2));
        $this->assertEquals('Shift Malam', ProductionMetricsService::currentProductionShift($t2));

        // 23:59 - Shift Malam before midnight
        $t3 = Carbon::parse('2026-10-26 23:59:59');
        $this->assertEquals('2026-10-26', ProductionMetricsService::currentProductionDate($t3));
        $this->assertEquals('Shift Malam', ProductionMetricsService::currentProductionShift($t3));

        // 00:01 (calendar 2026-10-27) - Must still be Shift Malam with work date 2026-10-26
        $t4 = Carbon::parse('2026-10-27 00:01:00');
        $this->assertEquals('2026-10-26', ProductionMetricsService::currentProductionDate($t4));
        $this->assertEquals('Shift Malam', ProductionMetricsService::currentProductionShift($t4));

        // 00:30 (Midnight break)
        $t5 = Carbon::parse('2026-10-27 00:30:00');
        $this->assertEquals('2026-10-26', ProductionMetricsService::currentProductionDate($t5));
        $this->assertEquals('Shift Malam', ProductionMetricsService::currentProductionShift($t5));

        // 04:00 (Early morning work)
        $t6 = Carbon::parse('2026-10-27 04:00:00');
        $this->assertEquals('2026-10-26', ProductionMetricsService::currentProductionDate($t6));
        $this->assertEquals('Shift Malam', ProductionMetricsService::currentProductionShift($t6));

        // 07:29 (Shift Malam final minutes)
        $t7 = Carbon::parse('2026-10-27 07:29:59');
        $this->assertEquals('2026-10-26', ProductionMetricsService::currentProductionDate($t7));
        $this->assertEquals('Shift Malam', ProductionMetricsService::currentProductionShift($t7));

        // 07:30 (Cutoff -> New work date Shift Pagi)
        $t8 = Carbon::parse('2026-10-27 07:30:00');
        $this->assertEquals('2026-10-27', ProductionMetricsService::currentProductionDate($t8));
        $this->assertEquals('Shift Pagi', ProductionMetricsService::currentProductionShift($t8));
    }

    public function test_night_shift_job_paused_and_resumed_across_midnight_retains_work_date()
    {
        $service = app(ProductionService::class);

        // Job starts at 22:00 on 2026-10-26
        Carbon::setTestNow('2026-10-26 22:00:00');

        $job = JobMaster::create([
            'job_number' => 'JOB-NIGHT-01-1',
            'job_name' => 'Part Night 01',
            'line' => 'Line A',
            'status' => 'pending',
            'target_qty' => 500,
            'capacity' => 500
        ]);

        $service->startJob($job->id);

        $session = ProductionSession::where('job_master_id', $job->id)->first();
        $this->assertNotNull($session);
        $this->assertEquals('2026-10-26', $session->work_date);
        $this->assertEquals('running', $session->status);

        // Pause at 00:05 (next calendar day: 2026-10-27)
        Carbon::setTestNow('2026-10-27 00:05:00');
        $service->pauseJob($job->id);

        $session->refresh();
        $this->assertEquals('paused', $session->status);
        $this->assertEquals('2026-10-26', $session->work_date);

        $daily = DailyProduction::where('job_master_id', $job->id)->first();
        $this->assertNotNull($daily);
        $this->assertEquals('2026-10-26', $daily->work_date);

        // Resume at 00:50 (after midnight break)
        Carbon::setTestNow('2026-10-27 00:50:00');
        $service->resumeJob($job->id);

        $session->refresh();
        $this->assertEquals('running', $session->status);
        $this->assertEquals('2026-10-26', $session->work_date);

        // Finish at 03:00
        Carbon::setTestNow('2026-10-27 03:00:00');
        $service->finishJob($job->id, null, false, 500, 0, 0);

        $daily->refresh();
        $this->assertEquals('2026-10-26', $daily->work_date);
        $this->assertEquals(500, $daily->actual_ok);

        Carbon::setTestNow();
    }

    public function test_auto_break_time_during_night_shift_midnight_break()
    {
        // Setup Midnight Break in MasterBreakTime: 00:00 - 00:45
        MasterBreakTime::create([
            'nama_break' => 'Istirahat Malam',
            'hari' => 'semua',
            'shift' => 'Shift Malam',
            'waktu_mulai' => '00:00',
            'waktu_selesai' => '00:45',
            'label' => 'BREAK MALAM',
            'is_active' => true,
        ]);

        $service = app(ProductionService::class);

        // Job starts at 22:00 on 2026-10-26
        Carbon::setTestNow('2026-10-26 22:00:00');
        $job = JobMaster::create([
            'job_number' => 'JOB-NIGHT-BREAK-1',
            'job_name' => 'Part Night Break',
            'line' => 'Line A',
            'status' => 'pending',
            'target_qty' => 300,
            'capacity' => 300,
        ]);
        $service->startJob($job->id);

        // Clock moves to 00:15 (2026-10-27) -> In Break Window
        Carbon::setTestNow('2026-10-27 00:15:00');
        Artisan::call('break:auto');

        $job->refresh();
        $this->assertEquals('paused', $job->status);

        $session = ProductionSession::where('job_master_id', $job->id)->first();
        $this->assertEquals('paused', $session->status);
        $this->assertEquals('2026-10-26', $session->work_date);

        $activeBreak = Downtime::where('job_master_id', $job->id)->where('jenis_downtime', 'break time')->first();
        $this->assertNotNull($activeBreak);
        $this->assertNull($activeBreak->finish_time);

        // Clock moves to 00:46 (Break has ended)
        Carbon::setTestNow('2026-10-27 00:46:00');
        Artisan::call('break:auto');

        $job->refresh();
        $this->assertEquals('running', $job->status);

        $session->refresh();
        $this->assertEquals('running', $session->status);
        $this->assertEquals('2026-10-26', $session->work_date);

        $activeBreak->refresh();
        $this->assertNotNull($activeBreak->finish_time);
        $this->assertGreaterThan(0, $activeBreak->duration_seconds);

        $daily = DailyProduction::where('job_master_id', $job->id)->first();
        $this->assertNotNull($daily);
        $this->assertEquals('2026-10-26', $daily->work_date);
        $this->assertEquals($activeBreak->duration_seconds, $daily->downtime_seconds);

        Carbon::setTestNow();
    }
}
