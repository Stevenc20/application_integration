<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleRevision extends Model
{
    protected $fillable = [
        'plan_date',
        'shift_name',
        'action',
        'section_scope',
        'source_filename',
        'snapshot_before',
        'snapshot_after',
        'created_by',
    ];

    protected $casts = [
        'plan_date' => 'date',
        'section_scope' => 'array',
        'snapshot_before' => 'json',
        'snapshot_after' => 'json',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
