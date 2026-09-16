<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductionSchedule;

class ProductionScheduleController extends Controller
{
    public function getByJobNo($job_no)
    {
        // Decode URI component just in case
        $job_no = urldecode($job_no);

        $schedule = ProductionSchedule::where('job_no', $job_no)->first();

        if (!$schedule) {
            // Return dummy data for testing purposes if not found in db
            // In a real scenario, this might fetch from an external SAP API
            return response()->json([
                'id' => null,
                'job_no' => $job_no,
                'actual_qty' => rand(150, 300),
                'ng_qty' => rand(0, 5),
                'repair_qty' => rand(0, 2),
                'target_qty' => 500,
                'tanggal' => date('Y-m-d')
            ]);
        }

        return response()->json([
            'id' => $schedule->id,
            'job_no' => $schedule->job_no,
            'actual_qty' => $schedule->actual_qty,
            'ng_qty' => $schedule->ng_qty,
            'repair_qty' => $schedule->repair_qty,
            'target_qty' => $schedule->target_qty,
            'tanggal' => $schedule->tanggal_produksi ?? date('Y-m-d')
        ]);
    }
}
