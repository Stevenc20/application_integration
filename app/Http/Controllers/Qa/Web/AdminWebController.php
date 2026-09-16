<?php

namespace App\Http\Controllers\Qa\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminWebController extends Controller
{
    public function users()
    {
        return view('qa.admin.users', ['pageTitle' => 'User Management']);
    }

    public function machines()
    {
        return view('qa.admin.machines', ['pageTitle' => 'Master Data Mesin / Line']);
    }

    public function defects()
    {
        return view('qa.admin.defects', ['pageTitle' => 'Master Data Jenis Defect / NG']);
    }
}
