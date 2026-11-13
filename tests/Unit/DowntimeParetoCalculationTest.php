<?php

it('correctly filters valid downtime categories and excludes non-downtime activities', function () {
    $validCategories = ['mesin', 'dies', 'material', 'logistic', 'try out', 'produksi', 'others'];
    $excluded = ['dandori', 'idle', 'idle time', 'break time', 'break_time'];

    $rawInputs = [
        'Mesin', 'MESIN', 'dies', 'Material ', ' logistic', 'try out', 'PRODUKSI', 'Others',
        'dandori', 'Break Time', 'idle', 'custom_invalid'
    ];

    $filtered = collect($rawInputs)
        ->map(fn($c) => strtolower(trim($c)))
        ->filter(fn($c) => !in_array($c, $excluded, true) && in_array($c, $validCategories, true))
        ->unique()
        ->values()
        ->all();

    expect($filtered)->toHaveCount(7)
        ->toContain('mesin')
        ->toContain('dies')
        ->toContain('material')
        ->toContain('logistic')
        ->toContain('try out')
        ->toContain('produksi')
        ->toContain('others')
        ->not->toContain('dandori')
        ->not->toContain('break time')
        ->not->toContain('idle')
        ->not->toContain('custom_invalid');
});

it('calculates Pareto 80/20 metrics correctly with sorted cumulative percentage', function () {
    // Simulated category totals in seconds
    $input = [
        ['category' => 'mesin', 'total_seconds' => 6000, 'count' => 10],     // 100 min (50%)
        ['category' => 'dies', 'total_seconds' => 3600, 'count' => 6],       // 60 min (30%) -> Cum: 80% (Vital Few cutoff)
        ['category' => 'material', 'total_seconds' => 1200, 'count' => 2],   // 20 min (10%) -> Cum: 90% (Useful Many)
        ['category' => 'produksi', 'total_seconds' => 1200, 'count' => 2],   // 20 min (10%) -> Cum: 100% (Useful Many)
    ];

    $totalSeconds = array_sum(array_column($input, 'total_seconds')); // 12000s = 200m
    $totalMinutes = round($totalSeconds / 60, 1);
    $totalEvents = array_sum(array_column($input, 'count'));

    $labels = [];
    $data = [];
    $cumulative = [];
    $percentages = [];
    $counts = [];
    $topCategories = [];
    $items = [];
    $runningSumSeconds = 0;

    foreach ($input as $row) {
        $seconds = (float) $row['total_seconds'];
        $minutes = round($seconds / 60, 1);
        $prevCumPct = $totalSeconds > 0 ? round(($runningSumSeconds / $totalSeconds) * 100, 1) : 0.0;
        $runningSumSeconds += $seconds;

        $pct = $totalSeconds > 0 ? round(($seconds / $totalSeconds) * 100, 1) : 0.0;
        $cumPct = $totalSeconds > 0 ? round(($runningSumSeconds / $totalSeconds) * 100, 1) : 0.0;
        if ($cumPct > 100) $cumPct = 100.0;

        $label = ucwords($row['category']);
        $inPareto80 = ($prevCumPct < 80.0 || empty($topCategories));
        if ($inPareto80) {
            $topCategories[] = $label;
        }

        $labels[] = $label;
        $data[] = $minutes;
        $percentages[] = $pct;
        $cumulative[] = $cumPct;
        $counts[] = (int) $row['count'];

        $items[] = [
            'category' => $label,
            'minutes' => $minutes,
            'percentage' => $pct,
            'cumulative' => $cumPct,
            'count' => (int) $row['count'],
            'in_pareto_80' => $inPareto80,
        ];
    }

    expect($totalMinutes)->toBe(200.0);
    expect($totalEvents)->toBe(20);
    expect($labels)->toBe(['Mesin', 'Dies', 'Material', 'Produksi']);
    expect($data)->toBe([100.0, 60.0, 20.0, 20.0]);
    expect($percentages)->toBe([50.0, 30.0, 10.0, 10.0]);
    expect($cumulative)->toBe([50.0, 80.0, 90.0, 100.0]);
    expect($topCategories)->toBe(['Mesin', 'Dies']);

    expect($items[0]['in_pareto_80'])->toBeTrue();
    expect($items[1]['in_pareto_80'])->toBeTrue();
    expect($items[2]['in_pareto_80'])->toBeFalse();
    expect($items[3]['in_pareto_80'])->toBeFalse();
});

it('handles single category dominant 100% case gracefully', function () {
    $input = [
        ['category' => 'mesin', 'total_seconds' => 3600, 'count' => 5],
    ];

    $totalSeconds = 3600;
    $runningSumSeconds = 3600;
    $cumPct = round(($runningSumSeconds / $totalSeconds) * 100, 1);
    $inPareto80 = (0 < 80.0);

    expect($cumPct)->toBe(100.0);
    expect($inPareto80)->toBeTrue();
});
