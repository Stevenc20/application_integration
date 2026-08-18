<?php
$schedules = App\Models\ProductionSchedule::where('job_no', 'RCS-011')->get();
foreach ($schedules as $s) {
    foreach ($s->itemChecks as $ic) {
        dump(['id' => $ic->id, 'status' => $ic->status]);
    }
}
