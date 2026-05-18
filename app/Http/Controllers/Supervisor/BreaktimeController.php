<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BreakTime;

class BreaktimeController extends Controller
{
    public function index()
    {
        $semua_break = BreakTime::all(); // Mocking data retrieval
        return view('supervisor.breaktime.index', compact('semua_break'));
    }

    public function create()
    {
        $break_obj = null;
        $choices_hari = [
            [1, 'Senin'],
            [2, 'Selasa'],
            [3, 'Rabu'],
            [4, 'Kamis'],
            [5, 'Jumat'],
            [6, 'Sabtu'],
            [7, 'Minggu']
        ];
        return view('supervisor.breaktime.create', compact('break_obj', 'choices_hari'));
    }

    public function store(Request $request)
    {
        // Simple store logic
        $break = new BreakTime();
        $break->nama_istirahat = $request->nama_istirahat;
        $break->waktu_mulai = $request->waktu_mulai;
        $break->waktu_selesai = $request->waktu_selesai;
        $break->shift = $request->shift;
        $break->hari = $request->hari;
        $break->save();

        return redirect()->route('supervisor.breaktime.index')->with('success', 'Break time created successfully.');
    }

    public function edit($id)
    {
        $break_obj = BreakTime::findOrFail($id);
        $choices_hari = [
            [1, 'Senin'],
            [2, 'Selasa'],
            [3, 'Rabu'],
            [4, 'Kamis'],
            [5, 'Jumat'],
            [6, 'Sabtu'],
            [7, 'Minggu']
        ];
        return view('supervisor.breaktime.create', compact('break_obj', 'choices_hari')); // Reusing create form
    }

    public function update(Request $request, $id)
    {
        $break = BreakTime::findOrFail($id);
        $break->nama_istirahat = $request->nama_istirahat;
        $break->waktu_mulai = $request->waktu_mulai;
        $break->waktu_selesai = $request->waktu_selesai;
        $break->shift = $request->shift;
        $break->hari = $request->hari;
        $break->save();

        return redirect()->route('supervisor.breaktime.index')->with('success', 'Break time updated successfully.');
    }

    public function destroy($id)
    {
        $break = BreakTime::findOrFail($id);
        $break->delete();
        return redirect()->route('supervisor.breaktime.index')->with('success', 'Break time deleted successfully.');
    }
}
