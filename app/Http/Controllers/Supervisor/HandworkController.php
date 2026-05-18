<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductionLine;
use Carbon\Carbon;

class HandworkController extends Controller
{
    public function index(Request $request)
    {
        $top_dropdown_lines = ProductionLine::all();
        $history_dropdown_lines = ProductionLine::all();
        
        $selected_history_date = $request->input('history_date', Carbon::today()->format('Y-m-d'));
        $selected_history_line = $request->input('history_line', '');
        
        $handwork_history = []; // Replace with actual query
        
        return view('supervisor.handwork.index', compact(
            'top_dropdown_lines',
            'history_dropdown_lines',
            'selected_history_date',
            'selected_history_line',
            'handwork_history'
        ));
    }

    public function select(Request $request)
    {
        $selected_date_str = $request->input('tanggal', Carbon::today()->format('Y-m-d'));
        $semua_detailjob = []; // Fetch active jobs
        $history_handwork = []; // Fetch history for selected date
        
        return view('supervisor.handwork.select', compact(
            'selected_date_str',
            'semua_detailjob',
            'history_handwork'
        ));
    }

    public function rekap($id)
    {
        // View for specific handwork recording
        // $detail_job = DetailJob::findOrFail($id);
        $total_ok = 0;
        $total_reject = 0;
        $handwork_items = [];
        
        return view('supervisor.handwork.rekap', compact(
            // 'detail_job',
            'total_ok',
            'total_reject',
            'handwork_items'
        ));
    }
}
