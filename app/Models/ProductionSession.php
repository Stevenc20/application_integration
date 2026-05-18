<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionSession extends Model
{
    protected $fillable = [
        'job_master_id',
        'work_date',
        'start_time',
        'pause_time',
        'finish_time',
        'total_seconds',
        'status'
    ];

    public function jobMaster()
    {
        return $this->belongsTo(JobMaster::class, 'job_master_id');
    }
}
