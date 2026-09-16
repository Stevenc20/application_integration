<?php

namespace App\Http\Controllers\Qa\Web;

use App\Http\Controllers\Controller;
use App\Models\LembarInspeksi;
use Illuminate\Http\Request;

class LembarInspeksiWebController extends Controller
{
    public function index()
    {
        return view('qa.li.index', [
            'pageTitle' => 'Lembar Inspeksi'
        ]);
    }

    public function create()
    {
        return view('qa.li.form', [
            'pageTitle' => 'Buat Lembar Inspeksi Baru'
        ]);
    }

    public function edit($id)
    {
        $item = LembarInspeksi::findOrFail($id);
        return view('qa.li.form', [
            'pageTitle' => 'Edit Lembar Inspeksi',
            'item' => $item
        ]);
    }

    public function masterTemplate()
    {
        return view('qa.li.master-template', ['pageTitle' => 'Master Template LI']);
    }

    public function summary()
    {
        return view('qa.li.summary', ['pageTitle' => 'Summary/LHI']);
    }


    public function rekap()
    {
        return view('qa.li.rekap', ['pageTitle' => 'Dashboard Rekap']);
    }

    public function print($id)
    {
        $item = LembarInspeksi::findOrFail($id);
        return view('qa.li.print', [
            'pageTitle' => 'Print Lembar Inspeksi',
            'item' => $item
        ]);
    }
}
