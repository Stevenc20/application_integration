<?php

namespace App\Events;

use App\Models\ProductionPlan;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductionPlanUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public ProductionPlan $plan,
        public ?string $changedField = null
    ) {}
}
