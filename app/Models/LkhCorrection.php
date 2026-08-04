<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LkhCorrection extends Model
{
    protected $fillable = [
        'plan_id',
        'field',
        'old_value',
        'new_value',
        'edited_by',
        'edited_at',
    ];

    protected $casts = [
        'edited_at' => 'datetime',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(ProductionPlan::class, 'plan_id');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by');
    }
}
