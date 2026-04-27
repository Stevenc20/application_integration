<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DandoriSession extends Model
{
    protected $fillable = [
        'job_master_id',
        'job_number',
        'job_name',
        'line',
        'shift',
        'status',
        'start_time',
        'finish_time',
        'total_minutes',
        'created_by'
    ];

    public function groups()
    {
        return $this->hasMany(DandoriGroup::class,'session_id');
    }
}