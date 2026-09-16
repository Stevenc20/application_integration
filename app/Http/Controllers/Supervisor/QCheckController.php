<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\QCheck;
use App\Models\ProductionLine;
use App\Models\JobMaster;
use Carbon\Carbon;

class QCheckController extends Controller
{
    public function index(Request $request)
    {
        $top_dropdown_lines = ProductionLine::all();
        $history_dropdown_lines = ProductionLine::all();

        $selected_history_date = $request->input('history_date', Carbon::today()->format('Y-m-d'));
        $selected_history_line = $request->input('history_line', '');

        $qcheck_history = [];
        $total_duration_history = 0;

        return view('supervisor.qcheck.index', compact(
            'top_dropdown_lines',
            'history_dropdown_lines',
            'selected_history_date',
            'selected_history_line',
            'qcheck_history',
            'total_duration_history'
        ));
    }

    public function select(Request $request)
    {
        $line_id = $request->input('line');
        $shift = $request->input('shift');

        $selected_line = ProductionLine::find($line_id) ?? new ProductionLine(['namaline' => 'Semua Line']);
        $selected_shift = $shift ?? '-';
        $items = [];

        return view('supervisor.qcheck.select', compact(
            'selected_line',
            'selected_shift',
            'items'
        ));
    }

    public function list($id)
    {
        $detail_job = (object)[
            'id_detailjob' => $id,
            'id_itemproduksi' => (object)['job_number' => 'JOB-'.$id]
        ];

        $qcheck_status = [];
        $total_duration = 0;

        return view('supervisor.qcheck.list', compact(
            'detail_job',
            'qcheck_status',
            'total_duration'
        ));
    }

    public function form($id = null)
    {
        $qc = null;
        if ($id) {
            $qc = QCheck::with('jobMaster')->find($id);
        }

        $qc_types = [
            ['initial_qcheck', 'Initial Q Check'],
            ['material_change_check', 'Material Change Check'],
            ['variant_change_check', 'Variant Change Check (SME)'],
        ];

        $detail_job = (object)[
            'id_detailjob' => request('detail_job_id', 1),
            'id_itemproduksi' => (object)['job_number' => 'JOB-'.request('detail_job_id', 1)]
        ];

        return view('supervisor.qcheck.form', compact('qc', 'qc_types', 'detail_job'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'job_master_id' => 'required|exists:job_masters,id',
            'jenis_qcheck'  => 'required|string|max:50',
            'hasil_qcheck'  => 'required|string|max:100',
            'keterangan'    => 'nullable|string',
            'start_time'    => 'nullable|date',
            'finish_time'   => 'nullable|date|after_or_equal:start_time',
        ]);

        $qcheck = QCheck::create($validated);

        return redirect()->route('supervisor.qcheck.list', $qcheck->job_master_id)
            ->with('success', 'Q-Check berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $qcheck = QCheck::findOrFail($id);

        $validated = $request->validate([
            'job_master_id' => 'sometimes|exists:job_masters,id',
            'jenis_qcheck'  => 'sometimes|string|max:50',
            'hasil_qcheck'  => 'sometimes|string|max:100',
            'keterangan'    => 'nullable|string',
            'start_time'    => 'nullable|date',
            'finish_time'   => 'nullable|date|after_or_equal:start_time',
        ]);

        $qcheck->update($validated);

        return redirect()->route('supervisor.qcheck.list', $qcheck->job_master_id)
            ->with('success', 'Q-Check berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $qcheck = QCheck::findOrFail($id);
        $jobId = $qcheck->job_master_id;
        $qcheck->delete();

        return redirect()->route('supervisor.qcheck.list', $jobId)
            ->with('success', 'Q-Check berhasil dihapus.');
    }
}
