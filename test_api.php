<?php
require __DIR__.'/vendor/autoload.php';
\ = require_once __DIR__.'/bootstrap/app.php';
\ = \->make(Illuminate\\Contracts\\Console\\Kernel::class);
\->bootstrap();
\ = app(\\App\\Services\\DashboardRealtimeService::class)->getLineMetrics('A', date('Y-m-d'), 1);
echo json_encode(\, JSON_PRETTY_PRINT);
