<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessEventLog extends Model
{
    use HasFactory;

    protected $table = 'business_event_logs';

    protected $fillable = [
        'event_type',
        'entity_type',
        'entity_id',
        'user_id',
        'user',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Fallback for user display name
     */
    public function getUserNameAttribute(): string
    {
        if ($this->user_id && $this->user) {
            return $this->user->name;
        }
        return $this->attributes['user'] ?? '-';
    }
}
