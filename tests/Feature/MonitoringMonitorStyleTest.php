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
