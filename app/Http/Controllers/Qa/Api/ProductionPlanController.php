<?php

namespace App\Http\Controllers\Qa\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductionPlan;

class ProductionPlanController extends Controller
{
    public function getByJobNo($job_no)
    {
        // Decode URI component just in case
        $job_no = urldecode($job_no);

        $schedule = ProductionPlan::where('job_no', $job_no)->first();

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
            'actual_qty' => $schedule->ok ?? 0,
            'ng_qty' => $schedule->reject ?? 0,
            'repair_qty' => $schedule->repair ?? 0,
            'target_qty' => $schedule->plan ?? 0,
            'tanggal' => $schedule->plan_date ? $schedule->plan_date->format('Y-m-d') : date('Y-m-d')
        ]);
    }
}
