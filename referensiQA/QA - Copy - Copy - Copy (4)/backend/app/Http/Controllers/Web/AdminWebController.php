<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminWebController extends Controller
{
    public function users()
    {
        return view('admin.users', ['pageTitle' => 'User Management']);
    }

    public function machines()
    {
        return view('admin.machines', ['pageTitle' => 'Master Data Mesin / Line']);
    }

    public function defects()
    {
        return view('admin.defects', ['pageTitle' => 'Master Data Jenis Defect / NG']);
    }
}
