<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ProductionPlan extends Model
{
    protected $fillable = [
        'line_master_id',
        'target_qty',
        'plan_date',
        'status',
        'notes',
        'shift_name',
        'shift_master_id',
        'press_name',
        'hari',
        'tgl',
        'jam',
        'revisi',
        'row_no',
        'row_type',
        'job_master',
        'type_plt',
        'qty_plt',
        'keb_mtl',
        'total_plt',
        'job_no',
        'each_part',
        'plan',
        'ok',
        'repair',
        'reject',
        'total_mesin',
        'ct_detik',
        'process_time',
        'reg_active',
        'dct',
        'mct',
        'plan_dct',
        'tpt',
        'gsph_item',
        'start_time',
        'finish_time',
        'act_start',
        'act_finish',
        'keterangan',
        'p1',
        'p2',
        'p3',
        'p4',
        'a1',
        'a2',
        'a3',
        'a4',
        'dt_menit',
        'total_pcs',
        'tpt_total',
        'parent_job_id',
        'split_group',
        'session_no',
        'original_plan',
        'remaining_plan',
        'recovery_id',
        'source_type',
    ];

    protected $casts = [
        'p1'           => 'boolean',
        'p2'           => 'boolean',
        'p3'           => 'boolean',
        'p4'           => 'boolean',
        'plan_date'    => 'date',
        'qty_plt'      => 'float',
        'keb_mtl'      => 'float',
        'total_plt'    => 'float',
        'plan'         => 'float',
        'ok'           => 'float',
        'repair'       => 'float',
        'reject'       => 'float',
        'ct_detik'     => 'float',
        'process_time' => 'float',
        'reg_active'   => 'float',
        'dct'          => 'float',
        'mct'          => 'float',
        'plan_dct'     => 'float',
        'tpt'          => 'float',
        'gsph_item'    => 'float',
        'tpt_total'    => 'float',
        'total_pcs'    => 'float',
        'parent_job_id' => 'integer',
        'session_no'   => 'integer',
        'original_plan' => 'float',
        'remaining_plan' => 'float'
    ];

    public function line()
    {
        return $this->belongsTo(LineMaster::class,'line_master_id');
    }

    public function jobMaster()
    {
        return $this->belongsTo(JobMaster::class, 'job_no', 'job_number');
    }

    public function scopeVisibleOnTimeline(Builder $query)
    {
        return $query->whereIn('row_type', ['job', 'break'])
            ->where(function($q) {
                $q->whereNotIn(DB::raw('UPPER(TRIM(job_master))'), [
                    'TOTAL FINISH', 'TOTAL FNISH', 'FINISH', 'PLAN', 'TOTAL STROKE', 'TOTAL  STROKE', 'TOTAL TPT', 'TARGET GSPH', 'GSPH', 'TOTAL PCS', 'DELETE PLAN SHIFT 1', 'TOTAL'
                ])
                ->whereNotIn(DB::raw('UPPER(TRIM(job_no))'), [
                    'TOTAL FINISH', 'TOTAL FNISH', 'FINISH', 'PLAN', 'TOTAL STROKE', 'TOTAL  STROKE', 'TOTAL TPT', 'TARGET GSPH', 'GSPH', 'TOTAL PCS', 'DELETE PLAN SHIFT 1', 'TOTAL'
                ]);
            })
            ->where(function($q) {
                $q->where('row_type', 'job')
                  ->orWhere(function($inner) {
                      $inner->where('row_type', 'break')
                            ->whereNotNull('start_time')
                            ->whereNotNull('finish_time')
                            ->where('start_time', '<>', '')
                            ->where('finish_time', '<>', '');
                  });
            });
    }

    public function scopeKpiJobs(Builder $query)
    {
        return $query->where('row_type', 'job')
            ->where(function($q) {
                $q->whereNotIn(DB::raw('UPPER(TRIM(job_master))'), [
                    'TOTAL FINISH', 'TOTAL FNISH', 'FINISH', 'PLAN', 'TOTAL STROKE', 'TOTAL  STROKE', 'TOTAL TPT', 'TARGET GSPH', 'GSPH', 'TOTAL PCS', 'DELETE PLAN SHIFT 1', 'TOTAL'
                ])
                ->whereNotIn(DB::raw('UPPER(TRIM(job_no))'), [
                    'TOTAL FINISH', 'TOTAL FNISH', 'FINISH', 'PLAN', 'TOTAL STROKE', 'TOTAL  STROKE', 'TOTAL TPT', 'TARGET GSPH', 'GSPH', 'TOTAL PCS', 'DELETE PLAN SHIFT 1', 'TOTAL'
                ]);
            });
    }

    public function scopePpc(Builder $query)
    {
        return $query->where('source_type', 'ppc');
    }

    public function scopeRecovery(Builder $query)
    {
        return $query->where('source_type', 'recovery');
    }

    public function scopeInProduction(Builder $query)
    {
        return $query->whereHas('recoveryItem', function ($q) {
            $q->where('status', 'in_production');
        });
    }

    public function recoveryItem()
    {
        return $this->belongsTo(RecoveryItem::class, 'recovery_id');
    }

    /**
     * Scope: only root schedule rows (excluding child slices created by break splits).
     */
    public function scopeRootSchedules(Builder $query)
    {
        return $query->whereNull('parent_job_id');
    }

    /**
     * Parent ProductionPlan when this row is a child slice of a break-split schedule.
     */
    public function parentPlan()
    {
        return $this->belongsTo(self::class, 'parent_job_id');
    }

    /**
     * Direct child slices of this ProductionPlan.
     */
    public function childPlans()
    {
        return $this->hasMany(self::class, 'parent_job_id');
    }

    /**
     * Whether this row is a child slice linked to a parent schedule.
     */
    public function isChildSlice(): bool
    {
        return !empty($this->parent_job_id);
    }

    /**
     * Resolve the root ProductionPlan for this schedule identity (ONE SCHEDULE = ONE JOB IDENTITY).
     * Walks up parent_job_id with cycle protection so multi-break chains (P -> C1 -> C2) resolve to P.
     */
    public function resolveRootPlan(?\Illuminate\Support\Collection $candidatePool = null): self
    {
        $current = $this;
        $visited = [$current->id => true];

        while (!empty($current->parent_job_id)) {
            $parentId = (int) $current->parent_job_id;
            if (isset($visited[$parentId])) {
                break;
            }
            $visited[$parentId] = true;

            $parent = $candidatePool
                ? $candidatePool->firstWhere('id', $parentId)
                : null;

            if (!$parent && $current->relationLoaded('parentPlan')) {
                $parent = $current->getRelation('parentPlan');
            }

            if (!$parent) {
                $parent = self::find($parentId);
            }

            if (!$parent) {
                break;
            }

            $current = $parent;
        }

        return $current;
    }

    /**
     * Return all ProductionPlan slices (root + all break-split descendants) belonging to the same
     * canonical schedule identity on the same date, shift, and press/line.
     *
     * Strictly uses parent_job_id hierarchy and explicit split_group — NEVER merges independent
     * schedules that merely share the same job_no or job_master.
     *
     * @param  \Illuminate\Support\Collection<int, self>|null  $candidatePool
     * @return \Illuminate\Support\Collection<int, self>
     */
    public function getScheduleGroupPlans(?\Illuminate\Support\Collection $candidatePool = null): \Illuminate\Support\Collection
    {
        $root = $this->resolveRootPlan($candidatePool);

        if (!$root->id) {
            return collect([$this]);
        }

        if ($candidatePool !== null) {
            $sameContext = $candidatePool->filter(function ($item) use ($root) {
                if (!($item instanceof self)) {
                    return false;
                }
                if (($item->row_type ?? 'job') !== 'job') {
                    return false;
                }
                return (string) $item->plan_date === (string) $root->plan_date
                    && (string) $item->shift_name === (string) $root->shift_name
                    && (string) $item->press_name === (string) $root->press_name;
            });
        } else {
            $query = self::query()
                ->whereDate('plan_date', $root->plan_date)
                ->where('shift_name', $root->shift_name)
                ->where('press_name', $root->press_name)
                ->where(function ($q) {
                    $q->where('row_type', 'job')->orWhereNull('row_type');
                });
            $sameContext = $query->orderBy('row_no')->orderBy('id')->get();
        }

        $groupIds = [(int) $root->id => true];
        $splitGroup = !empty($root->split_group)
            ? (string) $root->split_group
            : (!empty($this->split_group) ? (string) $this->split_group : null);

        $added = true;
        while ($added) {
            $added = false;
            foreach ($sameContext as $candidate) {
                $cid = (int) $candidate->id;
                if (isset($groupIds[$cid])) {
                    continue;
                }

                $parentId = !empty($candidate->parent_job_id) ? (int) $candidate->parent_job_id : null;
                $matchesParent = $parentId !== null && isset($groupIds[$parentId]);
                $matchesSplitGroup = $splitGroup !== null
                    && !empty($candidate->split_group)
                    && (string) $candidate->split_group === $splitGroup;

                if ($matchesParent || $matchesSplitGroup) {
                    $groupIds[$cid] = true;
                    if ($splitGroup === null && !empty($candidate->split_group)) {
                        $splitGroup = (string) $candidate->split_group;
                    }
                    $added = true;
                }
            }
        }

        $group = $sameContext
            ->filter(fn(self $p) => isset($groupIds[(int) $p->id]))
            ->sortBy(fn(self $p) => sprintf('%08d-%08d', (int) ($p->row_no ?? 0), (int) $p->id))
            ->values();

        if ($group->isEmpty()) {
            return collect([$root]);
        }

        return $group;
    }

    /**
     * Return all ProductionPlan IDs belonging to the same canonical schedule identity.
     *
     * @param  \Illuminate\Support\Collection<int, self>|null  $candidatePool
     * @return array<int, int>
     */
    public function getScheduleGroupPlanIds(?\Illuminate\Support\Collection $candidatePool = null): array
    {
        return $this->getScheduleGroupPlans($candidatePool)
            ->pluck('id')
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Calculate the single canonical PPC target quantity for this schedule identity.
     *
     * Rules:
     * 1. Never double-count parent + child when original_plan already stores the full pre-split total.
     * 2. If a schedule is split across breaks without original_plan (or where original_plan on the root
     *    matches the full total and slice plans sum to that total), return max(root->original_plan, sum(slice->plan)).
     * 3. Handles Dandori-only pre-break slice (root->plan == 0, child->plan == 800 => returns 800).
     */
    public function effectiveTargetQty(?\Illuminate\Support\Collection $candidatePool = null): int
    {
        $root = $this->resolveRootPlan($candidatePool);
        $group = $this->getScheduleGroupPlans($candidatePool);

        if ($group->count() <= 1) {
            $orig = (int) round((float) ($root->original_plan ?? 0));
            $plan = (int) round((float) ($root->plan ?? 0));
            return $orig > 0 ? max($orig, $plan) : $plan;
        }

        $rootOriginal = (int) round((float) ($root->original_plan ?? 0));
        $sumSlices = (int) round((float) $group->sum(fn(self $slice) => (float) ($slice->plan ?? 0)));

        // If a root plan was NOT mutated down when child was created (root->plan == root->original_plan
        // and child also holds part of the quantity), avoid double-counting root + child.
        if ($rootOriginal > 0 && (int) round((float) ($root->plan ?? 0)) === $rootOriginal && $sumSlices > $rootOriginal) {
            return $rootOriginal;
        }

        return max($rootOriginal, $sumSlices);
    }

    /**
     * Resolve the effective finish time across all slices of this schedule identity.
     */
    public function effectiveFinishTime(?\Illuminate\Support\Collection $candidatePool = null): ?string
    {
        $group = $this->getScheduleGroupPlans($candidatePool);
        $lastWithFinish = $group->reverse()->first(fn(self $slice) => !empty($slice->finish_time));

        return $lastWithFinish ? $lastWithFinish->finish_time : $this->finish_time;
    }

    /**
     * Canonical JobMaster job_number using the ROOT ProductionPlan ID.
     * Format: "{job_no}-{root_id}" or "AUTO-{slug}-{root_id}".
     */
    public function canonicalJobNumber(?\Illuminate\Support\Collection $candidatePool = null): string
    {
        $root = $this->resolveRootPlan($candidatePool);
        $base = $root->job_no
            ? trim((string) $root->job_no)
            : ('AUTO-' . \Illuminate\Support\Str::slug((string) $root->job_master));

        return $base . '-' . $root->id;
    }
}
