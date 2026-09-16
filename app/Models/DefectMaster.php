<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DefectMaster extends Model
{
    protected $fillable = [
        'code',
        'name',
        'category',
        'is_active',
    ];
}
