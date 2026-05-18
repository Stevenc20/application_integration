<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GrafikController extends Controller
{
    public function outputLine()
    {
        return view('supervisor.grafik.output_line');
    }
    
    public function downtimeItem()
    {
        return view('supervisor.grafik.downtime_item');
    }
}
