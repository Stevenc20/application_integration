<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dandori extends Model
{
    protected $table = 'dandoris';

    protected $fillable = [
        'previous_job_id',
        'next_job_id',
        'line',
        'shift',
        'activity',
        'jenis_dandori',
        'start_time',
        'finish_time',
        'duration_minutes',
        'work_date',
        'created_by'
    ];

    public function nextJob()
    {
        return $this->belongsTo(JobMaster::class, 'next_job_id');
    }

    public function previousJob()
    {
        return $this->belongsTo(JobMaster::class, 'previous_job_id');
    }
}