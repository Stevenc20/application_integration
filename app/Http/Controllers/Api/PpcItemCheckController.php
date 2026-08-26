<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductionPlan;
use App\Models\JobMaster;
use Illuminate\Support\Str;

class PpcItemCheckController extends Controller
{
    public function index(Request $request)
    {
        // 15. AUTHENTICATION
        $token = $request->bearerToken();
        $expectedToken = env('QA_API_TOKEN', 'qa-super-secret-token');
        if (!$token || $token !== $expectedToken) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        // VALIDATION
        if (!$request->has('date')) {
            return response()->json([
                'success' => false,
                'message' => 'The date field is required.'
            ], 422);
        }

        $date = $request->input('date');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid date format. Expected YYYY-MM-DD.'
            ], 422);
        }

        $shift = $request->input('shift');
        $line = $request->input('line');

        // CORE QUERY (Same logic as InputHarianController)
        $planQuery = ProductionPlan::whereDate('plan_date', $date)
            ->where('row_type', 'job')
            ->whereNotIn('job_no', ['TOTAL FINISH', 'TOTAL FNISH', 'FINISH']);

        if ($shift) {
            // Find latest revision shift name
            $latestShiftName = ProductionPlan::whereDate('plan_date', $date)
                ->where('shift_name', 'like', "{$shift}%")
                ->orderByDesc('updated_at')
                ->value('shift_name');
            
            if ($latestShiftName) {
                $planQuery->where('shift_name', $latestShiftName);
            } else {
                $planQuery->where('shift_name', $shift);
            }
        }

        if ($line) {
            $normalized = strtoupper(trim(str_replace(['Line ', 'LINE ', 'Press ', 'PRESS '], '', $line)));
            $planQuery->whereRaw("
                REPLACE(
                    REPLACE(
                        UPPER(TRIM(press_name)),
                        'PRESS ',
                        ''
                    ),
                    'LINE ',
                    ''
                ) LIKE ?
            ", ["%{$normalized}%"]);
        }

        // Order by PPC sequence
        $plans = $planQuery->orderBy('press_name')->orderBy('row_no', 'asc')->get();

        if ($plans->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'No PPC item check data found.',
                'filters' => [
                    'date' => $date,
                    'shift' => $shift,
                    'line' => $line
                ],
                'data' => []
            ]);
        }

        // AGGREGATION TO PREVENT N+1 & CARTESIAN DOUBLE COUNTING
        // Map to job_numbers exactly as InputHarian does
        $jobNumbers = $plans->map(function($p) {
            $jn = trim($p->job_no ?? '');
            $jm = trim($p->job_master ?? '');
            return $jn ? ($jn . '-' . $p->id) : ('AUTO-' . Str::slug($jm) . '-' . $p->id);
        })->toArray();

        // Eager load only necessary relations. JobMaster -> DailyProduction handles actuals.
        $jobMasters = JobMaster::whereIn('job_number', $jobNumbers)
            ->with(['dailyProduction' => function ($q) use ($date) {
                $q->where('work_date', $date);
            }])
            ->get()
            ->keyBy('job_number');

        $data = [];
        foreach ($plans as $plan) {
            $jn = trim($plan->job_no ?? '');
            $jm = trim($plan->job_master ?? '');
            $jobNumber = $jn ? ($jn . '-' . $plan->id) : ('AUTO-' . Str::slug($jm) . '-' . $plan->id);

            $jmRecord = $jobMasters->get($jobNumber);

            $actualOk = 0;
            $repair = 0;
            $reject = 0;

            if ($jmRecord && $jmRecord->dailyProduction) {
                $dp = $jmRecord->dailyProduction;
                $actualOk = (int) ($dp->actual_ok ?? 0);
                $repair = (int) ($dp->actual_repair ?? $dp->repair_qty ?? 0);
                $reject = (int) ($dp->actual_reject ?? $dp->reject_qty ?? 0);
            }

            $data[] = [
                'id' => $plan->id,
                'plan_date' => $plan->plan_date,
                'shift_name' => $plan->shift_name,
                'press_name' => $plan->press_name,
                'row_no' => $plan->row_no,
                'job_no' => $plan->job_no,
                'job_master' => $plan->job_master,
                'plan_qty' => (int) ($plan->plan ?? $plan->target_qty ?? 0),
                'actual_ok' => $actualOk,
                'repair' => $repair,
                'reject' => $reject,
                'plan_start' => substr($plan->start_time ?? '', 0, 5),
                'plan_finish' => substr($plan->finish_time ?? '', 0, 5),
                'row_type' => $plan->row_type
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'PPC item check data retrieved successfully.',
            'filters' => [
                'date' => $date,
                'shift' => $shift,
                'line' => $line
            ],
            'data' => $data
        ]);
    }
}
