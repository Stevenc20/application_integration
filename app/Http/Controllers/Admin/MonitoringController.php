<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Machine;
use App\Models\MachineLog;
use App\Models\Downtime;
use App\Models\LineMaster;
use Carbon\Carbon;

class MonitoringController extends Controller
{

    public function line()
    {
        $lines = LineMaster::where('status', 'active')
            ->select('line_name')->distinct()->pluck('line_name')->toArray();
        $selectedDate = request('date', now()->toDateString());
        $selectedShift = (int) request('shift', 1);
        $lineKpi = collect([]);
        return view('monitoring.line', compact('lines', 'selectedDate', 'selectedShift', 'lineKpi'));
    }

    public function lineApiData()
    {
        $date  = request('date', now()->toDateString());
        $shift = (int) request('shift', 1);

        $lines = LineMaster::where('status', 'active')
            ->select('line_name')->distinct()->pluck('line_name')->toArray();

        $dashService = app(\App\Services\DashboardRealtimeService::class);
        $statuses = \App\Services\LineStatusService::getStatuses($shift);

        $lineKpi = [];
        foreach ($lines as $lineName) {
            $metrics = $dashService->getLineMetrics($lineName, $date, $shift);

            $kpiRows = $metrics['kpi'] ?? [];
            $meta = $metrics['meta'] ?? [];
            $detail = $metrics['detailData'] ?? [];

            $st = $statuses[$lineName] ?? ['label' => 'NOT RUNNING', 'color' => 'gray', 'pulse' => false];

            // Build jobs from QTY detail rows
            $jobs = [];
            $qtyDetail = $detail['QTY'][$lineName] ?? [];
            foreach ($qtyDetail['rows'] ?? [] as $r) {
                $jobs[] = [
                    'job_number'   => $r['item'] ?? '-',
                    'job_name'     => $r['item'] ?? '-',
                    'actual_ok'    => $r['ok'] ?? 0,
                    'actual_repair'=> $r['repair'] ?? 0,
                    'actual_reject'=> $r['reject'] ?? 0,
                    'target_qty'   => 0,
                    'actual_qty'   => ($r['ok'] ?? 0) + ($r['repair'] ?? 0) + ($r['reject'] ?? 0),
                    'status'       => 'done',
                    'segments'     => [],
                ];
            }

            // Build downtimeByType from TOTAL_DT detail rows
            $downtimeByType = [];
            $dtDetail = $detail['TOTAL_DT'][$lineName] ?? [];
            foreach ($dtDetail['rows'] ?? [] as $dt) {
                $jenis = $dt['jenis'] ?? 'other';
                if (!isset($downtimeByType[$jenis])) {
                    $downtimeByType[$jenis] = 0;
                }
                $downtimeByType[$jenis] += ($dt['durasi'] ?? 0) * 60;
            }

            $lineKpi[$lineName] = [
                'rows'           => $kpiRows,
                'status'         => $st,
                'running'        => $st['label'] === 'PRODUCTION',
                'jobs'           => $jobs,
                'downtimeByType' => $downtimeByType,
            ];
        }

        return response()->json(['line_kpi' => $lineKpi]);
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

    public function history($type, Request $request)
    {
        $validTypes = ['downtime', 'break'];
        if (!in_array($type, $validTypes)) {
            abort(404);
        }

        $selectedDate = $request->date ?? now()->toDateString();
        $selectedLine = $request->line ?? '';
        $selectedShift = (int) $request->get('shift', 0);
        $planId = $request->plan_id;

        $query = Downtime::with('jobMaster')
            ->whereDate('start_time', $selectedDate);

        // Filter by specific plan if provided (from LKH DT link)
        if ($planId) {
            $plan = \App\Models\ProductionPlan::find($planId);
            if ($plan && trim($plan->job_no ?? '')) {
                $jobNumber = trim($plan->job_no);
                $query->whereHas('jobMaster', function($q) use ($jobNumber) {
                    $q->where('job_number', 'like', $jobNumber . '%');
                });
            }
        }

        if ($type === 'downtime') {
            $query->whereNotIn('jenis_downtime', ['break time', 'dandori', 'idle', 'idle time', 'qcheck', 'q check', 'quality', 'try out', 'tryout', 'repair', 'reject']);
        } elseif ($type === 'break') {
            $query->where('jenis_downtime', 'break time');
        }

        // Filter by specific jenis_downtime if provided (from analytics links)
        if ($jenis = $request->jenis) {
            $cleanJenis = str_replace('+', ' ', $jenis);
            $query->where('jenis_downtime', 'like', $cleanJenis);
        }

        if ($selectedLine) {
            $query->whereHas('jobMaster', function($q) use ($selectedLine) {
                $q->where('line', $selectedLine);
            });
        }

        if ($selectedShift === 1) {
            $query->whereTime('start_time', '>=', '07:30:00')
                  ->whereTime('start_time', '<', '21:00:00');
        } elseif ($selectedShift === 2) {
            $query->where(function($q) {
                $q->whereTime('start_time', '>=', '21:00:00')
                  ->orWhereTime('start_time', '<', '07:30:00');
            });
        }

        $records = $query->orderBy('start_time', 'desc')->paginate(50);

        $downtimeList = $records->map(function($d) {
            return [
                'id' => $d->id,
                'jenis' => $d->jenis_downtime,
                'problem' => $d->problem,
                'penyebab' => $d->penyebab,
                'action' => $d->action,
                'pic' => $d->pic,
            ];
        });

        $uneditedCount = Downtime::whereDate('start_time', $selectedDate)
            ->where(function($q) {
                $q->whereNull('problem')
                  ->orWhere('problem', '')
                  ->orWhere('problem', '-');
            })
            ->count();

        $lines = LineMaster::where('status', 'active')
            ->orderBy('line_name')
            ->pluck('line_name');

        return view('monitoring.history', compact(
            'type', 'selectedDate', 'selectedLine', 'selectedShift',
            'records', 'downtimeList', 'uneditedCount', 'lines'
        ));
    }

    public function downtimeList(Request $request)
    {
        $selectedDate = $request->date ?? now()->toDateString();
        $selectedLine = $request->line ?? '';
        $selectedShift = (int) $request->get('shift', 0);

        $excluded = ['break time', 'dandori', 'idle', 'idle time', 'qcheck', 'q check', 'quality', 'try out', 'tryout', 'repair', 'reject'];

        $query = Downtime::with('jobMaster')
            ->whereDate('start_time', $selectedDate)
            ->whereNotIn('jenis_downtime', $excluded);

        if ($selectedLine) {
            $query->whereHas('jobMaster', function($q) use ($selectedLine) {
                $q->where('line', $selectedLine);
            });
        }

        if ($selectedShift === 1) {
            $query->whereTime('start_time', '>=', '07:30:00')
                  ->whereTime('start_time', '<', '21:00:00');
        } elseif ($selectedShift === 2) {
            $query->where(function($q) {
                $q->whereTime('start_time', '>=', '21:00:00')
                  ->orWhereTime('start_time', '<', '07:30:00');
            });
        }

        $records = $query->orderBy('start_time', 'desc')->paginate(50);

        $downtimeList = $records->map(function($d) {
            return [
                'id' => $d->id,
                'jenis' => $d->jenis_downtime,
                'problem' => $d->problem,
                'penyebab' => $d->penyebab,
                'action' => $d->action,
                'pic' => $d->pic,
            ];
        });

        $uneditedCount = Downtime::whereDate('start_time', $selectedDate)
            ->whereNotIn('jenis_downtime', $excluded)
            ->where(function($q) {
                $q->whereNull('problem')
                  ->orWhere('problem', '')
                  ->orWhere('problem', '-');
            })
            ->count();

        $lines = LineMaster::where('status', 'active')
            ->orderBy('line_name')
            ->pluck('line_name');

        return view('monitoring.downtime_list', compact(
            'selectedDate', 'selectedLine', 'selectedShift',
            'records', 'downtimeList', 'uneditedCount', 'lines'
        ));
    }

    public function tryout(Request $request)
    {
        $selectedDate = $request->date ?? now()->toDateString();
        $selectedLine = $request->line ?? '';
        $selectedShift = (int) $request->get('shift', 0);

        $query = Downtime::with('jobMaster')
            ->whereDate('start_time', $selectedDate)
            ->whereIn('jenis_downtime', ['try out', 'tryout']);

        if ($selectedLine) {
            $query->whereHas('jobMaster', function($q) use ($selectedLine) {
                $q->where('line', $selectedLine);
            });
        }

        if ($selectedShift === 1) {
            $query->whereTime('start_time', '>=', '07:30:00')
                  ->whereTime('start_time', '<', '21:00:00');
        } elseif ($selectedShift === 2) {
            $query->where(function($q) {
                $q->whereTime('start_time', '>=', '21:00:00')
                  ->orWhereTime('start_time', '<', '07:30:00');
            });
        }

        $records = $query->orderBy('start_time', 'desc')->paginate(50);

        $downtimeList = $records->map(function($d) {
            return [
                'id' => $d->id,
                'jenis' => $d->jenis_downtime,
                'problem' => $d->problem,
                'penyebab' => $d->penyebab,
                'action' => $d->action,
                'pic' => $d->pic,
            ];
        });

        $uneditedCount = Downtime::whereDate('start_time', $selectedDate)
            ->whereIn('jenis_downtime', ['try out', 'tryout'])
            ->where(function($q) {
                $q->whereNull('problem')
                  ->orWhere('problem', '')
                  ->orWhere('problem', '-');
            })
            ->count();

        $lines = LineMaster::where('status', 'active')
            ->orderBy('line_name')
            ->pluck('line_name');

        return view('monitoring.tryout', compact(
            'selectedDate', 'selectedLine', 'selectedShift',
            'records', 'downtimeList', 'uneditedCount', 'lines'
        ));
    }

    public function uneditedCount(Request $request)
    {
        $date = $request->date ?? now()->toDateString();

        $count = Downtime::whereDate('start_time', $date)
            ->where(function($q) {
                $q->whereNull('problem')
                  ->orWhere('problem', '')
                  ->orWhere('problem', '-');
            })
            ->count();

        return response()->json(['count' => $count]);
    }
}