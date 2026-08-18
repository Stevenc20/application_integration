<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// cari file excel terbaru di storage
$files = glob(storage_path('app/public/inspeksi-sketches/*.xlsx'));
// atau mungkin import directory?
$files = glob(storage_path('app/*.xlsx')); // the user uploads it via UI, where is it temporarily stored?
// The user uploads via UI, so we don't have it on disk permanently except maybe if we log it.
