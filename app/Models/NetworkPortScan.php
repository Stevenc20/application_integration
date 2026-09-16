<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NetworkPortScan extends Model
{
    protected $fillable = [
        'target', 'port', 'protocol', 'service_name',
        'status', 'response_time_ms', 'scanned_at',
    ];

    protected $casts = [
        'scanned_at' => 'datetime',
    ];
}
