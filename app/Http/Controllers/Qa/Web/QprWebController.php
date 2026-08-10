<?php

namespace App\Http\Controllers\Qa\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Qpr;

class QprWebController extends Controller
{
    public function index()
    {
        return view('qa.qpr.index');
    }

    public function create()
    {
        return view('qa.qpr.form');
    }

    public function edit($id)
    {
        $qpr = \App\Models\Qpr::findOrFail($id);
        if (auth()->user() && auth()->user()->role === 'Operator' && $qpr->created_by !== auth()->id()) {
            abort(403, 'Akses Ditolak: Anda hanya dapat mengedit QPR yang Anda buat sendiri.');
        }
        return view('qa.qpr.form', ['id' => $id]);
    }

    public function preview($id)
    {
        return view('qa.qpr.preview', ['id' => $id]);
    }

    public function registration()
    {
        return view('qa.qpr.registration', ['pageTitle' => 'Formulir Registrasi QPR']);
    }
}
