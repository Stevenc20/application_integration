<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleBackup extends Model
{
    protected $table = 'schedule_backups';

    protected $fillable = [
        'plan_date',
        'shift_scope',
        'press_scope',
        'backup_type',
        'label',
        'source_filename',
        'checksum',
        'plans_count',
        'jobs_count',
        'active_jobs_count',
        'signatures_count',
        'summary_meta',
        'snapshot_payload',
        'created_by',
        'restored_from_id',
        'last_restored_at',
        'last_restored_by',
    ];

    protected $casts = [
        'plan_date' => 'date',
        'summary_meta' => 'array',
        'snapshot_payload' => 'array',
        'last_restored_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function restorer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_restored_by');
    }

    public function restoredFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'restored_from_id');
    }
}
