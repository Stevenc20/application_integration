<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataScrap extends Model
{
    protected $table = 'data_scraps';

    protected $fillable = [
        'no', 'year', 'month', 'ba_no', 'job_no', 'sourch_1', 'part_number',
        'part_name', 'sourch_2', 'customer', 'qty', 'value', 'total_production', 'reject_rate',
    ];

    protected $casts = [
        'no'               => 'integer',
        'year'             => 'integer',
        'qty'              => 'integer',
        'value'            => 'double',
        'total_production' => 'integer',
        'reject_rate'      => 'double',
    ];
}
