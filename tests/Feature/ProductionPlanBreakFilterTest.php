<?php

use App\Models\LineMaster;
use App\Models\MasterBreakTime;
use App\Models\ProductionPlan;
use App\Services\BreakTimelineValidator;
use App\Services\TimelineGenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;

uses(RefreshDatabase::class);

function bpSeedBreakTimes(): void
{
    $rows = [
        ['hari' => 'senin', 'waktu_mulai' => '12:00', 'waktu_selesai' => '12:45', 'type' => 'istirahat', 'label' => 'ISTIRAHAT SIANG', 'sort_order' => 10, 'shift' => 'Shift Pagi'],
        ['hari' => 'jumat', 'waktu_mulai' => '11:45', 'waktu_selesai' => '12:45', 'type' => 'istirahat', 'label' => 'ISTIRAHAT JUMAT', 'sort_order' => 10, 'shift' => 'Shift Pagi'],
        ['hari' => 'semua', 'waktu_mulai' => '15:15', 'waktu_selesai' => '15:30', 'type' => 'cinkorak', 'label' => 'CINGKORAK', 'sort_order' => 20, 'shift' => 'Shift Pagi'],
        ['hari' => 'semua', 'waktu_mulai' => '16:30', 'waktu_selesai' => '16:45', 'type' => 'istirahat', 'label' => 'BREAKTIME', 'sort_order' => 30, 'shift' => 'Shift Pagi'],
        ['hari' => 'semua', 'waktu_mulai' => '18:00', 'waktu_selesai' => '18:30', 'type' => 'istirahat', 'label' => 'ISTIRAHAT SORE', 'sort_order' => 40, 'shift' => 'Shift Pagi'],
    ];
    foreach ($rows as $r) {
        MasterBreakTime::create(array_merge($r, ['is_active' => true]));
    }
}

function bpCreateLineMasters(): void
{
    LineMaster::firstOrCreate(
        ['line_code' => 'PA'],
        ['line_name' => 'PA', 'kapasitas' => 100, 'type_line' => 'printing']
    );
}

function bpLineMasterId(): int
{
    return LineMaster::where('line_code', 'PA')->first()->id;
}

function bpPlan(array $overrides = []): ProductionPlan
{
    $defaults = [
        'line_master_id' => bpLineMasterId(),
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
        'ct_detik' => 10,
        'dct' => 5,
        'source_type' => 'ppc',
    ];
    return ProductionPlan::create(array_merge($defaults, $overrides));
}

function bpBreak(array $overrides = []): ProductionPlan
{
    $defaults = [
        'line_master_id' => bpLineMasterId(),
        'plan_date' => '2026-06-25',
        'shift_name' => 'Shift Pagi',
        'press_name' => 'PA',
        'hari' => 'kamis',
        'tgl' => '25/06/2026',
        'jam' => 'S1',
        'revisi' => '0',
        'row_no' => 100,
        'row_type' => 'break',
        'job_no' => 'ISTIRAHAT SORE',
        'job_master' => 'ISTIRAHAT SORE',
        'start_time' => '18:00',
        'finish_time' => '18:30',
        'dct' => 30,
        'source_type' => 'ppc',
    ];
    return ProductionPlan::create(array_merge($defaults, $overrides));
}

function bpFilterBreakRows(Collection $plans): Collection
{
    $validator = app(BreakTimelineValidator::class);

    $keptIds = $plans
        ->groupBy(fn ($p) => (string) ($p->press_name ?? '') . '|' . (string) ($p->shift_name ?? ''))
        ->flatMap(fn (Collection $group) => $validator->filterOverlappingBreaks(
            $validator->filterValidPlans($group)
        ))
        ->pluck('id');

    return $plans->filter(fn ($p) => $keptIds->contains($p->id))->values();
}

// ──────────────── BreakTimelineValidator span filtering ────────────────

describe('BreakTimelineValidator span filtering', function () {
    beforeEach(function () {
        bpCreateLineMasters();
    });

    test('drops breaks outside the active job span', function () {
        $job = bpPlan(['row_no' => 10, 'start_time' => '17:10', 'finish_time' => '17:32']);
        $breakEarly = bpBreak(['job_no' => 'BREAKTIME', 'start_time' => '16:30', 'finish_time' => '16:45', 'dct' => 15]);
        $breakLate = bpBreak(['job_no' => 'ISTIRAHAT SORE', 'start_time' => '18:00', 'finish_time' => '18:30', 'dct' => 30]);
        $breakInSpan = bpBreak(['job_no' => 'CLEANING', 'start_time' => '17:15', 'finish_time' => '17:25', 'dct' => 10]);

        $plans = ProductionPlan::whereDate('plan_date', '2026-06-25')->get();
        $filtered = app(BreakTimelineValidator::class)->filterValidPlans($plans);

        expect($filtered->pluck('id')->contains($breakEarly->id))->toBeFalse();
        expect($filtered->pluck('id')->contains($breakLate->id))->toBeFalse();
        expect($filtered->pluck('id')->contains($breakInSpan->id))->toBeTrue();
        expect($filtered->pluck('id')->contains($job->id))->toBeTrue();
    });

    test('keeps a break the schedule actually reaches', function () {
        $job = bpPlan(['row_no' => 10, 'start_time' => '17:10', 'finish_time' => '18:40']);
        $break = bpBreak(['job_no' => 'ISTIRAHAT SORE', 'start_time' => '18:00', 'finish_time' => '18:30', 'dct' => 30]);

        $plans = ProductionPlan::whereDate('plan_date', '2026-06-25')->get();
        $filtered = app(BreakTimelineValidator::class)->filterValidPlans($plans);

        expect($filtered->pluck('id')->contains($break->id))->toBeTrue();
        expect($filtered->pluck('id')->contains($job->id))->toBeTrue();
    });

    test('jobs without start_time do not skew the span', function () {
        bpPlan(['row_no' => 10, 'start_time' => '17:10', 'finish_time' => '17:32']);
        $pending = bpPlan(['row_no' => 20, 'job_no' => 'PENDING-001', 'job_master' => 'PENDING JOB', 'start_time' => null, 'finish_time' => null]);
        $breakLate = bpBreak(['job_no' => 'ISTIRAHAT SORE', 'start_time' => '18:00', 'finish_time' => '18:30', 'dct' => 30]);
        $breakInSpan = bpBreak(['job_no' => 'CLEANING', 'start_time' => '17:15', 'finish_time' => '17:25', 'dct' => 10]);

        $plans = ProductionPlan::whereDate('plan_date', '2026-06-25')->get();
        $filtered = app(BreakTimelineValidator::class)->filterValidPlans($plans);

        expect($filtered->pluck('id')->contains($breakLate->id))->toBeFalse();
        expect($filtered->pluck('id')->contains($breakInSpan->id))->toBeTrue();
        expect($filtered->pluck('id')->contains($pending->id))->toBeTrue();
    });

    test('returns empty when collection has breaks but no jobs', function () {
        bpBreak(['job_no' => 'ISTIRAHAT SORE', 'start_time' => '18:00', 'finish_time' => '18:30', 'dct' => 30]);

        $plans = ProductionPlan::whereDate('plan_date', '2026-06-25')->get();
        $filtered = app(BreakTimelineValidator::class)->filterValidPlans($plans);

        expect($filtered)->toHaveCount(0);
    });

    test('filters per press+shift so one section never leaks into another', function () {
        // Press A: 17:10–17:32 (does NOT reach 18:00)
        bpPlan(['press_name' => 'PA', 'row_no' => 10, 'start_time' => '17:10', 'finish_time' => '17:32']);
        $breakA = bpBreak(['press_name' => 'PA', 'job_no' => 'ISTIRAHAT SORE', 'start_time' => '18:00', 'finish_time' => '18:30', 'dct' => 30]);

        // Press B: jobs segmented around the break (17:00–18:00, 18:30–19:00)
        // so SORE 18:00–18:30 legitimately sits between two jobs.
        LineMaster::firstOrCreate(
            ['line_code' => 'PB'],
            ['line_name' => 'PB', 'kapasitas' => 100, 'type_line' => 'printing']
        );
        $lmB = LineMaster::where('line_code', 'PB')->first()->id;
        ProductionPlan::create([
            'line_master_id' => $lmB,
            'plan_date' => '2026-06-25',
            'shift_name' => 'Shift Pagi',
            'press_name' => 'PB',
            'hari' => 'kamis',
            'row_no' => 9,
            'row_type' => 'job',
            'job_no' => 'PB-001',
            'job_master' => 'PB JOB',
            'start_time' => '17:00',
            'finish_time' => '18:00',
            'dct' => 5,
            'source_type' => 'ppc',
        ]);
        $breakB = ProductionPlan::create([
            'line_master_id' => $lmB,
            'plan_date' => '2026-06-25',
            'shift_name' => 'Shift Pagi',
            'press_name' => 'PB',
            'hari' => 'kamis',
            'row_no' => 100,
            'row_type' => 'break',
            'job_no' => 'ISTIRAHAT SORE',
            'job_master' => 'ISTIRAHAT SORE',
            'start_time' => '18:00',
            'finish_time' => '18:30',
            'dct' => 30,
            'source_type' => 'ppc',
        ]);
        ProductionPlan::create([
            'line_master_id' => $lmB,
            'plan_date' => '2026-06-25',
            'shift_name' => 'Shift Pagi',
            'press_name' => 'PB',
            'hari' => 'kamis',
            'row_no' => 11,
            'row_type' => 'job',
            'job_no' => 'PB-002',
            'job_master' => 'PB JOB 2',
            'start_time' => '18:30',
            'finish_time' => '19:00',
            'dct' => 5,
            'source_type' => 'ppc',
        ]);

        // Simulate controller behaviour: group by press+shift, filter each group
        $plans = ProductionPlan::whereDate('plan_date', '2026-06-25')->get();
        $filtered = bpFilterBreakRows($plans);

        expect($filtered->pluck('id')->contains($breakA->id))->toBeFalse();
        expect($filtered->pluck('id')->contains($breakB->id))->toBeTrue();
    });

    test('drops a break that overlaps a running job (reported PRESS A case)', function () {
        // Reported data 2026-08-05: SORE break row kept morning times 09:49–10:19
        // which overlaps job IB-094 WIP (09:49–10:34). Real breaks sit between jobs.
        bpPlan(['row_no' => 3, 'job_no' => 'XP-0078', 'start_time' => '08:59', 'finish_time' => '09:49']);
        bpPlan(['row_no' => 4, 'job_no' => 'IB-094 WIP', 'start_time' => '09:49', 'finish_time' => '10:34']);
        bpPlan(['row_no' => 6, 'job_no' => 'T4023', 'start_time' => '11:45', 'finish_time' => '12:01']);
        $breakSore = bpBreak(['row_no' => 20, 'job_no' => 'ISTIRAHAT SORE', 'start_time' => '09:49', 'finish_time' => '10:19', 'dct' => 30]);
        $breakSiang = bpBreak(['row_no' => 7, 'job_no' => 'ISTIRAHAT SIANG', 'start_time' => '12:01', 'finish_time' => '12:41', 'dct' => 40]);

        $plans = ProductionPlan::whereDate('plan_date', '2026-06-25')->get();
        $filtered = bpFilterBreakRows($plans);

        expect($filtered->pluck('id')->contains($breakSore->id))->toBeFalse();
        expect($filtered->pluck('id')->contains($breakSiang->id))->toBeTrue();
    });

    test('keeps a break that sits exactly between two jobs', function () {
        bpPlan(['row_no' => 1, 'start_time' => '17:10', 'finish_time' => '17:20']);
        $break = bpBreak(['row_no' => 2, 'job_no' => 'CLEANING', 'start_time' => '17:20', 'finish_time' => '17:30', 'dct' => 10]);
        bpPlan(['row_no' => 3, 'start_time' => '17:30', 'finish_time' => '17:40']);

        $plans = ProductionPlan::whereDate('plan_date', '2026-06-25')->get();
        $filtered = bpFilterBreakRows($plans);

        expect($filtered->pluck('id')->contains($break->id))->toBeTrue();
    });

    test('single long job spanning a break time hides that break', function () {
        // Edge case accepted: a lone job running across the break hour leaves
        // no room to place the break between jobs, so it is hidden.
        $job = bpPlan(['row_no' => 10, 'start_time' => '17:10', 'finish_time' => '18:40']);
        $job2 = bpPlan(['row_no' => 11, 'start_time' => '18:40', 'finish_time' => '19:00']);
        $break = bpBreak(['job_no' => 'ISTIRAHAT SORE', 'start_time' => '18:00', 'finish_time' => '18:30', 'dct' => 30]);

        $plans = ProductionPlan::whereDate('plan_date', '2026-06-25')->get();
        $filtered = bpFilterBreakRows($plans);

        expect($filtered->pluck('id')->contains($break->id))->toBeFalse();
        expect($filtered->pluck('id')->contains($job->id))->toBeTrue();
        expect($filtered->pluck('id')->contains($job2->id))->toBeTrue();
    });
});

// ──────────────── ensureBreaksExist span guard ────────────────

describe('ensureBreaksExist span guard', function () {
    beforeEach(function () {
        bpCreateLineMasters();
        bpSeedBreakTimes();
    });

    test('does not inject breaks the schedule never reaches', function () {
        bpPlan(['row_no' => 10, 'start_time' => '17:10', 'finish_time' => '17:32']);

        $service = app(TimelineGenerationService::class);
        $service->ensureBreaksExist('2026-06-25', 'Shift Pagi', 'PA', bpLineMasterId(), 'kamis');

        expect(ProductionPlan::where('row_type', 'break')->count())->toBe(0);
    });

    test('injects only the break the schedule reaches', function () {
        bpPlan(['row_no' => 10, 'start_time' => '16:45', 'finish_time' => '18:20']);

        $service = app(TimelineGenerationService::class);
        $service->ensureBreaksExist('2026-06-25', 'Shift Pagi', 'PA', bpLineMasterId(), 'kamis');

        $breakLabels = ProductionPlan::where('row_type', 'break')->pluck('job_no');
        expect($breakLabels)->toContain('ISTIRAHAT SORE');
        expect($breakLabels)->not->toContain('BREAKTIME');
        expect($breakLabels)->not->toContain('CINGKORAK');
    });

    test('injects all breaks when the schedule reaches them all', function () {
        bpPlan(['row_no' => 10, 'start_time' => '15:00', 'finish_time' => '18:40']);

        $service = app(TimelineGenerationService::class);
        $service->ensureBreaksExist('2026-06-25', 'Shift Pagi', 'PA', bpLineMasterId(), 'kamis');

        $breakLabels = ProductionPlan::where('row_type', 'break')->pluck('job_no');
        expect($breakLabels)->toContain('CINGKORAK');
        expect($breakLabels)->toContain('BREAKTIME');
        expect($breakLabels)->toContain('ISTIRAHAT SORE');
    });

    test('shift malam breaks around midnight are kept when schedule crosses them', function () {
        // Shift malam job 21:30 → 05:00 next day must still reach 04:45 break
        ProductionPlan::create([
            'line_master_id' => bpLineMasterId(),
            'plan_date' => '2026-06-25',
            'shift_name' => 'Shift Malam',
            'press_name' => 'PA',
            'hari' => 'kamis',
            'row_no' => 10,
            'row_type' => 'job',
            'job_no' => 'MALAM-001',
            'job_master' => 'MALAM JOB',
            'start_time' => '21:30',
            'finish_time' => '05:00',
            'dct' => 5,
            'source_type' => 'ppc',
        ]);

        MasterBreakTime::create([
            'hari' => 'semua',
            'waktu_mulai' => '00:00',
            'waktu_selesai' => '00:45',
            'type' => 'istirahat',
            'label' => 'ISTIRAHAT MALAM',
            'sort_order' => 10,
            'shift' => 'Shift Malam',
            'is_active' => true,
        ]);
        MasterBreakTime::create([
            'hari' => 'semua',
            'waktu_mulai' => '04:45',
            'waktu_selesai' => '05:00',
            'type' => 'istirahat',
            'label' => 'BREAKTIME',
            'sort_order' => 20,
            'shift' => 'Shift Malam',
            'is_active' => true,
        ]);

        $service = app(TimelineGenerationService::class);
        $service->ensureBreaksExist('2026-06-25', 'Shift Malam', 'PA', bpLineMasterId(), 'kamis');

        $breakLabels = ProductionPlan::where('row_type', 'break')->pluck('job_no');
        expect($breakLabels)->toContain('ISTIRAHAT MALAM');
        expect($breakLabels)->toContain('BREAKTIME');
    });
});
