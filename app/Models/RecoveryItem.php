<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecoveryItem extends Model
{
    protected $fillable = [
        'recovery_schedule_id',
        'production_plan_id',
        'job_no',
        'job_master',
        'press_name',
        'plan_qty',
        'ok',
        'repair',
        'reject',
        'ct_detik',
        'dct',
        'reg_active',
        'total_mesin',
        'status',
        'original_date',
        'original_shift_name',
        'original_row_no',
        'source_date',
        'source_shift',
        'actual_qty',
        'recovery_qty',
        'sort_order',
        'duration_minutes',
        'queued_at',
        'rejected_at',
        'rejected_by',
        'rejection_notes',
    ];

    protected $casts = [
        'plan_qty' => 'float',
        'ok' => 'float',
        'repair' => 'float',
        'reject' => 'float',
        'ct_detik' => 'float',
        'dct' => 'float',
        'reg_active' => 'float',
        'total_mesin' => 'integer',
        'actual_qty' => 'float',
        'recovery_qty' => 'float',
        'duration_minutes' => 'float',
        'original_date' => 'date',
        'source_date' => 'date',
        'queued_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function scopePending($q)
    {
        return $q->where('status', 'waiting_approval');
    }

    public function scopeWaitingApproval($q)
    {
        return $q->where('status', 'waiting_approval');
    }

    public function scopeApproved($q)
    {
        return $q->where('status', 'approved');
    }

    public function scopeRejected($q)
    {
        return $q->where('status', 'rejected');
    }

    public function scopeScheduled($q)
    {
        return $q->where('status', 'scheduled');
    }

    public function scopeInProduction($q)
    {
        return $q->where('status', 'in_production');
    }

    public function scopeCompleted($q)
    {
        return $q->where('status', 'completed');
    }

    public function scopeForPress($q, $pressName)
    {
        return $q->where('press_name', $pressName);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(RecoverySchedule::class, 'recovery_schedule_id');
    }

    public function productionPlan(): BelongsTo
    {
        return $this->belongsTo(ProductionPlan::class);
    }

    public function rejector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }
}
