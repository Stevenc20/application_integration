<?php

namespace App\Listeners;

use App\Events\ProductionPlanUpdated;
use App\Services\TimelineGenerationService;

class RegenerateTimelineListener
{
    public function __construct(
        private TimelineGenerationService $timelineGenerator
    ) {}

    public function handle(ProductionPlanUpdated $event): void
    {
        $triggers = ['plan', 'qty_plt', 'ct_detik', 'dct', 'reg_active', 'total_mesin', 'row_no', 'recovery_id', 'source_type'];
        if ($event->changedField && !in_array($event->changedField, $triggers, true)) {
            return;
        }

        $this->timelineGenerator->regenerateForPlan($event->plan);
    }
}
