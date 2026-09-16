<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmrCustomer extends Model
{
    protected $table = 'smr_customers';

    protected $fillable = [
        'no', 'year', 'date', 'month', 'quarterly', 'no_smr', 'job_no',
        'part_number', 'part_name', 'qty_smr', 'total_production',
        'cost_rijection', 'rijection_rate', 'customer', 'problem', 'countermeasures',
    ];

    protected $casts = [
        'year'             => 'integer',
        'date'             => 'date',
        'qty_smr'          => 'integer',
        'total_production' => 'integer',
        'cost_rijection'   => 'double',
        'rijection_rate'   => 'double',
    ];
}
