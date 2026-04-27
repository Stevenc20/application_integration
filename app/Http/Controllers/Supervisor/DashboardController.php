<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\ProductionProcess;
use App\Models\ProductionTarget;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {

        /*
        ====================================
        DEFAULT DATE
        ====================================
        */

        $dateFrom = request('date_from') ?? now()->toDateString();
        $dateTo   = request('date_to') ?? now()->toDateString();


        /*
        ====================================
        BASE QUERY
        ====================================
        */

        $query = ProductionProcess::with('job');

        $query->whereDate('created_at', '>=', $dateFrom)
              ->whereDate('created_at', '<=', $dateTo);

        if (request('process_type')) {
            $query->where('process_type', request('process_type'));
        }

        if (request('shift')) {
            $query->where('shift', request('shift'));
        }

        if (request('order')) {
            $query->where('production_order_number', 'like', '%' . request('order') . '%');
        }


        /*
        ====================================
        PRODUCTION TOTAL
        ====================================
        */

        $totalOk = (clone $query)->sum('qty_ok');
        $totalRepair = (clone $query)->sum('qty_repair');
        $totalReject = (clone $query)->sum('qty_reject');

        $totalProduction = $totalOk + $totalRepair + $totalReject;

        $okPercent = $totalProduction > 0 ? round(($totalOk / $totalProduction) * 100, 1) : 0;
        $repairPercent = $totalProduction > 0 ? round(($totalRepair / $totalProduction) * 100, 1) : 0;
        $rejectPercent = $totalProduction > 0 ? round(($totalReject / $totalProduction) * 100, 1) : 0;


        /*
        ====================================
        REJECT RATE
        ====================================
        */

        $rejectRate = $totalProduction > 0
            ? round(($totalReject / $totalProduction) * 100, 2)
            : 0;


        /*
        ====================================
        TARGET QUERY
        ====================================
        */

        $targetQuery = ProductionTarget::query();

        $targetQuery->whereDate('target_date', $dateFrom);

        if (request('process_type')) {
            $targetQuery->where('process_type', request('process_type'));
        }

        if (request('shift')) {
            $targetQuery->where('shift', request('shift'));
        }

        $targetQty = $targetQuery->sum('target_qty');


        /*
        ====================================
        ACHIEVEMENT
        ====================================
        */

        if ($targetQty > 0) {

            $achievementPercent = round(($totalOk / $targetQty) * 100, 1);

            $gap = $targetQty - $totalOk;

            if ($achievementPercent >= 100) {
                $performanceColor = 'bg-green-600';
            } elseif ($achievementPercent >= 80) {
                $performanceColor = 'bg-yellow-500';
            } else {
                $performanceColor = 'bg-red-600';
            }

        } else {

            $achievementPercent = 0;
            $gap = 0;
            $performanceColor = 'bg-gray-400';

        }

        /*
        ====================================
        TIME BASE (FIX REDUNDANSI)
        ====================================
        */

        $now = Carbon::now();
        $time = $now->format('H:i');

        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        $tomorrow = Carbon::tomorrow();

        // DEFAULT
        $minutesPassed = 0;
        $remainingMinutes = 0;
        $shift = null;
        $isOvertime = false;

        /*
        ====================================
        SHIFT LOGIC
        ====================================
        */

        if ($time >= '07:30' && $time < '16:15') {
            $shift = 1;
            $shiftStart = $today->copy()->setTime(7,30);
            $shiftEnd   = $today->copy()->setTime(16,15);
        }

        elseif ($time >= '16:15' && $time < '21:00') {
            $shift = 1;
            $isOvertime = true;
            $shiftStart = $today->copy()->setTime(7,30);
            $shiftEnd   = $today->copy()->setTime(21,0);
        }

        elseif ($time >= '21:00' || $time < '04:30') {
            $shift = 2;

            if ($time >= '21:00') {
                $shiftStart = $today->copy()->setTime(21,0);
                $shiftEnd   = $tomorrow->copy()->setTime(4,30);
            } else {
                $shiftStart = $yesterday->copy()->setTime(21,0);
                $shiftEnd   = $today->copy()->setTime(4,30);
            }
        }

        elseif ($time >= '04:30' && $time < '07:30') {
            $shift = 2;
            $isOvertime = true;
            $shiftStart = $yesterday->copy()->setTime(21,0);
            $shiftEnd   = $today->copy()->setTime(7,30);
        }

        /*
====================================
REALTIME TIME CALC (SYNC WITH JS)
====================================
*/

if (isset($shiftStart) && isset($shiftEnd)) {

    // total shift duration
    $shiftDurationMinutes = $shiftStart->diffInMinutes($shiftEnd);

    // realtime minutes passed
    $minutesPassed = $shiftStart->diffInMinutes($now);

    // clamp biar ga minus / over
    if ($now < $shiftStart) {
        $minutesPassed = 0;
    }

    if ($now > $shiftEnd) {
        $minutesPassed = $shiftDurationMinutes;
    }

    // remaining realtime
    $remainingMinutes = max($shiftDurationMinutes - $minutesPassed, 0);
}

        /*
        ====================================
        BREAK LOGIC
        ====================================
        */

        $day = $now->format('l');

        $breaks = []; 
        $isBreak = false;

        $currentBreakStart = null;
        $currentBreakEnd = null;

        if (in_array($day, ['Monday','Tuesday','Wednesday','Thursday'])) {

            $breaks[] = [
                'start' => $today->copy()->setTime(12,0),
                'end'   => $today->copy()->setTime(12,40),
            ];

            $breaks[] = [
                'start' => $today->copy()->setTime(15,15),
                'end'   => $today->copy()->setTime(15,30),
            ];
        }

        elseif ($day == 'Friday') {

            $breaks[] = [
                'start' => $today->copy()->setTime(11,45),
                'end'   => $today->copy()->setTime(12,45),
            ];
        }

        foreach ($breaks as $b) {

            if (
                $now->greaterThanOrEqualTo($b['start']) &&
                $now->lessThan($b['end'])
            ) {
                $isBreak = true;
                $currentBreakStart = $b['start'];
                $currentBreakEnd = $b['end'];
                break;
            }
        }

        /*
        ====================================
        EFFECTIVE TIME
        ====================================
        */

        $breakMinutesPassed = 0;
        $futureBreakMinutes = 0;

        $effectiveMinutesPassed = max($minutesPassed - $breakMinutesPassed, 0);
        $effectiveRemainingMinutes = max($remainingMinutes - $futureBreakMinutes, 0);

        if ($effectiveRemainingMinutes < 0) {
            $effectiveRemainingMinutes = 0;
        }

        foreach ($breaks as $b) {

            if ($now > $b['end']) {
                $breakMinutesPassed += $b['start']->diffInMinutes($b['end']);
            }

            elseif ($now >= $b['start'] && $now < $b['end']) {
                $breakMinutesPassed += $b['start']->diffInMinutes($now);
            }

            elseif ($now < $b['start']) {
                $futureBreakMinutes += $b['start']->diffInMinutes($b['end']);
            }
        }

        $effectiveMinutesPassed = max($minutesPassed - $breakMinutesPassed, 0);
        $effectiveRemainingMinutes = max($remainingMinutes - $futureBreakMinutes, 0);

        // HOURS
        $hoursPassed = round($effectiveMinutesPassed / 60, 2);
        $remainingHours = round($effectiveRemainingMinutes / 60, 2);

        /*
        ====================================
        SPEED CALCULATION
        ====================================
        */

        $currentSpeed = ($effectiveMinutesPassed > 0)
            ? round(($totalOk / $effectiveMinutesPassed) * 60, 1)
            : 0;

        $requiredSpeed = ($effectiveRemainingMinutes > 0 && $gap > 0)
            ? round(($gap / $effectiveRemainingMinutes) * 60, 1)
            : 0;

        /*
        ====================================
        MACHINE STATUS
        ====================================
        */

        $openAbnormality = 0;
        $activeDowntime = 0;

        /*
        ====================================
        PRODUCTION TREND CHART (TIDAK DIUBAH)
        ====================================
        */

        $chartQuery = ProductionProcess::query();

        $chartQuery->whereDate('created_at', $dateFrom);

        if (request('process_type')) {
            $chartQuery->where('process_type', request('process_type'));
        }

        if (request('shift')) {
            $chartQuery->where('shift', request('shift'));
        }

        $chartData = $chartQuery
            ->selectRaw("HOUR(created_at) as hour, SUM(qty_ok) as total_ok")
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        $chartLabels = [];
        $actualProduction = [];
        $expectedProduction = [];

        $cumulative = 0;

        foreach ($chartData as $row) {

            $label = str_pad($row->hour,2,'0',STR_PAD_LEFT).":00";

            $chartLabels[] = $label;

            $cumulative += $row->total_ok;

            $actualProduction[] = $cumulative;

        }

        if ($targetQty > 0 && count($chartLabels) > 0) {

            $step = $targetQty / count($chartLabels);

            $expected = 0;

            foreach ($chartLabels as $label) {

                $expected += $step;

                $expectedProduction[] = round($expected);

            }

        }

        /*
        ====================================
        RECENT PRODUCTION (TIDAK DIUBAH)
        ====================================
        */

        $latestProductions = $query
            ->latest()
            ->paginate(10)
            ->withQueryString();

        /*
        ====================================
        STATUS
        ====================================
        */

        $status = 'waiting';

        if (($requiredSpeed ?? 0) > 0) {
            $status = $currentSpeed >= $requiredSpeed ? 'on_track' : 'behind';
        }

        // realtime shift egine
        

        /*
        ====================================
        RETURN
        ====================================
        */

        return view('supervisor.dashboard', [

            'totalOk' => $totalOk,
            'totalRepair' => $totalRepair,
            'totalReject' => $totalReject,
            'rejectRate' => $rejectRate,
            'okPercent' => $okPercent,
            'repairPercent' => $repairPercent, 
            'rejectPercent' => $rejectPercent,

            'targetQty' => $targetQty,
            'achievementPercent' => $achievementPercent,
            'gap' => $gap,
            'performanceColor' => $performanceColor,

            'remainingHours' => $remainingHours,
            'currentSpeed' => $currentSpeed,
            'requiredSpeed' => $requiredSpeed,
            
            'openAbnormality' => $openAbnormality,
            'activeDowntime' => $activeDowntime,

            'chartLabels' => $chartLabels,
            'actualProduction' => $actualProduction,
            'expectedProduction' => $expectedProduction,

            'latestProductions' => $latestProductions,
            'status' => $status,
            
            'shift' => $shift,
            'isOvertime' => $isOvertime,
            'shiftStart' => $shiftStart->format('H:i'),
            'shiftEnd' => $shiftEnd->format('H:i'),

            'isBreak' => $isBreak,
            'breakStart' => $currentBreakStart?->format('H:i'),
            'breakEnd' => $currentBreakEnd?->format('H:i'),
            'serverNow' => now()->format('Y-m-d H:i:s'),
            'shiftStartFull' => $shiftStart->format('Y-m-d H:i:s'),
            'shiftEndFull' => $shiftEnd->format('Y-m-d H:i:s'),

        ]);

    }
}