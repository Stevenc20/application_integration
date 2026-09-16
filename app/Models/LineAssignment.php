<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LineAssignment extends Model
{
    protected $table = 'line_assignments';

    protected $fillable = [
        'line_name',
        'shift_name',
        'leader_user_id',
        'foreman_user_id',
        'supervisor_user_id',
    ];

    public function leaderUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_user_id');
    }

    public function foremanUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'foreman_user_id');
    }

    public function supervisorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_user_id');
    }
}
