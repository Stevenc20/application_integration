<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecoverySchedule extends Model
{
    protected $fillable = [
        'plan_date',
        'shift_name',
        'press_name',
        'status',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'notes',
    ];

    protected $casts = [
        'plan_date' => 'date',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(RecoveryItem::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejecter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }
}
