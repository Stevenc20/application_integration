<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Machine;
use App\Models\MachineLog;
use Carbon\Carbon;

class MonitoringController extends Controller
{

    public function line()
    {
        $lines = ['A', 'B', 'C', 'D'];
        return view('monitoring.line', compact('lines'));
    }

    public function tv()
    {
        $alerts = collect([
            (object)['line' => 'A','message' => 'Reject Rate High'],
            (object)['line' => 'B','message' => 'Machine Stop'],
            (object)['line' => 'C','message' => 'Material Jam']
        ]);

        $lines = collect([
            (object)['name' => 'A', 'status' => 'running'],
            (object)['name' => 'B', 'status' => 'downtime'],
            (object)['name' => 'C', 'status' => 'slow'],
            (object)['name' => 'D', 'status' => 'running'],
        ]);

        $totalProduction = 24850;
        $averageSpeed = 480;
        $activeLines = 3;

        return view('monitoring.tv', compact(
            'alerts',
            'lines',
            'totalProduction',
            'averageSpeed',
            'activeLines'
        ));
    }

    // MACHINE STATUS
    public function machine_status(Request $request)
    {
        $date = $request->date ?? now()->toDateString();

        $machines = Machine::with(['logs' => function($q) use ($date){
            $q->whereDate('created_at', $date)
              ->latest();
        }])->get();

        $machines = $machines->map(function($machine){

            $latestLog = $machine->logs->first();
            $status = $latestLog->status ?? 'unknown';

            $downtime = '-';

            if($status === 'downtime' && $latestLog?->downtime_start){
                $start = Carbon::parse($latestLog->downtime_start);
                $end = $latestLog->downtime_end 
                    ? Carbon::parse($latestLog->downtime_end)
                    : now();

                $downtime = $start->diffInMinutes($end) . ' min';
            }

            return (object)[
                'name' => $machine->name,
                'line' => $machine->line,
                'status' => $status,
                'downtime' => $downtime
            ];
        });

        return view('monitoring.machine_status', compact('machines'));
    }

    public function index()
    {
        return redirect()->route('monitoring.line');
    }
}