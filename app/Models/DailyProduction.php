<?php

namespace App\Models;

use App\Models\JobMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyProduction extends Model
{
    protected $fillable = [
        'job_master_id',
        'work_date',
        'actual_qty',
        'actual_ok',
        'actual_repair',
        'actual_reject',
        'reject_qty',
        'repair_qty',
        'remarks',
        'line',
        'shift',
        'target_qty',
        'runtime_seconds',
        'downtime_seconds',
        'efficiency',
        'saved_by'
    ];

    public function jobMaster(): BelongsTo
    {
        return $this->belongsTo(JobMaster::class, 'job_master_id');
    }

    public function getActualOkAttribute($value): int
    {
        return (int)($value ?: ($this->actual_qty - ($this->repair_qty ?? 0) - ($this->reject_qty ?? 0)));
    }

    public function getActualRepairAttribute($value): int
    {
        return (int)($value ?: ($this->repair_qty ?? 0));
    }

    public function getActualRejectAttribute($value): int
    {
        return (int)($value ?: ($this->reject_qty ?? 0));
    }

  public function saveQty(Request $request, $id)
{
    $qty = (int) $request->actual_qty;
    $repair = (int) $request->repair_qty;
    $reject = (int) $request->reject_qty;

    DailyProduction::updateOrCreate(
        [
            'job_master_id' => $id,
            'work_date' => now()->toDateString()
        ],
        [
            'actual_qty' => $qty,
            'actual_ok' => $qty - $repair - $reject,
            'actual_repair' => $repair,
            'actual_reject' => $reject,
            'reject_qty' => $reject,
            'repair_qty' => $repair,
            'remarks' => $request->remarks
        ]
    );

    return response()->json([
        'success' => true,
        'message' => 'Data berhasil disimpan'
    ]);
}

}


