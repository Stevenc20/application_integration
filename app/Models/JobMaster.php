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

    public function repairRejects()
    {
        return $this->hasMany(RepairRejectLog::class, 'job_master_id');
    }

    public function productionSessions()
    {
        return $this->hasMany(ProductionSession::class, 'job_master_id');
    }

    public function dandoriSessions()
    {
        return $this->hasMany(DandoriSession::class, 'job_master_id');
    }

    public function dandoris()
    {
        return $this->hasMany(Dandori::class, 'next_job_id');
    }

    /**
     * Relasi ke ProductionPlan berdasarkan job_number = job_no
     * Digunakan untuk filter antrian berdasarkan jadwal PPC.
     */
    public function productionPlans()
    {
        return $this->hasMany(\App\Models\ProductionPlan::class, 'job_no', 'job_number');
    }

    public function qChecks()
    {
        return $this->hasMany(QCheck::class, 'job_master_id');
    }

    public function getTotalQcheckMinutesAttribute(): float
    {
        return round($this->qChecks->sum(fn ($qc) => $qc->duration), 2);
    }

    /**
     * Get specific ProductionPlan using the ID suffix from job_number
     */
    public function getProductionPlanAttribute()
    {
        $parts = explode('-', $this->job_number);
        $planId = end($parts);
        if (is_numeric($planId)) {
            return \App\Models\ProductionPlan::find($planId);
        }
        return null;
    }
}