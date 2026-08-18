<?php
require 'c:/MAMP/htdocs/application_integration/vendor/autoload.php';
\ = require_once 'c:/MAMP/htdocs/application_integration/bootstrap/app.php';
\ = \->make(Illuminate\Contracts\Console\Kernel::class);
\->bootstrap();
\ = date('Y-m-d');
\ = \App\Models\ProductionPlan::where('plan_date', \)
    ->where('row_type', 'job')
    ->where('shift_name', 'like', 'Shift Pagi%')
    ->get();
echo json_encode(\->toArray(), JSON_PRETTY_PRINT);
