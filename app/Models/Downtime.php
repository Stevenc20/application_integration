<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Downtime extends Model
{
    protected $fillable = [
        'job_master_id',
        'jenis_downtime',
        'problem',
        'penyebab',
        'action',
        'pic',
        'start_time',
        'finish_time',
        'duration_seconds',
    ];

    public function jobMaster()
    {
        return $this->belongsTo(JobMaster::class);
    }
}
