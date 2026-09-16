<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DailyProduction;
use App\Models\JobMaster;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProductionRecapExport;
use Illuminate\Support\Facades\Auth;

class ProductionController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Show Production Entry Page
    |--------------------------------------------------------------------------
    */
    public function index(Request $request)
    {
        $user = auth()->user();

        // ambil tanggal dari filter / default hari ini
        $date = $request->date ?? now()->toDateString();

        $query = DailyProduction::with('jobMaster')
                    ->whereDate('work_date', $date);

        // filter khusus operator
        if($user->role == 'operator'){
            $query->where('saved_by', $user->id);
        }

        $productions = $query->latest()->get();

        $jobs = JobMaster::orderBy('job_number')->get();

        return view('production.production_entry', [
            'productions' => $productions,
            'jobs' => $jobs,
            'date' => $date
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Store Production Data
    |--------------------------------------------------------------------------
    */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'job_id' => 'required|exists:job_masters,id',
            'qty_ok' => 'required|integer|min:0',
            'qty_repair' => 'nullable|integer|min:0',
            'qty_reject' => 'nullable|integer|min:0',
        ]);

        try {
            $ok = $validated['qty_ok'];
            $repair = $validated['qty_repair'] ?? 0;
            $reject = $validated['qty_reject'] ?? 0;

            DailyProduction::create([
                'job_master_id' => $validated['job_id'],
                'work_date' => now()->toDateString(),
                'actual_qty' => $ok + $repair + $reject,
                'actual_ok' => $ok,
                'actual_repair' => $repair,
                'actual_reject' => $reject,
                'saved_by' => auth()->id()
            ]);

            return redirect()
                ->route('production_entry')
                ->with('success', 'Production data saved successfully');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to save production: ' . $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Update Production
    |--------------------------------------------------------------------------
    */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'job_id' => 'required|exists:job_masters,id',
            'qty_ok' => 'required|integer|min:0',
            'qty_repair' => 'nullable|integer|min:0',
            'qty_reject' => 'nullable|integer|min:0',
        ]);

        $production = DailyProduction::findOrFail($id);

        $ok = $validated['qty_ok'];
        $repair = $validated['qty_repair'] ?? 0;
        $reject = $validated['qty_reject'] ?? 0;

        $production->update([
            'job_master_id' => $validated['job_id'],
            'actual_qty' => $ok + $repair + $reject,
            'actual_ok' => $ok,
            'actual_repair' => $repair,
            'actual_reject' => $reject
        ]);

        return redirect()
            ->back()
            ->with('success', 'Production updated successfully');
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Production
    |--------------------------------------------------------------------------
    */
    public function delete($id)
    {
        $production = DailyProduction::findOrFail($id);
        $production->delete();

        return redirect()
            ->back()
            ->with('success', 'Production deleted successfully');
    }

    /*
    |--------------------------------------------------------------------------
    | Production Recap
    |--------------------------------------------------------------------------
    */
    public function recap(Request $request)
    {
        $type = $request->type ?? 'daily';

        if($type == 'monthly'){
            $month = $request->month ?? now()->format('Y-m');

            $productions = DailyProduction::with('jobMaster')
                ->whereMonth('work_date', date('m', strtotime($month)))
                ->whereYear('work_date', date('Y', strtotime($month)))
                ->get();

            $dateLabel = \Carbon\Carbon::parse($month)->format('F Y');

        }elseif($type == 'weekly'){
            $start = $request->start ?? now()->startOfWeek()->toDateString();
            $end   = $request->end ?? now()->endOfWeek()->toDateString();

            $productions = DailyProduction::with('jobMaster')
                ->whereBetween('work_date', [$start, $end])
                ->get();

            $dateLabel = $start . ' - ' . $end;

        }else{
            $date = $request->date ?? now()->toDateString();    

            $productions = DailyProduction::with('jobMaster')
                ->whereDate('work_date', $date)
                ->get();

            $dateLabel = \Carbon\Carbon::parse($date)->format('d F Y');
        }

        $summary = [
            'ok' => $productions->sum('actual_ok'),
            'repair' => $productions->sum('actual_repair'),    
            'reject' => $productions->sum('actual_reject'),
        ];

        $summary['total'] = $summary['ok'] + $summary['repair'] + $summary['reject'];

        $summary['achievement'] = $summary['total'] > 0 
            ? round(($summary['ok'] / $summary['total']) * 100, 2)
            : 0;

        return view('production.production_recap', compact(
            'productions','summary','type','dateLabel'
        ));
    }

    public function export(Request $request)
    {
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\ProductionRecapExport($request),
            'production_recap.xlsx'
        );  
    }

    public function history()
    {
        $productions = DailyProduction::latest()
            ->take(20)
            ->get();    
        return view('production.history', compact('productions'));
    }
}
