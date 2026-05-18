<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductionLine extends Model
{
    use SoftDeletes;

    protected $table = 'production_lines';

    protected $fillable = [
        'line_name',
        'capacity',
        'target_qty',
        'plan_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'plan_date'  => 'date',
        'capacity'   => 'integer',
        'target_qty' => 'integer',
    ];
}
