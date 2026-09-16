<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PullAheadRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'original_plan_id',
        'new_plan_id',
        'requested_by',
        'approved_by',
        'source_shift',
        'target_shift',
        'qty_requested',
        'qty_approved',
        'proposed_sequence_after',
        'final_sequence_after',
        'status',
        'remarks',
        'is_read_by_leader',
        'is_read_by_ppc',
    ];

    protected $casts = [
        'qty_requested'     => 'float',
        'qty_approved'      => 'float',
        'is_read_by_leader' => 'boolean',
        'is_read_by_ppc'    => 'boolean',
    ];

    public function originalPlan()
    {
        return $this->belongsTo(ProductionPlan::class, 'original_plan_id');
    }

    public function newPlan()
    {
        return $this->belongsTo(ProductionPlan::class, 'new_plan_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
