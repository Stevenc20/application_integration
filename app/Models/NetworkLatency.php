<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NetworkLatency extends Model
{
    protected $fillable = [
        'source',
        'target',
        'target_type',
        'latency_ms',
        'status',
        'measured_at',
    ];

    protected $casts = [
        'latency_ms' => 'float',
        'measured_at' => 'datetime',
    ];
}
