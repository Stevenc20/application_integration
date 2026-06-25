<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionPlan extends Model
{
    protected $fillable = [
        'line_master_id',
        'target_qty',
        'plan_date',
        'status',
        'notes',
        'shift_name',
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

    public function scopeVisibleOnTimeline($query)
    {
        return $query->whereIn('row_type', ['job', 'break'])
            ->where(function($q) {
                $q->whereNotIn(\DB::raw('UPPER(TRIM(job_master))'), [
                    'TOTAL FINISH', 'TOTAL FNISH', 'FINISH', 'PLAN', 'TOTAL STROKE', 'TOTAL  STROKE', 'TOTAL TPT', 'TARGET GSPH', 'GSPH', 'TOTAL PCS', 'DELETE PLAN SHIFT 1', 'TOTAL'
                ])
                ->whereNotIn(\DB::raw('UPPER(TRIM(job_no))'), [
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

    public function scopeKpiJobs($query)
    {
        return $query->where('row_type', 'job')
            ->where(function($q) {
                $q->whereNotIn(\DB::raw('UPPER(TRIM(job_master))'), [
                    'TOTAL FINISH', 'TOTAL FNISH', 'FINISH', 'PLAN', 'TOTAL STROKE', 'TOTAL  STROKE', 'TOTAL TPT', 'TARGET GSPH', 'GSPH', 'TOTAL PCS', 'DELETE PLAN SHIFT 1', 'TOTAL'
                ])
                ->whereNotIn(\DB::raw('UPPER(TRIM(job_no))'), [
                    'TOTAL FINISH', 'TOTAL FNISH', 'FINISH', 'PLAN', 'TOTAL STROKE', 'TOTAL  STROKE', 'TOTAL TPT', 'TARGET GSPH', 'GSPH', 'TOTAL PCS', 'DELETE PLAN SHIFT 1', 'TOTAL'
                ]);
            });
    }

    public function scopePpc($query)
    {
        return $query->where('source_type', 'ppc');
    }

    public function scopeRecovery($query)
    {
        return $query->where('source_type', 'recovery');
    }

    public function scopeInProduction($query)
    {
        return $query->whereHas('recoveryItem', function ($q) {
            $q->where('status', 'in_production');
        });
    }

    public function recoveryItem()
    {
        return $this->belongsTo(RecoveryItem::class, 'recovery_id');
    }
}
