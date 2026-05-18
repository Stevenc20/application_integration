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
        'finished_at',
        'plan_start',
        'plan_end',
        'target_qty'
    ];

    public function dailyProduction()
    {
        return $this->hasOne(DailyProduction::class, 'job_master_id');
    }

    public function downtimes()
    {
        return $this->hasMany(Downtime::class, 'job_master_id');
    }

    public function productionLogs()
    {
        return $this->hasMany(ProductionLog::class, 'job_master_id');
    }

    /**
     * Relasi ke ProductionPlan berdasarkan job_number = job_no
     * Digunakan untuk filter antrian berdasarkan jadwal PPC.
     */
    public function productionPlans()
    {
        return $this->hasMany(\App\Models\ProductionPlan::class, 'job_no', 'job_number');
    }
}