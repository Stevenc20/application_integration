<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobMaster extends Model
{
    protected $table      = 'job_masters';
    protected $primaryKey = 'id';
    public    $incrementing = true;
    protected $keyType    = 'int';

    protected $fillable = [
        'job_number',
        'job_name',
        'line',
        'capacity',
        'status',
        'sequence_no',
        'started_at',
        'finished_at'
    ];
}