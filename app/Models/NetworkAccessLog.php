<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NetworkAccessLog extends Model
{
    protected $fillable = [
        'ip_address',
        'method',
        'endpoint',
        'response_time_ms',
        'response_status',
        'user_agent',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
