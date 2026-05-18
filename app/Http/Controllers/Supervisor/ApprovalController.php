<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    public function production()
    {
        return view('supervisor.approval.production');
    }
    
    public function quality()
    {
        return view('supervisor.approval.quality');
    }
}
