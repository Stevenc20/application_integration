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
        'start_time',
        'finish_time',
        'duration_minutes',
        'work_date',
        'created_by'
    ];
}