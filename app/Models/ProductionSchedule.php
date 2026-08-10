<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionSchedule extends Model
{
    protected $fillable = [
        'sap_order_no',
        'job_no',
        'part_no',
        'part_name',
        'tanggal_produksi',
        'line',
        'target_qty',
        'actual_qty',
        'ok_qty',
        'ng_qty',
        'repair_qty',
        'press_name',
        'shift_name',
        'ppc_plan_id',
        'row_no',
        'production_repair_notes',
        'production_reject_notes',
        'status'
    ];

    protected $casts = [
        'tanggal_produksi'        => 'date',
        'production_repair_notes' => 'array',
        'production_reject_notes' => 'array',
    ];

    public function itemChecks()
    {
        return $this->hasMany(ItemCheck::class, 'production_schedule_id');
    }
}
