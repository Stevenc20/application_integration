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
        'target_qty',
        'ppc_revision_id',
        'ppc_revision_active',
    ];

    protected $casts = [
        'ppc_revision_id' => 'integer',
        'ppc_revision_active' => 'boolean',
    ];

    /**
     * Jobs that are valid members of the latest PPC revision.
     * Legacy/non-PPC jobs have no revision marker and remain active.
     */
    public function scopeActivePpcRevision($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('ppc_revision_active')
                ->orWhere('ppc_revision_active', true);
        });
    }

    public function scopeOmittedFromPpcRevision($query)
    {
        return $query->where('ppc_revision_active', false);
    }

    public function isActivePpcRevision(): bool
    {
        return $this->ppc_revision_active === null || (bool) $this->ppc_revision_active;
    }

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
     * Extract trailing numeric ProductionPlan ID from job_number ("{job_no}-{plan_id}" or "AUTO-{slug}-{plan_id}").
     */
    public function extractPlanId(): ?int
    {
        if (empty($this->job_number)) {
            return null;
        }

        $parts = explode('-', (string) $this->job_number);
        $planId = end($parts);

        return is_numeric($planId) && (int) $planId > 0 ? (int) $planId : null;
    }

    /**
     * Get specific ProductionPlan using the ID suffix from job_number
     */
    public function getProductionPlanAttribute()
    {
        $planId = $this->extractPlanId();
        if ($planId !== null) {
            return \App\Models\ProductionPlan::find($planId);
        }
        return null;
    }

    /**
     * Resolve the root ProductionPlan for this JobMaster (ONE SCHEDULE = ONE JOB IDENTITY).
     */
    public function resolveRootPlan(?\Illuminate\Support\Collection $candidatePool = null): ?\App\Models\ProductionPlan
    {
        $planId = $this->extractPlanId();
        if ($planId === null) {
            return null;
        }

        $plan = $candidatePool
            ? $candidatePool->firstWhere('id', $planId)
            : null;

        if (!$plan) {
            $plan = \App\Models\ProductionPlan::find($planId);
        }

        return $plan ? $plan->resolveRootPlan($candidatePool) : null;
    }

    /**
     * Resolve the canonical root JobMaster for this schedule identity.
     * If this JobMaster was created for a child break-split slice, returns the JobMaster
     * linked to the root ProductionPlan (falling back to $this if not found so historical records never break).
     */
    public function resolveRootJobMaster(?\Illuminate\Support\Collection $candidatePool = null): self
    {
        $rootPlan = $this->resolveRootPlan($candidatePool);
        if (!$rootPlan) {
            return $this;
        }

        $currentPlanId = $this->extractPlanId();
        if ($currentPlanId !== null && (int) $rootPlan->id === $currentPlanId) {
            return $this;
        }

        $canonicalNumber = $rootPlan->canonicalJobNumber($candidatePool);
        $rootJob = self::where('job_number', $canonicalNumber)->first();

        if (!$rootJob) {
            $rootJob = self::where('line', $this->line)
                ->where('job_number', 'LIKE', '%-' . $rootPlan->id)
                ->orderBy('id')
                ->first();
        }

        return $rootJob ?: $this;
    }

    /**
     * Effective PPC target quantity for this job identity (evaluated from the root ProductionPlan group).
     */
    public function effectiveTargetQty(?\Illuminate\Support\Collection $candidatePool = null): int
    {
        $rootPlan = $this->resolveRootPlan($candidatePool);
        if ($rootPlan) {
            $planTarget = $rootPlan->effectiveTargetQty($candidatePool);
            if ($planTarget > 0) {
                return $planTarget;
            }
        }

        return (int) round((float) ($this->target_qty ?? 0));
    }
}