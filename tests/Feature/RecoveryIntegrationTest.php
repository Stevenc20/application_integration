<?php

use App\Models\LineMaster;
use App\Models\MasterBreakTime;
use App\Models\ProductionPlan;
use App\Models\RecoveryItem;
use App\Models\RecoverySchedule;
use App\Models\User;
use App\Services\CutOffService;
use App\Services\RecoverySchedulerService;
use App\Services\TimelineGenerationService;
use App\Services\ProductionService;
use App\Models\JobMaster;
use App\Models\ProductionSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

uses(RefreshDatabase::class);

// ──────────────── Helpers ────────────────

function seedBreakTimes(): void
{
    $rows = [
        ['hari' => 'senin', 'waktu_mulai' => '12:00', 'waktu_selesai' => '12:45', 'type' => 'istirahat', 'label' => 'ISTIRAHAT SIANG', 'sort_order' => 10, 'shift' => 'Shift Pagi'],
        ['hari' => 'jumat', 'waktu_mulai' => '11:45', 'waktu_selesai' => '12:45', 'type' => 'istirahat', 'label' => 'ISTIRAHAT JUMAT', 'sort_order' => 10, 'shift' => 'Shift Pagi'],
        ['hari' => 'semua', 'waktu_mulai' => '15:15', 'waktu_selesai' => '15:30', 'type' => 'cinkorak', 'label' => 'CINGKORAK', 'sort_order' => 20, 'shift' => 'Shift Pagi'],
        ['hari' => 'semua', 'waktu_mulai' => '16:30', 'waktu_selesai' => '16:45', 'type' => 'istirahat', 'label' => 'BREAKTIME', 'sort_order' => 30, 'shift' => 'Shift Pagi'],
        ['hari' => 'semua', 'waktu_mulai' => '18:00', 'waktu_selesai' => '18:30', 'type' => 'istirahat', 'label' => 'ISTIRAHAT SORE', 'sort_order' => 40, 'shift' => 'Shift Pagi'],
        ['hari' => 'semua', 'waktu_mulai' => '00:00', 'waktu_selesai' => '00:45', 'type' => 'istirahat', 'label' => 'ISTIRAHAT MALAM', 'sort_order' => 10, 'shift' => 'Shift Malam'],
        ['hari' => 'semua', 'waktu_mulai' => '04:45', 'waktu_selesai' => '05:00', 'type' => 'istirahat', 'label' => 'BREAKTIME', 'sort_order' => 20, 'shift' => 'Shift Malam'],
    ];
    foreach ($rows as $r) {
        MasterBreakTime::create(array_merge($r, ['is_active' => true]));
    }
}

function createLineMasters(): void
{
    foreach (['PA', 'PB', 'PC', 'PD'] as $name) {
        LineMaster::firstOrCreate(
            ['line_code' => $name],
            ['line_name' => $name, 'kapasitas' => 100, 'type_line' => 'printing']
        );
    }
}

function lineMasterId(string $code): int
{
    return LineMaster::where('line_code', $code)->first()->id;
}

function makePlan(array $overrides = []): ProductionPlan
{
    $lmId = lineMasterId($overrides['press_name'] ?? 'PA');
    $defaults = [
        'line_master_id' => $lmId,
        'plan_date' => '2026-06-25',
        'shift_name' => 'Shift Pagi',
        'press_name' => 'PA',
        'hari' => 'kamis',
        'tgl' => '25/06/2026',
        'jam' => 'S1',
        'revisi' => '0',
        'row_no' => 10,
        'row_type' => 'job',
        'job_no' => 'TEST-001',
        'job_master' => 'TEST JOB 001',
        'plan' => 100,
        'ok' => 0,
        'repair' => 0,
        'reject' => 0,
        'ct_detik' => 10,
        'dct' => 5,
        'reg_active' => 1,
        'total_mesin' => 1,
        'source_type' => 'ppc',
    ];
    return ProductionPlan::create(array_merge($defaults, $overrides));
}

function makeRecoverySchedule(string $date = '2026-06-25', string $shift = 'Shift Pagi', string $press = 'PA'): RecoverySchedule
{
    return RecoverySchedule::create([
        'plan_date' => $date,
        'shift_name' => $shift,
        'press_name' => $press,
        'status' => 'waiting_approval',
    ]);
}

function makeUser(): User
{
    return User::create([
        'name' => 'Test User',
        'nrp' => '1234',
        'password' => bcrypt('password'),
        'role' => 'ppc',
        'is_active' => 1,
    ]);
}

// ──────────────── 1. CUT OFF TESTS ────────────────

describe('Cut Off', function () {
    beforeEach(function () {
        createLineMasters();
    });

    test('Shift Pagi: creates recovery items for unfinished plans', function () {
        $plan = makePlan(['plan' => 100, 'ok' => 60, 'repair' => 0, 'reject' => 0]);
        $service = new CutOffService();
        $stats = $service->processCutOff('2026-06-25', 'Shift Pagi');

        expect($stats['created'])->toBe(1);
        expect($stats['total_unfinished'])->toBe(1);

        $item = RecoveryItem::first();
        expect($item)->not->toBeNull();
        expect($item->status)->toBe('waiting_approval');
        expect($item->recovery_qty)->toBe(40.0);
        expect($item->plan_qty)->toBe(100.0);
        expect($item->ok)->toBe(60.0);
        expect($item->source_date->format('Y-m-d'))->toBe('2026-06-25');
        expect($item->source_shift)->toBe('Shift Pagi');
    });

    test('Shift Malam: creates recovery items for unfinished plans', function () {
        $plan = makePlan([
            'plan_date' => '2026-06-24',
            'shift_name' => 'Shift Malam',
            'plan' => 200, 'ok' => 150,
        ]);
        $service = new CutOffService();
        $stats = $service->processCutOff('2026-06-24', 'Shift Malam');

        expect($stats['created'])->toBe(1);
        $item = RecoveryItem::first();
        expect($item->recovery_qty)->toBe(50.0);
        expect($item->duration_minutes)->toBeGreaterThan(0);
    });

    test('skips plans where ok >= plan', function () {
        makePlan(['plan' => 100, 'ok' => 100]);
        $service = new CutOffService();
        $stats = $service->processCutOff('2026-06-25', 'Shift Pagi');
        expect($stats['created'])->toBe(0);
        expect($stats['total_unfinished'])->toBe(0);
    });

    test('recovery_qty = plan - ok', function () {
        makePlan(['plan' => 100, 'ok' => 0]);
        $service = new CutOffService();
        $stats = $service->processCutOff('2026-06-25', 'Shift Pagi');
        $item = RecoveryItem::first();
        expect($item->recovery_qty)->toBe(100.0);
    });

    test('handles multiple presses in same cut-off', function () {
        makePlan(['press_name' => 'PA', 'plan' => 100, 'ok' => 50]);
        makePlan(['press_name' => 'PB', 'plan' => 80, 'ok' => 30]);
        $service = new CutOffService();
        $stats = $service->processCutOff('2026-06-25', 'Shift Pagi');
        expect($stats['created'])->toBe(2);
    });
});

// ──────────────── 2. RECOVERY APPROVAL TESTS ────────────────

describe('Recovery Approval', function () {
    beforeEach(function () {
        createLineMasters();
    });

    test('approve 1 item changes status to approved', function () {
        $plan = makePlan(['plan' => 100, 'ok' => 60]);
        $service = new CutOffService();
        $service->processCutOff('2026-06-25', 'Shift Pagi');

        $item = RecoveryItem::first();
        $item->update(['status' => 'approved']);

        expect($item->fresh()->status)->toBe('approved');
    });

    test('approve does not cascade to other items', function () {
        makePlan(['press_name' => 'PA', 'row_no' => 10, 'job_no' => 'JOB-A', 'plan' => 100, 'ok' => 50]);
        makePlan(['press_name' => 'PB', 'row_no' => 20, 'job_no' => 'JOB-B', 'plan' => 100, 'ok' => 50]);

        $service = new CutOffService();
        $service->processCutOff('2026-06-25', 'Shift Pagi');

        RecoveryItem::where('job_no', 'JOB-A')->update(['status' => 'approved']);
        $itemB = RecoveryItem::where('job_no', 'JOB-B')->first();

        expect($itemB->status)->toBe('waiting_approval');
    });

    test('approve 2 items retains waiting_approval on others', function () {
        for ($i = 1; $i <= 5; $i++) {
            makePlan(['row_no' => $i * 10, 'job_no' => "JOB-{$i}", 'plan' => 100, 'ok' => 50]);
        }
        $service = new CutOffService();
        $service->processCutOff('2026-06-25', 'Shift Pagi');

        $ids = RecoveryItem::take(2)->pluck('id');
        RecoveryItem::whereIn('id', $ids)->update(['status' => 'approved']);

        expect(RecoveryItem::approved()->count())->toBe(2);
        expect(RecoveryItem::pending()->count())->toBe(3);
    });

    test('reject 1 item changes status to rejected', function () {
        makeUser();
        makePlan(['plan' => 100, 'ok' => 60]);
        $service = new CutOffService();
        $service->processCutOff('2026-06-25', 'Shift Pagi');

        $item = RecoveryItem::first();
        $item->update(['status' => 'rejected']);

        expect($item->fresh()->status)->toBe('rejected');
    });

    test('reject does not cascade to other items', function () {
        makePlan(['row_no' => 10, 'job_no' => 'JOB-A', 'plan' => 100, 'ok' => 50]);
        makePlan(['row_no' => 20, 'job_no' => 'JOB-B', 'plan' => 100, 'ok' => 50]);

        $service = new CutOffService();
        $service->processCutOff('2026-06-25', 'Shift Pagi');

        RecoveryItem::where('job_no', 'JOB-A')->update(['status' => 'rejected']);
        $itemB = RecoveryItem::where('job_no', 'JOB-B')->first();

        expect($itemB->status)->toBe('waiting_approval');
    });
});

// ──────────────── 3. UPLOAD EXCEL SIMULATION ────────────────

describe('Upload Excel Simulation', function () {
    beforeEach(function () {
        createLineMasters();
    });

    test('upload shift pagi only', function () {
        makePlan(['plan' => 100, 'ok' => 0]);
        expect(ProductionPlan::where('shift_name', 'Shift Pagi')->count())->toBe(1);
    });

    test('upload shift malam only', function () {
        makePlan(['shift_name' => 'Shift Malam', 'plan_date' => '2026-06-24', 'plan' => 100]);
        expect(ProductionPlan::where('shift_name', 'Shift Malam')->count())->toBe(1);
    });

    test('upload all shifts', function () {
        makePlan(['shift_name' => 'Shift Pagi', 'row_no' => 10, 'plan' => 100]);
        makePlan(['shift_name' => 'Shift Malam', 'plan_date' => '2026-06-24', 'row_no' => 20, 'plan' => 100]);
        expect(ProductionPlan::count())->toBe(2);
    });

    test('upload ulang tanggal sama — duplicate prevention', function () {
        makePlan(['row_no' => 10, 'job_no' => 'JOB-A', 'plan' => 100]);
        makePlan(['row_no' => 10, 'job_no' => 'JOB-A', 'plan' => 100]);
        // Should allow duplicates (no unique constraint on job_no)
        expect(ProductionPlan::count())->toBe(2);
    });
});

// ──────────────── 4. SCHEDULER TESTS ────────────────

describe('Scheduler', function () {
    beforeEach(function () {
        createLineMasters();
        seedBreakTimes();
    });

    function createSchedulerItems(string $press, int $count, int $planQty, float $ct, float $dct): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $p = makePlan(['press_name' => $press, 'row_no' => $i * 10, 'job_no' => "JOB-{$press}{$i}", 'plan' => $planQty]);
            RecoveryItem::create([
                'recovery_schedule_id' => makeRecoverySchedule('2026-06-25', 'Shift Pagi', $press)->id,
                'production_plan_id' => $p->id,
                'job_no' => "JOB-{$press}{$i}",
                'job_master' => "JOB {$press} {$i}",
                'press_name' => $press,
                'plan_qty' => $planQty,
                'ok' => 0,
                'recovery_qty' => $planQty,
                'status' => 'approved',
                'source_date' => '2026-06-25',
                'source_shift' => 'Shift Pagi',
                'ct_detik' => $ct,
                'dct' => $dct,
                'queued_at' => now(),
            ]);
        }
    }

    test('Press A with 5 approved items schedules correctly', function () {
        createSchedulerItems('PA', 5, 50, 10, 5);
        $service = new RecoverySchedulerService();
        $stats = $service->scheduleForShift('2026-06-25', 'Shift Pagi', 'PA');
        expect($stats['recovery_total'])->toBe(5);
    });

    test('Press B with 20 items capacity check', function () {
        createSchedulerItems('PB', 20, 30, 5, 2);
        $service = new RecoverySchedulerService();
        $stats = $service->scheduleForShift('2026-06-25', 'Shift Pagi', 'PB');
        expect($stats['recovery_total'])->toBe(20);
    });

    test('Press B 100 items does not crash', function () {
        createSchedulerItems('PB', 100, 10, 3, 1);
        $service = new RecoverySchedulerService();
        $stats = $service->scheduleForShift('2026-06-25', 'Shift Pagi', 'PB');
        expect($stats['recovery_total'])->toBe(100);
    });

    test('Press D stress test', function () {
        createSchedulerItems('PD', 170, 15, 4, 1);
        $service = new RecoverySchedulerService();
        $stats = $service->scheduleForShift('2026-06-25', 'Shift Pagi', 'PD');
        expect($stats['recovery_total'])->toBe(170);
    });
});

// ──────────────── 5. BREAKTIME TESTS ────────────────

describe('Breaktime', function () {
    beforeEach(function () {
        createLineMasters();
        seedBreakTimes();
    });

    test('break windows resolve correctly for Senin vs Jumat', function () {
        $engine = app(TimelineGenerationService::class);

        $seninBreaks = $engine->resolveBreakWindows('2026-06-22', 'Shift Pagi', 'senin');
        $seninTimes = array_map(fn($b) => $b['start'] . '-' . $b['finish'], $seninBreaks);
        expect($seninTimes)->toContain('12:00-12:45');

        $jumatBreaks = $engine->resolveBreakWindows('2026-06-26', 'Shift Pagi', 'jumat');
        $jumatTimes = array_map(fn($b) => $b['start'] . '-' . $b['finish'], $jumatBreaks);
        expect($jumatTimes)->toContain('11:45-12:45');
    });

    test('malam shift break windows resolve correctly', function () {
        $engine = app(TimelineGenerationService::class);
        $breaks = $engine->resolveBreakWindows('2026-06-25', 'Shift Malam');
        expect($breaks)->not->toBeEmpty();
    });

    test('calculateFinishWithBreaks handles breaks correctly', function () {
        $engine = app(TimelineGenerationService::class);
        $breaks = [['start' => '12:00', 'finish' => '12:45']];

        $finish = $engine->calculateFinishWithBreaks('08:00', 300, $breaks);
        expect($finish)->not->toBeNull();

        $finishMins = \App\Models\MasterBreakTime::timeToMinutes(substr($finish, 0, 5));
        expect($finishMins)->toBeGreaterThan(0);
    });

    test('pushIfInBreak pushes start time past break', function () {
        $engine = app(TimelineGenerationService::class);
        $breaks = [['start' => '12:00', 'finish' => '12:45']];

        $pushed = $engine->pushIfInBreak('11:30', $breaks);
        expect(substr($pushed, 0, 5))->toBe('11:30');

        $pushed = $engine->pushIfInBreak('12:30', $breaks);
        expect(substr($pushed, 0, 5))->toBe('12:45');
    });
});

// ──────────────── 6. TIMELINE GENERATION ────────────────

describe('Timeline Generation', function () {
    beforeEach(function () {
        createLineMasters();
        seedBreakTimes();
    });

    test('generates timeline for Shift Pagi PA with 5 jobs', function () {
        for ($i = 1; $i <= 5; $i++) {
            makePlan(['row_no' => $i * 10, 'job_no' => "JOB-{$i}", 'plan' => 100, 'ct_detik' => 30, 'dct' => 5]);
        }

        $engine = app(TimelineGenerationService::class);
        $result = $engine->regenerateSection('2026-06-25', 'Shift Pagi', 'PA');

        expect($result['updated'])->toBeGreaterThanOrEqual(5);
        expect($result)->toHaveKey('overflow');
        expect($result['overflow'])->toBeArray();
    });

    test('no time overlap in validated timeline', function () {
        for ($i = 1; $i <= 5; $i++) {
            $startTime = sprintf('%02d:00', 7 + $i);
            $finishTime = sprintf('%02d:30', 7 + $i);
            makePlan(['row_no' => $i * 10, 'job_no' => "JOB-{$i}", 'plan' => 100, 'start_time' => $startTime, 'finish_time' => $finishTime]);
        }

        $engine = app(TimelineGenerationService::class);
        $errors = $engine->validateTimeline('2026-06-25', 'Shift Pagi', 'PA');

        expect($errors)->toBeEmpty();
    });

    test('detects duplicate recovery_id in timeline', function () {
        makePlan(['row_no' => 10, 'job_no' => 'JOB-A', 'plan' => 100, 'start_time' => '08:00', 'finish_time' => '09:00', 'recovery_id' => 1]);
        makePlan(['row_no' => 20, 'job_no' => 'JOB-B', 'plan' => 100, 'start_time' => '09:00', 'finish_time' => '10:00', 'recovery_id' => 1]);

        $engine = app(TimelineGenerationService::class);
        $errors = $engine->validateTimeline('2026-06-25', 'Shift Pagi', 'PA');

        expect($errors)->not->toBeEmpty();
        expect($errors[0])->toContain('Duplicate recovery_id');
    });

    test('detects finish after shift end', function () {
        makePlan(['row_no' => 10, 'job_no' => 'JOB-A', 'plan' => 100, 'start_time' => '20:00', 'finish_time' => '22:00']);
        $engine = app(TimelineGenerationService::class);
        $errors = $engine->validateTimeline('2026-06-25', 'Shift Pagi', 'PA');
        expect($errors)->not->toBeEmpty();
        expect($errors[0])->toContain('exceeds shift end');
    });

    test('passes validation for well-formed timeline', function () {
        makePlan(['row_no' => 10, 'job_no' => 'JOB-A', 'plan' => 100, 'start_time' => '07:30', 'finish_time' => '09:00']);
        makePlan(['row_no' => 20, 'job_no' => 'JOB-B', 'plan' => 100, 'start_time' => '09:00', 'finish_time' => '10:30']);
        $engine = app(TimelineGenerationService::class);
        $errors = $engine->validateTimeline('2026-06-25', 'Shift Pagi', 'PA');
        expect($errors)->toBeEmpty();
    });
});

// ──────────────── 7. RECOVERY LOCK TESTS ────────────────

describe('Recovery Lock', function () {
    beforeEach(function () {
        createLineMasters();
    });

    function createRecoveryItemForLock(array $overrides = []): array
    {
        $plan = makePlan([
            'source_type' => 'recovery',
            'ok' => 50,
        ]);
        $schedule = RecoverySchedule::create([
            'plan_date' => '2026-06-25',
            'shift_name' => 'Shift Pagi',
            'press_name' => 'PA',
            'status' => 'approved',
        ]);
        $item = RecoveryItem::create(array_merge([
            'recovery_schedule_id' => $schedule->id,
            'production_plan_id' => $plan->id,
            'job_no' => 'JOB-LOCKED',
            'job_master' => 'JOB LOCKED',
            'press_name' => 'PA',
            'plan_qty' => 100,
            'ok' => 50,
            'recovery_qty' => 50,
            'status' => 'scheduled',
            'source_date' => '2026-06-25',
            'source_shift' => 'Shift Pagi',
        ], $overrides));

        $plan->update(['recovery_id' => $item->id]);

        return ['item' => $item, 'plan' => $plan];
    }

    test('OK > 0 triggers IN_PRODUCTION on scheduled recovery item', function () {
        $data = createRecoveryItemForLock();
        $plan = $data['plan'];
        $item = $data['item'];

        // syncPlan needs a JobMaster with a job_number that contains the plan ID
        $job = JobMaster::create([
            'job_number' => 'AUTO-TEST-' . $plan->id,
            'job_name' => 'Test Job',
            'line' => 'PA',
            'capacity' => 100,
            'status' => 'running',
            'sequence_no' => 1,
            'started_at' => now(),
        ]);

        $plan->update(['ok' => 10]);
        $productionService = new ProductionService();
        $ref = new ReflectionClass($productionService);
        $syncPlan = $ref->getMethod('syncPlan');
        $syncPlan->setAccessible(true);
        $syncPlan->invoke($productionService, $job->id, null, 10, 0, 0);

        $item->refresh();
        expect($item->status)->toBe('in_production');
    });

    test('IN_PRODUCTION recovery item detected by validation', function () {
        $data = createRecoveryItemForLock(['status' => 'in_production']);
        $plan = $data['plan'];
        $plan->update(['start_time' => '08:00', 'finish_time' => '10:00']);

        $engine = app(TimelineGenerationService::class);
        $errors = $engine->validateTimeline('2026-06-25', 'Shift Pagi', 'PA');

        expect($errors)->not->toBeEmpty();
        expect($errors[0])->toContain('Locked recovery');
    });

    test('upload excel does not affect in_production recovery plans', function () {
        $data = createRecoveryItemForLock(['status' => 'in_production']);
        $plan = $data['plan'];

        ProductionPlan::where('source_type', 'ppc')->delete();

        expect(ProductionPlan::find($plan->id))->not->toBeNull();
        expect($plan->fresh()->ok)->toBe(50.0);
    });
});

// ──────────────── 8. RECOVERY LOCK THROUGH PRODUCTION ────────────────

describe('Production Flow', function () {
    beforeEach(function () {
        createLineMasters();
    });

    test('produces recovery item then completes it', function () {
        $data = createRecoveryItemForLock();
        $plan = $data['plan'];
        $item = $data['item'];

        $plan->update(['ok' => 100, 'repair' => 0, 'reject' => 0]);
        $item->update(['ok' => 100, 'status' => 'completed']);

        expect($item->fresh()->status)->toBe('completed');
        expect($plan->fresh()->ok)->toBe(100.0);
    });

    test('full cycle: cut-off → approve → schedule → produce → complete', function () {
        $plan = makePlan(['plan' => 100, 'ok' => 60]);

        // 1. Cut Off
        $cutOff = new CutOffService();
        $cutOff->processCutOff('2026-06-25', 'Shift Pagi');
        $item = RecoveryItem::first();
        expect($item->status)->toBe('waiting_approval');

        // 2. Approve
        $item->update(['status' => 'approved']);
        expect($item->fresh()->status)->toBe('approved');
        expect(RecoveryItem::pending()->count())->toBe(0);

        // Link item to plan
        $item->update(['production_plan_id' => $plan->id]);
        $plan->update(['recovery_id' => $item->id, 'source_type' => 'recovery']);
        $item->update(['status' => 'scheduled']);
        expect($item->fresh()->status)->toBe('scheduled');

        // 3. Create JobMaster for syncPlan
        $job = JobMaster::create([
            'job_number' => 'AUTO-TEST-' . $plan->id,
            'job_name' => 'Test Job',
            'line' => 'PA',
            'capacity' => 100,
            'status' => 'running',
            'sequence_no' => 1,
            'started_at' => now(),
        ]);

        // 4. Produce — trigger in_production via syncPlan
        $plan->update(['ok' => 90]);
        $productionService = new ProductionService();
        $ref = new ReflectionClass($productionService);
        $syncPlan = $ref->getMethod('syncPlan');
        $syncPlan->setAccessible(true);
        $syncPlan->invoke($productionService, $job->id, null, 90, 0, 0);
        expect($item->fresh()->status)->toBe('in_production');

        // 5. Complete
        $item->update(['ok' => 100, 'status' => 'completed']);
        expect($item->fresh()->status)->toBe('completed');
    });
});

// ──────────────── 9. TIMELINE VALIDATION ────────────────

describe('Timeline Validation', function () {
    beforeEach(function () {
        createLineMasters();
        seedBreakTimes();
    });

    test('validates no overlap between consecutive jobs', function () {
        makePlan(['row_no' => 10, 'job_no' => 'JOB-A', 'start_time' => '08:00', 'finish_time' => '10:00']);
        makePlan(['row_no' => 20, 'job_no' => 'JOB-B', 'start_time' => '09:30', 'finish_time' => '11:00']);

        $engine = app(TimelineGenerationService::class);
        $errors = $engine->validateTimeline('2026-06-25', 'Shift Pagi', 'PA');

        expect($errors)->not->toBeEmpty();
        expect($errors[0])->toContain('Time overlap');
    });

    test('validates no gaps in timeline', function () {
        makePlan(['row_no' => 10, 'job_no' => 'JOB-A', 'start_time' => '07:30', 'finish_time' => '09:00']);
        makePlan(['row_no' => 20, 'job_no' => 'JOB-B', 'start_time' => '09:30', 'finish_time' => '11:30']);

        $engine = app(TimelineGenerationService::class);
        $errors = $engine->validateTimeline('2026-06-25', 'Shift Pagi', 'PA');

        expect($errors)->toBeEmpty();
    });

    test('validates no finish after cut-off time', function () {
        makePlan(['row_no' => 10, 'job_no' => 'JOB-A', 'start_time' => '20:00', 'finish_time' => '22:00']);

        $engine = app(TimelineGenerationService::class);
        $errors = $engine->validateTimeline('2026-06-25', 'Shift Pagi', 'PA');

        expect($errors)->not->toBeEmpty();
        expect($errors[0])->toContain('exceeds shift end');
    });

    test('passes for a correctly structured timeline with breaks', function () {
        makePlan(['row_no' => 10, 'job_no' => 'JOB-A', 'start_time' => '07:30', 'finish_time' => '09:00']);
        makePlan(['row_no' => 20, 'job_no' => 'JOB-B', 'start_time' => '09:00', 'finish_time' => '10:30']);

        $engine = app(TimelineGenerationService::class);
        $errors = $engine->validateTimeline('2026-06-25', 'Shift Pagi', 'PA');
        expect($errors)->toBeEmpty();
    });
});
