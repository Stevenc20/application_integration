<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobFinishReason extends Model
{
    use HasFactory;

    protected $table = 'job_finish_reasons';

    protected $fillable = [
        'job_master_id',
        'production_plan_id',
        'user_id',
        'line_id',
        'line_name',
        'work_date',
        'shift',
        'target_qty',
        'actual_ok',
        'actual_repair',
        'actual_reject',
        'actual_total',
        'reason',
    ];

    public function jobMaster(): BelongsTo
    {
        return $this->belongsTo(JobMaster::class, 'job_master_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function productionPlan(): BelongsTo
    {
        return $this->belongsTo(ProductionPlan::class, 'production_plan_id');
    }

    public function lineMaster(): BelongsTo
    {
        return $this->belongsTo(LineMaster::class, 'line_id');
    }
}
