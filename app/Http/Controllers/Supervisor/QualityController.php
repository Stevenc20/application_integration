<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class QualityController extends Controller
{
    public function defectMonitoring()
    {
        return view('supervisor.quality.defect_monitoring');
    }
    
    public function rejectAnalysis()
    {
        return view('supervisor.quality.reject_analysis');
    }
}
