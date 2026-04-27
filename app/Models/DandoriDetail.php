<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DandoriDetail extends Model
{
    protected $fillable = [
        'group_id',
        'activity_name',
        'sequence_no',
        'status',
        'start_time',
        'finish_time',
        'duration_minutes',
        'remarks'
    ];
}