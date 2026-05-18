<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductionLine;
use Carbon\Carbon;

class QCheckController extends Controller
{
    public function index(Request $request)
    {
        $top_dropdown_lines = ProductionLine::all();
        $history_dropdown_lines = ProductionLine::all();
        
        $selected_history_date = $request->input('history_date', Carbon::today()->format('Y-m-d'));
        $selected_history_line = $request->input('history_line', '');
        
        $qcheck_history = [];
        $total_duration_history = 0;
        
        return view('supervisor.qcheck.index', compact(
            'top_dropdown_lines',
            'history_dropdown_lines',
            'selected_history_date',
            'selected_history_line',
            'qcheck_history',
            'total_duration_history'
        ));
    }

    public function select(Request $request)
    {
        $line_id = $request->input('line');
        $shift = $request->input('shift');
        
        $selected_line = ProductionLine::find($line_id) ?? new ProductionLine(['namaline' => 'Semua Line']);
        $selected_shift = $shift ?? '-';
        $items = []; // Fetch items based on line and shift
        
        return view('supervisor.qcheck.select', compact(
            'selected_line',
            'selected_shift',
            'items'
        ));
    }

    public function list($id)
    {
        // Mock detail job object
        $detail_job = (object)[
            'id_detailjob' => $id,
            'id_itemproduksi' => (object)['job_number' => 'JOB-'.$id]
        ];
        
        $qcheck_status = [];
        $total_duration = 0;
        
        return view('supervisor.qcheck.list', compact(
            'detail_job',
            'qcheck_status',
            'total_duration'
        ));
    }
    
    public function form($id = null)
    {
        // Add or Edit QCheck
        $qc = null;
        if ($id) {
            // $qc = QCheck::findOrFail($id);
        }
        
        $qc_types = [
            ['1', 'First Piece Check'],
            ['2', 'In Process Check'],
            ['3', 'Last Piece Check'],
            ['4', 'Special Check']
        ];
        
        $detail_job = (object)[
            'id_detailjob' => request('detail_job_id', 1),
            'id_itemproduksi' => (object)['job_number' => 'JOB-'.request('detail_job_id', 1)]
        ];
        
        return view('supervisor.qcheck.form', compact('qc', 'qc_types', 'detail_job'));
    }
}
