<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionPlanDuplicateAudit extends Model
{
    protected $fillable = [
        'production_plan_id',
        'duplicate_of_id',
        'action',
        'job_no',
        'job_master',
        'plan_date',
        'shift_name',
        'press_name',
        'start_time',
        'finish_time',
        'plan_qty',
        'reason',
        'snapshot',
        'deleted_by',
        'deleted_at',
    ];

    protected $casts = [
        'plan_date' => 'date',
        'snapshot' => 'array',
        'deleted_at' => 'datetime',
        'plan_qty' => 'float',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(ProductionPlan::class, 'production_plan_id');
    }

    public function duplicateOf(): BelongsTo
    {
        return $this->belongsTo(ProductionPlan::class, 'duplicate_of_id');
    }

    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
