<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\LembarInspeksi;
use Illuminate\Http\Request;

class LembarInspeksiWebController extends Controller
{
    public function index()
    {
        return view('li.index', [
            'pageTitle' => 'Lembar Inspeksi'
        ]);
    }

    public function create()
    {
        return view('li.form', [
            'pageTitle' => 'Buat Lembar Inspeksi Baru'
        ]);
    }

    public function edit($id)
    {
        $item = LembarInspeksi::findOrFail($id);
        return view('li.form', [
            'pageTitle' => 'Edit Lembar Inspeksi',
            'item' => $item
        ]);
    }

    public function masterTemplate()
    {
        return view('li.master-template', ['pageTitle' => 'Master Template LI']);
    }

    public function summary()
    {
        return view('li.summary', ['pageTitle' => 'Summary/LHI']);
    }


    public function rekap()
    {
        return view('li.rekap', ['pageTitle' => 'Dashboard Rekap']);
    }

    public function print($id)
    {
        $item = LembarInspeksi::findOrFail($id);
        return view('li.print', [
            'pageTitle' => 'Print Lembar Inspeksi',
            'item' => $item
        ]);
    }
}
