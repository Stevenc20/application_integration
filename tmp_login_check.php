<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Print every user's login redirect target by calling AuthController directly.
$ctrl = new \App\Http\Controllers\AuthController();

$users = \App\Models\User::with('section', 'position')->where('is_active', 1)->orderBy('role')->get();

$results = [];
foreach ($users as $u) {
    $req = \Illuminate\Http\Request::create('/login', 'POST', [
        'nrp' => $u->nrp,
        'password' => 'password123',
    ]);
    $req->setLaravelSession($app->make('session')->driver());
    \Illuminate\Support\Facades\Auth::logout();

    try {
        $res = $ctrl->loginProcess($req);
        $loc = $res->headers->get('Location') ?? '(no redirect)';
        $results[] = sprintf("%-5s | role=%s | pos=%s | sec=%s | -> %s",
            $u->nrp, $u->role,
            $u->position?->position_name ?? '-',
            $u->section?->section_name ?? '-',
            $loc);
    } catch (\Throwable $e) {
        $results[] = sprintf("%-5s | role=%s | pos=%s | sec=%s | THREW %s %s",
            $u->nrp, $u->role,
            $u->position?->position_name ?? '-',
            $u->section?->section_name ?? '-',
            get_class($e), $e->getMessage());
    }
}
echo implode("\n", $results) . "\n";