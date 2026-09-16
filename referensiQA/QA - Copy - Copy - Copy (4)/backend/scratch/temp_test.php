<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$controller = app(\App\Http\Controllers\Api\ItemCheckController::class);
$request = new \Illuminate\Http\Request();
$request->merge(['per_page' => 200]);
$res = $controller->summaryList($request);
echo $res->getContent();
