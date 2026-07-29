<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NetworkContainer extends Model
{
    protected $fillable = [
        'container_name',
        'image',
        'status',
        'ports',
        'cpu_percent',
        'memory_mb',
        'uptime_seconds',
        'last_checked_at',
    ];

    protected $casts = [
        'cpu_percent' => 'float',
        'memory_mb' => 'float',
        'uptime_seconds' => 'integer',
        'last_checked_at' => 'datetime',
    ];

    public function scopeUp($q)
    {
        return $q->where('status', 'running');
    }

    public function scopeDown($q)
    {
        return $q->where('status', '!=', 'running');
    }

    public function getUptimeHoursAttribute()
    {
        if (!$this->uptime_seconds) return 0;
        return round($this->uptime_seconds / 3600, 1);
    }
}
