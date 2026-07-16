<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Signature extends Model
{
    protected $fillable = [
        'role',
        'work_date',
        'signature_data',
    ];
}
