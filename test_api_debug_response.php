<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$date = '2026-08-25';
$shift = 'Shift Pagi';
$line = 'LINE A';

$reqApi = Illuminate\Http\Request::create('/api/v1/ppc/item-check', 'GET', [
    'date' => $date, 'shift' => $shift, 'line' => $line
]);
$reqApi->headers->set('Authorization', 'Bearer qa-super-secret-token');
$resApi = $kernel->handle($reqApi);
echo "Status: " . $resApi->getStatusCode() . "\n";
echo "Content: " . $resApi->getContent() . "\n";
