<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionLine extends Model
{
    protected $fillable = [
        'code',
        'name',
        'department',
        'is_active',
        'is_stopped',
    ];
}