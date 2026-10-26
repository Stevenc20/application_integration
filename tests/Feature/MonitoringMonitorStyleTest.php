<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('monitor renders solid reject and repair colors for every line in both themes', function () {
    $user = User::create([
        'name' => 'Supervisor Monitor',
        'nrp' => 'SUP-MON-'.uniqid(),
        'password' => 'password',
        'role' => 'supervisor',
        'is_active' => 1,
    ]);

    $html = $this->actingAs($user)
        ->get(route('supervisor.monitor'))
        ->assertOk()
        ->getContent();

    expect($html)
        ->toContain('#row-REJECT td.val-plan')
        ->toContain('#row-REJECT td.val-curr')
        ->toContain('#row-REJECT td.val-actual')
        ->toContain('body.dark #row-REJECT td.val-plan')
        ->toContain('body.dark #row-REJECT td.val-curr')
        ->toContain('body.dark #row-REJECT td.val-actual')
        ->toContain('#row-REPAIR td.val-plan')
        ->toContain('#row-REPAIR td.val-curr')
        ->toContain('#row-REPAIR td.val-actual')
        ->toContain('body.dark #row-REPAIR td.val-plan')
        ->toContain('body.dark #row-REPAIR td.val-curr')
        ->toContain('body.dark #row-REPAIR td.val-actual')
        ->toContain('background-color: #ef4444')
        ->toContain('background-color: #facc15')
        ->toContain('background-color: #dc2626')
        ->toContain('background-color: #eab308')
        ->toContain('data-line="all"')
        ->toContain('data-line="A"')
        ->toContain('data-line="B"')
        ->toContain('data-line="C"')
        ->toContain('data-line="D"');
});

test('monitor exposes display count, auto-scroll interval, pager, and local persistence controls', function () {
    $user = User::create([
        'name' => 'Supervisor Monitor Display',
        'nrp' => 'SUP-MON-DISPLAY-'.uniqid(),
        'password' => 'password',
        'role' => 'supervisor',
        'is_active' => 1,
    ]);

    $html = $this->actingAs($user)
        ->get(route('supervisor.monitor'))
        ->assertOk()
        ->getContent();

    expect($html)
        ->toContain('data-display-mode="items15"')
        ->toContain('data-display-mode="items20"')
        ->toContain('data-display-mode="auto"')
        ->toContain('data-scroll-interval="15"')
        ->toContain('data-scroll-interval="20"')
        ->toContain('id="displayPager"')
        ->toContain('id="displayPrev"')
        ->toContain('id="displayNext"')
        ->toContain("const DISPLAY_MODE_KEY = 'monitor-display-mode'")
        ->toContain("const DISPLAY_INTERVAL_KEY = 'monitor-scroll-interval'")
        ->toContain('function pauseAutoRotate()')
        ->toContain('function resetDisplaySession()')
        ->toContain('const AUTO_PAGE_SIZE = 10;')
        ->toContain("if (displayMode === 'items15') pageSize = 15;")
        ->toContain("if (displayMode === 'items20') pageSize = 20;");
});
