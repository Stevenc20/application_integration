<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Operational\InputHarianController;

// 1. Create or get leader b user
$user = User::updateOrCreate(
    ['nrp' => '88888'],
    [
        'name' => 'Leader B Test',
        'password' => bcrypt('password123'),
        'role' => 'leader b',
        'is_active' => 1
    ]
);

// 2. Act as the user
auth()->login($user);

// 3. Make a request to the controller
$request = Request::create('/operational/input-harian', 'GET');
$controller = app(InputHarianController::class);

echo "Simulating Request from Leader B...\n";
$controller->index($request);

// Verify request merged values
$selectedLine = $request->get('line');
echo "Default selected line from Request: {$selectedLine}\n";

if ($selectedLine === 'Line B') {
    echo "SUCCESS: Automatically defaulted selected line filter to Line B for Leader B!\n";
} else {
    echo "FAILED: Selected line is {$selectedLine}\n";
}

// 4. Test the Sorting logic in Blade via helper array mapping
$lines = collect(['Line A', 'Line B', 'Line C', 'Line D', 'Shearing', 'Handwork']);
$userLine = 'Line B';

$sortedLines = $lines->sortBy(function($l) use ($userLine) {
    return $l === $userLine ? 0 : 1;
})->values()->toArray();

echo "Sorted Lines Tab Sequence: " . implode(', ', $sortedLines) . "\n";
if ($sortedLines[0] === 'Line B' && $sortedLines[1] === 'Line A') {
    echo "SUCCESS: Sequence is perfectly sorted to B, A, C, D...\n";
} else {
    echo "FAILED: Sequence is " . implode(', ', $sortedLines) . "\n";
}
