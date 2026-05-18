<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\JobMaster;
use App\Models\ProductionLine;
use App\Models\Karyawan;
use App\Models\ItemProduksi;
use App\Models\Machine;
use Carbon\Carbon;

class JobController extends Controller
{
    public function index(Request $request)
    {
        $selected_date_str = $request->input('tanggal', Carbon::today()->format('Y-m-d'));
        
        $filterjob = JobMaster::whereDate('created_at', $selected_date_str)
            ->get();
            
        return view('supervisor.job.index', compact('filterjob', 'selected_date_str'));
    }

    public function create()
    {
        $dataproductionline = ProductionLine::all();
        $datakaryawan = Karyawan::all();
        $dataitemproduksi = ItemProduksi::all();
        $machines = Machine::all();
        
        // Convert to JSON for JS in view
        $items_json_str = $dataitemproduksi->toJson();
        $machines_json_str = $machines->toJson();
        
        return view('supervisor.job.create', compact(
            'dataproductionline',
            'datakaryawan',
            'dataitemproduksi',
            'machines',
            'items_json_str',
            'machines_json_str'
        ));
    }

    public function store(Request $request)
    {
        // 1. Get Line Info
        $line = ProductionLine::find($request->id_productionline);
        
        // 2. Create JobMaster (The actual production header)
        $job = new JobMaster();
        $job->job_number = 'JOB-' . strtoupper($line->namaline ?? 'LINE') . '-' . now()->format('YmdHis');
        $job->job_name   = 'Production ' . ($line->namaline ?? 'Line');
        $job->line       = $line->namaline ?? 'Line A';
        $job->capacity   = $request->stroke_plan ?? 0;
        $job->status     = 'pending'; // Start as pending
        $job->started_at = null;
        $job->save();

        // 3. Create DailyProduction record (The Plan/Target)
        \App\Models\DailyProduction::create([
            'job_master_id' => $job->id,
            'work_date'     => $request->date ?? now()->toDateString(),
            'line'          => $job->line,
            'shift'         => $line->shift ?? 1,
            'target_qty'    => $request->gsph_plan ?? 0,
            'remarks'       => 'Initial Plan'
        ]);

        return redirect()->route('supervisor.job.index')->with('success', 'Job and Production Plan created successfully.');
    }

    public function edit($id)
    {
        $jobobj = JobMaster::findOrFail($id);
        $dataproductionline = ProductionLine::all();
        $datakaryawan = Karyawan::all();
        $tanggal = Carbon::parse($jobobj->date)->format('Y-m-d');
        
        return view('supervisor.job.update', compact('jobobj', 'dataproductionline', 'datakaryawan', 'tanggal'));
    }

    public function update(Request $request, $id)
    {
        $job = JobMaster::findOrFail($id);
        $job->id_productionline = $request->id_productionline;
        $job->id_karyawan = $request->id_karyawan;
        $job->date = $request->date;
        $job->save();

        return redirect()->route('supervisor.job.index')->with('success', 'Job updated successfully.');
    }

    public function destroy($id)
    {
        $job = JobMaster::findOrFail($id);
        $job->delete();
        
        return redirect()->route('supervisor.job.index')->with('success', 'Job deleted successfully.');
    }
}
