<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inspection extends Model
{
    protected $fillable = [
        'user_id',
        'part_id',
        'line_id',
        'qty_checked',
        'qty_defect',
        'shift',
        'inspection_date',
    ];
}