<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterShift extends Model
{
    protected $fillable = [
        'name',
        'planned_start_time',
        'planned_end_time',
    ];
}
