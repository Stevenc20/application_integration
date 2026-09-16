<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\BreakTime;
use App\Models\MasterBreakTime;
use App\Services\TimelineGenerationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class BreaktimeController extends Controller
{
    public function index(Request $request)
    {
        $selectedShift = $request->get('shift', 'pagi');

        if (Schema::hasTable('master_break_times')) {
            $query = MasterBreakTime::orderBy('sort_order')->orderBy('hari');
            $semua_break = $query->get();
            $pagiBreaks = MasterBreakTime::where(function ($q) {
                    $q->whereNull('shift')->orWhere('shift', '')->orWhere('shift', 'Shift Pagi');
                })->orderBy('sort_order')->orderBy('hari')->get();
            $malamBreaks = MasterBreakTime::where('shift', 'Shift Malam')
                ->orderBy('sort_order')->orderBy('hari')->get();
            $useMaster = true;
        } else {
            $semua_break = BreakTime::all();
            $pagiBreaks = collect();
            $malamBreaks = collect();
            $useMaster = false;
        }

        return view('supervisor.breaktime.index', compact('semua_break', 'useMaster', 'selectedShift', 'pagiBreaks', 'malamBreaks'));
    }

    public function create()
    {
        $break_obj = null;
        $useMaster = Schema::hasTable('master_break_times');
        $choices_hari = $this->hariChoices();

        return view('supervisor.breaktime.create', compact('break_obj', 'choices_hari', 'useMaster'));
    }

    public function store(Request $request)
    {
        if (Schema::hasTable('master_break_times')) {
            $data = $request->validate([
                'label' => 'required|string|max:120',
                'hari' => 'required|string|max:20',
                'waktu_mulai' => 'required',
                'waktu_selesai' => 'required',
                'type' => 'required|in:istirahat,cinkorak',
                'shift' => 'nullable|string|max:80',
                'is_active' => 'nullable|boolean',
            ]);

            MasterBreakTime::create([
                'label' => $data['label'],
                'hari' => strtolower($data['hari']),
                'waktu_mulai' => $data['waktu_mulai'],
                'waktu_selesai' => $data['waktu_selesai'],
                'type' => $data['type'],
                'shift' => $data['shift'] ?: null,
                'is_active' => $request->boolean('is_active', true),
            ]);
            app(TimelineGenerationService::class)->regenerateAllSections(true);
        } else {
            $break = new BreakTime();
            $break->nama_istirahat = $request->nama_istirahat;
            $break->waktu_mulai = $request->waktu_mulai;
            $break->waktu_selesai = $request->waktu_selesai;
            $break->shift = $request->shift;
            $break->hari = $request->hari;
            $break->save();
        }

        return redirect()->route('supervisor.breaktime.index')->with('success', 'Break time berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $useMaster = Schema::hasTable('master_break_times');
        $break_obj = $useMaster
            ? MasterBreakTime::findOrFail($id)
            : BreakTime::findOrFail($id);
        $choices_hari = $this->hariChoices();

        return view('supervisor.breaktime.create', compact('break_obj', 'choices_hari', 'useMaster'));
    }

    public function update(Request $request, $id)
    {
        if (Schema::hasTable('master_break_times')) {
            $break = MasterBreakTime::findOrFail($id);
            $data = $request->validate([
                'label' => 'required|string|max:120',
                'hari' => 'required|string|max:20',
                'waktu_mulai' => 'required',
                'waktu_selesai' => 'required',
                'type' => 'required|in:istirahat,cinkorak',
                'shift' => 'nullable|string|max:80',
                'is_active' => 'nullable|boolean',
            ]);

            $break->update([
                'label' => $data['label'],
                'hari' => strtolower($data['hari']),
                'waktu_mulai' => $data['waktu_mulai'],
                'waktu_selesai' => $data['waktu_selesai'],
                'type' => $data['type'],
                'shift' => $data['shift'] ?: null,
                'is_active' => $request->boolean('is_active', true),
            ]);
            app(TimelineGenerationService::class)->regenerateAllSections(true);
        } else {
            $break = BreakTime::findOrFail($id);
            $break->nama_istirahat = $request->nama_istirahat;
            $break->waktu_mulai = $request->waktu_mulai;
            $break->waktu_selesai = $request->waktu_selesai;
            $break->shift = $request->shift;
            $break->hari = $request->hari;
            $break->save();
        }

        return redirect()->route('supervisor.breaktime.index')->with('success', 'Break time berhasil diperbarui.');
    }

    public function destroy($id)
    {
        if (Schema::hasTable('master_break_times')) {
            MasterBreakTime::findOrFail($id)->delete();
            app(TimelineGenerationService::class)->regenerateAllSections(true);
        } else {
            BreakTime::findOrFail($id)->delete();
        }

        return redirect()->route('supervisor.breaktime.index')->with('success', 'Break time berhasil dihapus.');
    }

    private function hariChoices(): array
    {
        return [
            ['senin', 'Senin'],
            ['selasa', 'Selasa'],
            ['rabu', 'Rabu'],
            ['kamis', 'Kamis'],
            ['jumat', 'Jumat'],
            ['sabtu', 'Sabtu'],
            ['minggu', 'Minggu'],
            ['semua', 'Semua Hari'],
        ];
    }
}
