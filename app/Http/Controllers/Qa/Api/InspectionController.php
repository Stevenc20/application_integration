<?php

namespace App\Http\Controllers\Qa\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Inspection;

class InspectionController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'part_id' => 'required',
            'line_id' => 'required',
            'qty_checked' => 'required|integer',
            'qty_defect' => 'required|integer',
            'shift' => 'required|in:pagi,siang,malam',
        ]);

        $inspection = Inspection::create([
            'user_id' => auth()->id(),
            'part_id' => $request->part_id,
            'line_id' => $request->line_id,
            'qty_checked' => $request->qty_checked,
            'qty_defect' => $request->qty_defect,
            'shift' => $request->shift,
            'inspection_date' => now(),
        ]);
        
        $defectRate = ($request->qty_defect / $request->qty_checked) * 100;

        return response()->json([
            'message' => 'Inspection berhasil',
            'data' => $inspection,
            'defect_rate' => $defectRate
        ]);
    }
}