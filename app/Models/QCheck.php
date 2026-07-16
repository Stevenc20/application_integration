<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class QCheck extends Model
{
    protected $table = 'q_checks';

    protected $fillable = [
        'job_master_id',
        'jenis_qcheck',
        'hasil_qcheck',
        'keterangan',
        'start_time',
        'finish_time',
    ];

    protected $casts = [
        'start_time'  => 'datetime',
        'finish_time' => 'datetime',
    ];

    public function jobMaster()
    {
        return $this->belongsTo(JobMaster::class);
    }

    public function getDurationAttribute(): float
    {
        if ($this->start_time && $this->finish_time) {
            return round($this->start_time->diffInMinutes($this->finish_time), 2);
        }
        if ($this->start_time && !$this->finish_time) {
            return round($this->start_time->diffInMinutes(now()), 2);
        }
        return 0;
    }
}
