<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class QualityController extends Controller
{

    public function index()
    {
        return view('quality.index');
    }

    public function store(Request $request)
    {

        // later insert to database
        // for now just return back

        return redirect()->back()->with('success','Quality issue saved');
    }

    public function edit($id)
    {
        return view('quality.edit');
    }

    public function update(Request $request, $id)
    {
        return redirect()->back();
    }

    public function destroy($id)
    {
        return redirect()->back();
    }

}