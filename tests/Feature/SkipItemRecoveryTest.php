<?php

use App\Models\JobMaster;
use App\Models\LineMaster;
use App\Models\ProductionPlan;
use App\Models\ProductionSession;
use App\Models\RecoveryItem;
use App\Models\RecoverySchedule;
use App\Models\User;
use App\Notifications\ItemTidakTercapaiNotification;
use App\Services\CutOffService;
use App\Services\ProductionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

afterEach(function () {
    Carbon::setTestNow();
});

function srPlan(int $id, string $jobNo, int $planQty, string $line = 'LINE-A', array $extra = []): ProductionPlan
{
    LineMaster::firstOrCreate(
        ['line_code' => 'L1'],
        ['line_name' => $line, 'kapasitas' => 100, 'type_line' => 'printing']
    );

    $plan = new ProductionPlan();
    $plan->id = $id;
    $plan->line_master_id = LineMaster::where('line_code', 'L1')->first()->id;
    $plan->plan_date = '2026-06-25';
    $plan->shift_name = 'Shift Pagi';
    $plan->press_name = $line;
    $plan->row_type = 'job';
    $plan->row_no = 1;
    $plan->job_no = $jobNo;
    $plan->job_master = $jobNo . ' MASTER';
    $plan->plan = $planQty;
    $plan->ok = 0;
    $plan->ct_detik = 10;
    $plan->dct = 5;
    $plan->total_mesin = 1;
    $plan->status = 'pending';
    $plan->source_type = 'ppc';
    foreach ($extra as $k => $v) {
        $plan->$k = $v;
    }
    $plan->save();

    return $plan;
}

function srJob(string $jobNo, int $planId, string $line = 'LINE-A', string $status = 'pending'): JobMaster
{
    return JobMaster::create([
        'job_number' => $jobNo . '-' . $planId,
        'job_name' => 'Item ' . $jobNo,
        'line' => $line,
        'capacity' => 100,
        'status' => $status,
        'sequence_no' => 1,
    ]);
}

function srRunningSession(JobMaster $job): void
{
    ProductionSession::create([
        'job_master_id' => $job->id,
        'work_date' => '2026-06-25',
        'status' => 'running',
        'start_time' => Carbon::now(),
    ]);
}

function srPpcUser(): User
{
    return User::create([
        'name' => 'PPC User',
        'nrp' => 'PPC-001',
        'password' => bcrypt('secret'),
        'role' => 'ppc',
    ]);
}

function srRecoverySchedule(string $press): RecoverySchedule
{
    return RecoverySchedule::create([
        'plan_date' => '2026-06-25',
        'shift_name' => 'Shift Pagi',
        'press_name' => $press,
        'status' => 'waiting_approval',
    ]);
}

describe('finishJob skipped actions', function () {
    beforeEach(function () {
        Carbon::setTestNow('2026-06-25 10:00:00');
    });

    test('finishing short of plan still auto-queues the current job to recovery (Case A unchanged)', function () {
        Notification::fake();
        $user = srPpcUser();

        $plan = srPlan(501, 'JOB-A', 100);
        $job = srJob('JOB-A', $plan->id, 'LINE-A', 'running');
        srRunningSession($job);

        (new ProductionService())->finishJob($job->id, null, false, 60, 0, 0);

        $item = RecoveryItem::where('job_no', $job->job_number)->first();
        expect($item)->not->toBeNull();
        expect($item->status)->toBe('waiting_approval');
        expect((float) $item->recovery_qty)->toBe(40.0);

        expect($plan->fresh()->skipped_at)->toBeNull();

        Notification::assertSentTo($user, ItemTidakTercapaiNotification::class);
    });

    test('skipped item flagged "continue" is queued, marked skipped, and notifies PPC', function () {
        Notification::fake();
        $user = srPpcUser();

        $currentPlan = srPlan(601, 'JOB-CUR', 0);
        $current = srJob('JOB-CUR', $currentPlan->id, 'LINE-A', 'running');
        srRunningSession($current);

        $skippedPlan = srPlan(602, 'JOB-SKIP', 50);
        $skipped = srJob('JOB-SKIP', $skippedPlan->id, 'LINE-A', 'pending');

        $result = (new ProductionService())->finishJob($current->id, 'FINISH_ONLY', false, null, null, null, [$skipped->id => 'continue']);

        $item = RecoveryItem::where('job_no', $skipped->job_number)->first();
        expect($item)->not->toBeNull();
        expect($item->status)->toBe('continue');
        expect((float) $item->recovery_qty)->toBe(50.0);
        expect($result['skipped'])->toHaveCount(1);

        expect($skippedPlan->fresh()->skipped_at)->not->toBeNull();

        Notification::assertSentTo($user, ItemTidakTercapaiNotification::class);
    });

    test('skipped item flagged "recovery" is queued as waiting_approval and notifies PPC', function () {
        Notification::fake();
        $user = srPpcUser();

        $currentPlan = srPlan(611, 'JOB-CUR2', 0);
        $current = srJob('JOB-CUR2', $currentPlan->id, 'LINE-A', 'running');
        srRunningSession($current);

        $skippedPlan = srPlan(612, 'JOB-SKIP2', 80);
        $skipped = srJob('JOB-SKIP2', $skippedPlan->id, 'LINE-A', 'pending');

        (new ProductionService())->finishJob($current->id, 'FINISH_ONLY', false, null, null, null, [$skipped->id => 'recovery']);

        $item = RecoveryItem::where('job_no', $skipped->job_number)->first();
        expect($item)->not->toBeNull();
        expect($item->status)->toBe('waiting_approval');

        expect($skippedPlan->fresh()->skipped_at)->not->toBeNull();

        Notification::assertSentTo($user, ItemTidakTercapaiNotification::class);
    });
});

describe('skipped marker on production plan', function () {
    beforeEach(function () {
        Carbon::setTestNow('2026-06-25 10:00:00');
    });

    test('starting a previously-skipped job clears the skipped marker', function () {
        Notification::fake();
        srPpcUser();

        $skippedPlan = srPlan(621, 'JOB-REWORK', 50, 'LINE-A', ['skipped_at' => now()]);
        $skipped = srJob('JOB-REWORK', $skippedPlan->id, 'LINE-A', 'pending');

        (new ProductionService())->startJob($skipped->id);

        expect($skippedPlan->fresh()->skipped_at)->toBeNull();
        expect($skippedPlan->fresh()->status)->toBe('approved');
    });
});

describe('cut-off carry forward', function () {
    beforeEach(function () {
        Carbon::setTestNow('2026-06-25 10:00:00');
    });

    test('carries "continue" items into the next shift automatically', function () {
        $plan = srPlan(701, 'JOB-CARRY', 50);
        $schedule = srRecoverySchedule('LINE-A');
        $item = RecoveryItem::create([
            'recovery_schedule_id' => $schedule->id,
            'production_plan_id' => $plan->id,
            'job_no' => 'JOB-CARRY-701',
            'job_master' => 'JOB CARRY',
            'press_name' => 'LINE-A',
            'plan_qty' => 50,
            'ok' => 0,
            'recovery_qty' => 50,
            'ct_detik' => 10,
            'dct' => 5,
            'total_mesin' => 1,
            'status' => 'continue',
            'original_date' => '2026-06-25',
            'original_shift_name' => 'Shift Pagi',
            'source_date' => '2026-06-25',
            'source_shift' => 'Shift Pagi',
            'queued_at' => Carbon::now(),
        ]);

        $stats = (new CutOffService())->processCutOff('2026-06-25', 'Shift Pagi');

        expect($stats['carried'])->toBe(1);
        expect($stats['created'])->toBe(0);

        $item->refresh();
        expect($item->status)->toBe('scheduled');
        expect($item->source_shift)->toBe('Shift Malam');

        $newPlan = ProductionPlan::whereDate('plan_date', '2026-06-25')
            ->where('shift_name', 'Shift Malam')
            ->where('recovery_id', $item->id)
            ->first();
        expect($newPlan)->not->toBeNull();
        expect($newPlan->source_type)->toBe('recovery');
        expect((float) $newPlan->plan)->toBe(50.0);
    });

    test('cancels "continue" item when its plan was already achieved', function () {
        $plan = srPlan(702, 'JOB-DONE', 50, 'LINE-A', ['ok' => 50]);
        $schedule = srRecoverySchedule('LINE-A');
        $item = RecoveryItem::create([
            'recovery_schedule_id' => $schedule->id,
            'production_plan_id' => $plan->id,
            'job_no' => 'JOB-DONE-702',
            'job_master' => 'JOB DONE',
            'press_name' => 'LINE-A',
            'plan_qty' => 50,
            'ok' => 50,
            'recovery_qty' => 0,
            'ct_detik' => 10,
            'dct' => 5,
            'total_mesin' => 1,
            'status' => 'continue',
            'original_date' => '2026-06-25',
            'original_shift_name' => 'Shift Pagi',
            'source_date' => '2026-06-25',
            'source_shift' => 'Shift Pagi',
            'queued_at' => Carbon::now(),
        ]);

        $stats = (new CutOffService())->processCutOff('2026-06-25', 'Shift Pagi');

        expect($stats['cancelled'])->toBe(1);
        expect($item->fresh()->status)->toBe('completed');
    });
});

describe('stale plan id resolution', function () {
    beforeEach(function () {
        Carbon::setTestNow('2026-06-25 10:00:00');
    });

    test('stale embedded plan id falls back to the current plan without crashing', function () {
        Notification::fake();
        srPpcUser();

        $plan = srPlan(801, 'GT-2518', 100);
        $job = JobMaster::create([
            'job_number' => 'GT-2518-99999',
            'job_name' => 'K-1041',
            'line' => 'LINE-A',
            'target_qty' => 100,
            'capacity' => 100,
            'status' => 'running',
            'sequence_no' => 1,
        ]);
        srRunningSession($job);

        $result = (new ProductionService())->finishJob($job->id, null, false, 75, 0, 0);

        $item = RecoveryItem::where('job_no', $job->job_number)->first();
        expect($item)->not->toBeNull();
        expect($item->production_plan_id)->toBe($plan->id);
        expect((float) $item->recovery_qty)->toBe(25.0);
        expect($job->fresh()->status)->toBe('complete');
        expect($result['mismatch'])->not->toBeNull();
    });

    test('unresolvable plan still finishes the job and creates a recovery item without a plan link', function () {
        Notification::fake();
        srPpcUser();

        $job = JobMaster::create([
            'job_number' => 'LOST-99999',
            'job_name' => 'Item Tidak Dijadwalkan',
            'line' => 'LINE-A',
            'target_qty' => 100,
            'capacity' => 100,
            'status' => 'running',
            'sequence_no' => 1,
        ]);
        srRunningSession($job);

        (new ProductionService())->finishJob($job->id, null, false, 60, 0, 0);

        $item = RecoveryItem::where('job_no', $job->job_number)->first();
        expect($item)->not->toBeNull();
        expect($item->production_plan_id)->toBeNull();
        expect((float) $item->recovery_qty)->toBe(40.0);
        expect($item->status)->toBe('waiting_approval');
        expect($job->fresh()->status)->toBe('complete');
    });
});
