<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DandoriGroup extends Model
{
    protected $fillable = [
        'session_id',
        'group_name',
        'sequence_no',
        'status',
        'start_time',
        'finish_time',
        'total_minutes'
    ];

    public function details()
    {
        return $this->hasMany(DandoriDetail::class,'group_id');
    }
}