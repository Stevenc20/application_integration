<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyProduction extends Model
{
    protected $fillable = [
        'job_master_id',
        'work_date',
        'actual_qty',
        'reject_qty',
        'repair_qty',
        'remarks'
    ];


  public function saveQty(Request $request, $id)
{
    DailyProduction::updateOrCreate(
        [
            'job_master_id' => $id,
            'work_date' => now()->toDateString()
        ],
        [
            'actual_qty' => $request->actual_qty,
            'reject_qty' => $request->reject_qty,
            'repair_qty' => $request->repair_qty,
            'remarks' => $request->remarks
        ]
    );

    return response()->json([
        'success' => true,
        'message' => 'Data berhasil disimpan'
    ]);
}

}


