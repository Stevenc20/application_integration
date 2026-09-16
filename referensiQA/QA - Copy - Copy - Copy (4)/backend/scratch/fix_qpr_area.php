<?php
$itemChecks = App\Models\ItemCheck::whereNotNull('qpr_id')->get();
foreach ($itemChecks as $ic) {
    if (!empty($ic->ng_details)) {
        $area = $ic->getAreaKejadianString();
        if ($area) {
            $qpr = App\Models\Qpr::find($ic->qpr_id);
            if ($qpr && empty($qpr->area)) {
                $qpr->area = $area;
                $qpr->save();
                echo 'Updated QPR ' . $qpr->id . ' with area ' . $area . PHP_EOL;
            }
        }
    }
}
