<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductionProcess;
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

        $query = ProductionProcess::with('job')
                    ->whereDate('created_at', $date);

        // filter khusus operator
        if($user->role == 'operator'){
            $query->where('user_id', $user->id);
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
            'production_order_number' => 'required|string|max:255',
            'job_id' => 'required|exists:job_masters,id',
            'process_type' => 'required|string|max:100',
            'shift' => 'required|string|max:50',
            'qty_ok' => 'required|integer|min:0',
            'qty_repair' => 'nullable|integer|min:0',
            'qty_reject' => 'nullable|integer|min:0',
        ]);

        try {

            ProductionProcess::create([
                'production_order_number' => $validated['production_order_number'],
                'job_id' => $validated['job_id'],
                'process_type' => $validated['process_type'],
                'shift' => $validated['shift'],
                'qty_ok' => $validated['qty_ok'],
                'qty_repair' => $validated['qty_repair'] ?? 0,
                'qty_reject' => $validated['qty_reject'] ?? 0,
                'status' => 'pending'
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
            'production_order_number' => 'required|string|max:255',
            'job_id' => 'required|exists:job_masters,id',
            'process_type' => 'required|string|max:100',
            'shift' => 'required|string|max:50',
            'qty_ok' => 'required|integer|min:0',
            'qty_repair' => 'nullable|integer|min:0',
            'qty_reject' => 'nullable|integer|min:0',
        ]);

        $production = ProductionProcess::findOrFail($id);

        $production->update([
            'production_order_number' => $validated['production_order_number'],
            'job_id' => $validated['job_id'],
            'process_type' => $validated['process_type'],
            'shift' => $validated['shift'],
            'qty_ok' => $validated['qty_ok'],
            'qty_repair' => $validated['qty_repair'] ?? 0,
            'qty_reject' => $validated['qty_reject'] ?? 0
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

        $production = ProductionProcess::findOrFail($id);

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

        $productions = ProductionProcess::with('job')
            ->whereMonth('created_at', date('m', strtotime($month)))
            ->whereYear('created_at', date('Y', strtotime($month)))
            ->get();

        $dateLabel = \Carbon\Carbon::parse($month)->format('F Y');

    }elseif($type == 'weekly'){

        $start = $request->start ?? now()->startOfWeek()->toDateString();
        $end   = $request->end ?? now()->endOfWeek()->toDateString();

        $productions = ProductionProcess::with('job')
            ->whereBetween('created_at', [$start, $end])
            ->get();

        $dateLabel = $start . ' - ' . $end;

    }else{

        $date = $request->date ?? now()->toDateString();

        $productions = ProductionProcess::with('job')
            ->whereDate('created_at', $date)
            ->get();

        $dateLabel = \Carbon\Carbon::parse($date)->format('d F Y');
    }

    $summary = [
        'ok' => $productions->sum('qty_ok'),
        'repair' => $productions->sum('qty_repair'),
        'reject' => $productions->sum('qty_reject'),
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
        $user = Auth::user();

        // Ambil data production milik operator (jika nanti ada user_id)
        $productions = ProductionProcess::latest()
            ->take(20)
            ->get();

        return view('production.history', compact('productions'));
    }
}



