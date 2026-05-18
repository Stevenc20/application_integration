<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\JobMaster;

class JobController extends Controller
{
    public function index()
    {
        $jobs = JobMaster::latest()->paginate(10);
        return view('ppc.job_master', compact('jobs'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'job_number' => 'required|unique:job_masters,job_number',
            'job_name' => 'required',
            'line' => 'required',
            'capacity' => 'required|numeric'
        ]);

        JobMaster::create([
            'job_number' => $request->job_number,
            'job_name' => $request->job_name,
            'line' => $request->line,
            'capacity' => $request->capacity,
        ]);

        return back()->with('success', 'Job berhasil ditambahkan');
    }

    public function delete($id)
    {
        JobMaster::findOrFail($id)->delete();

        return back()->with('success', 'Job berhasil dihapus');
    }

   public function update(Request $request, $id)
    {
        $request->validate([
            'job_number' => 'required',
            'job_name' => 'required',
            'line' => 'required',
            'capacity' => 'required|numeric'
        ]);

        $job = JobMaster::findOrFail($id);

        $job->update([
            'job_number' => $request->job_number,
            'job_name' => $request->job_name,
            'line' => $request->line,
            'capacity' => $request->capacity,
        ]);

        return back()->with('success', 'Job berhasil diupdate');
    }
}