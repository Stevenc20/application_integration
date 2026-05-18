<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductionLine;
use App\Models\IdleTime;
use Carbon\Carbon;

class IdleTimeController extends Controller
{
    public function index(Request $request)
    {
        $lines = \App\Models\JobMaster::select('line')->whereNotNull('line')->distinct()->get()->map(function($j, $k) {
            return (object)['id' => $k, 'namaline' => $j->line, 'shift' => 1];
        });
        
        $top_dropdown_lines = $lines;
        $history_dropdown_lines = $lines;
        
        $selected_history_date = $request->input('history_date', Carbon::today()->format('Y-m-d'));
        $selected_history_line = $request->input('history_line', '');
        
        // Mocking history query since full schema isn't present
        $idletime_history = []; // Replace with actual query
        $total_duration_history = 0;
        
        return view('supervisor.idletime.index', compact(
            'top_dropdown_lines', 
            'history_dropdown_lines',
            'selected_history_date',
            'selected_history_line',
            'idletime_history',
            'total_duration_history'
        ));
    }

    // Additional methods like rekap would go here
}
