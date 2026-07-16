<?php

use App\Services\ProductionMetricsService;

it('calculates OEE with ideal values', function () {
    // Perfect OEE: no downtime, no dandori, no break, pressTime = operatingTime, quality = 100%
    $result = ProductionMetricsService::calculateOee(
        workTimeDuration: 480.0,    // 8 hour shift
        breakDuration: 0.0,         // no break
        downtime: 0.0,              // no downtime
        dandori: 0.0,               // no setup
        pressTime: 480.0,           // ideal press time = operating time
        totalStroke: 4800,          // 4800 pieces
        actualGood: 4800,           // all good
    );

    expect($result['availability'])->toBe(1.0);
    expect($result['performance'])->toBe(1.0);
    expect((float) $result['quality'])->toBe(1.0);
    expect($result['oee'])->toBe(100.0);
    expect($result['planned_production_time'])->toBe(480.0);
    expect($result['operating_time'])->toBe(480.0);
    expect($result['availability_loss'])->toBe(0.0);
});

it('calculates OEE with availability loss (downtime + dandori)', function () {
    $result = ProductionMetricsService::calculateOee(
        workTimeDuration: 480.0,
        breakDuration: 30.0,        // 30 min break
        downtime: 40.0,             // 40 min breakdown
        dandori: 20.0,              // 20 min setup
        pressTime: 390.0,           // ideal press time
        totalStroke: 3900,
        actualGood: 3900,
    );

    // Planned production = 480 - 30 = 450
    expect($result['planned_production_time'])->toBe(450.0);
    // Operating time = 450 - 40 - 20 = 390
    expect($result['operating_time'])->toBe(390.0);
    // Availability = 390 / 450 = 0.8667
    expect($result['availability'])->toBe(390.0 / 450.0);
    expect($result['availability_loss'])->toBe(60.0);
});

it('calculates OEE with performance loss (pressTime < operatingTime)', function () {
    $result = ProductionMetricsService::calculateOee(
        workTimeDuration: 480.0,
        breakDuration: 0.0,
        downtime: 0.0,
        dandori: 0.0,
        pressTime: 360.0,           // ideal = 360 min (running slower)
        totalStroke: 3600,
        actualGood: 3600,
    );

    // operatingTime = 480 - 0 - 0 = 480
    expect($result['operating_time'])->toBe(480.0);
    // performance = 360 / 480 = 0.75
    expect($result['performance'])->toBe(360.0 / 480.0);
    // oee = 1.0 * 0.75 * 1.0 * 100 = 75
    expect($result['oee'])->toBe(75.0);
});

it('calculates OEE with quality loss', function () {
    $result = ProductionMetricsService::calculateOee(
        workTimeDuration: 480.0,
        breakDuration: 0.0,
        downtime: 0.0,
        dandori: 0.0,
        pressTime: 480.0,
        totalStroke: 4800,
        actualGood: 4560,           // 240 reject/repair
    );

    expect((float) $result['quality'])->toBe(4560.0 / 4800.0);
    expect($result['oee'])->toBe(1.0 * 1.0 * (4560.0 / 4800.0) * 100.0);
});

it('clamps values to max 1.0', function () {
    // performance > 100% should be clamped to 1.0
    $result = ProductionMetricsService::calculateOee(
        workTimeDuration: 480.0,
        breakDuration: 0.0,
        downtime: 0.0,
        dandori: 0.0,
        pressTime: 480.0,
        totalStroke: 4800,
        actualGood: 4800,
    );

    expect($result['availability'])->toBeLessThanOrEqual(1.0);
    expect($result['performance'])->toBeLessThanOrEqual(1.0);
    expect($result['quality'])->toBeLessThanOrEqual(1.0);
});

it('handles zero totalStroke gracefully', function () {
    $result = ProductionMetricsService::calculateOee(
        workTimeDuration: 480.0,
        breakDuration: 0.0,
        downtime: 0.0,
        dandori: 0.0,
        pressTime: 0.0,
        totalStroke: 0,
        actualGood: 0,
    );

    expect($result['availability'])->toBe(1.0);
    expect($result['performance'])->toBe(0.0);
    expect($result['quality'])->toBe(0.0);
    expect($result['oee'])->toBe(0.0);
});

it('handles zero workTimeDuration gracefully', function () {
    $result = ProductionMetricsService::calculateOee(
        workTimeDuration: 0.0,
        breakDuration: 0.0,
        downtime: 0.0,
        dandori: 0.0,
        pressTime: 0.0,
        totalStroke: 0,
        actualGood: 0,
    );

    expect($result['availability'])->toBe(0.0);
    expect($result['performance'])->toBe(0.0);
    expect($result['quality'])->toBe(0.0);
    expect($result['oee'])->toBe(0.0);
});

it('matches real-world example', function () {
    // Realistic shift: 8 hours = 480 min
    // Break: 30 min, Downtime: 45 min, Setup: 25 min
    // Ideal press time: 350 min (produced 3500 pcs at 6 sec CT)
    // Produced: 3300 good, 100 repair, 100 reject = 3500 total
    $result = ProductionMetricsService::calculateOee(
        workTimeDuration: 480.0,
        breakDuration: 30.0,
        downtime: 45.0,
        dandori: 25.0,
        pressTime: 350.0,           // (3500 * 6) / 60 = 350 min
        totalStroke: 3500,
        actualGood: 3300,
    );

    // Planned production = 480 - 30 = 450
    // Operating = 450 - 45 - 25 = 380
    // A = 380/450 ≈ 0.8444
    // P = 350/380 ≈ 0.9211
    // Q = 3300/3500 ≈ 0.9429
    // OEE = 0.8444 * 0.9211 * 0.9429 * 100 ≈ 73.3

    $expectedA = 380.0 / 450.0;
    $expectedP = 350.0 / 380.0;
    $expectedQ = 3300.0 / 3500.0;
    $expectedOee = $expectedA * $expectedP * $expectedQ * 100.0;

    expect($result['availability'])->toBe($expectedA);
    expect($result['performance'])->toBe($expectedP);
    expect($result['quality'])->toBe($expectedQ);
    expect($result['oee'])->toBe($expectedOee);
    expect($result['availability_loss'])->toBe(70.0);
});
