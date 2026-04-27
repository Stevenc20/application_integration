<?php

namespace App\Http\Controllers\Operational;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\JobMaster;
use App\Models\Dandori;
use Carbon\Carbon;

class DandoriController extends Controller
{
    /* =====================================================
       PAGE DANDORI
    ===================================================== */
    public function index(Request $request)
    {
        $lines = JobMaster::select('line')
            ->whereNotNull('line')
            ->distinct()
            ->orderBy('line')
            ->pluck('line');

        $jobId = $request->job_id;
        $line  = $request->line;
        $shift = $request->shift ?? 'Shift 1';

        return view('operational.dandori', compact(
            'lines',
            'jobId',
            'line',
            'shift'
        ));
    }

    /* =====================================================
       LOAD ITEM CARD ATAS
       DIPANGGIL SAAT KLIK TAMPILKAN ITEM
    ===================================================== */
    public function loadJobs(Request $request)
    {
        $q = JobMaster::query();

        if ($request->line) {
            $q->where('line', $request->line);
        }

        /* ==========================================
           UNTUK TESTING:
           AMBIL SEMUA STATUS DULU
        ========================================== */
        $rows = $q->orderByDesc('updated_at')
            ->orderBy('sequence_no')
            ->get();

        return response()->json($rows);
    }

    /* =====================================================
       OPEN DETAIL JOB
       KLIK CARD ITEM
    ===================================================== */
    public function open($id)
    {
        $job = JobMaster::findOrFail($id);

        $processes = \App\Models\JobProcess::where('job_master_id',$id)
            ->orderBy('sequence_no')
            ->get();

        return view('operational.dandori_detail', compact(
            'job',
            'processes'
        ));
    }
    

    /* =====================================================
       START DANDORI PER AKTIVITAS
    ===================================================== */
    public function start(Request $request)
    {
        $process = JobProcess::findOrFail($request->job_process_id);

        $row = Dandori::create([
            'job_process_id' => $process->id,
            'next_job_id' => $process->job_master_id,
            'activity' => $process->process_name,
            'line' => $request->line,
            'shift' => $request->shift,
            'start_time' => now(),
            'work_date' => now()->toDateString(),
            'created_by' => auth()->id()
        ]);

        return response()->json([
            'success'=>true
        ]);
    }

    /* =====================================================
       FINISH DANDORI
    ===================================================== */
    public function finish($id)
    {
        $row = Dandori::findOrFail($id);

        $finish = now();

        $minutes = Carbon::parse($row->start_time)
            ->diffInSeconds($finish) / 60;

        $row->update([
            'finish_time'      => $finish,
            'duration_minutes' => round($minutes, 2)
        ]);

        return response()->json([
            'success' => true,
            'finish'  => $finish->format('H:i:s'),
            'minutes' => round($minutes, 2)
        ]);
    }

    /* =====================================================
       HISTORY TABLE BAWAH
    ===================================================== */
    public function history(Request $request)
    {
        $q = Dandori::query();

        if ($request->date) {
            $q->whereDate('work_date', $request->date);
        }

        if ($request->line) {
            $q->where('line', $request->line);
        }

        $rows = $q->latest()
            ->take(100)
            ->get()
            ->map(function ($row) {

                $job = JobMaster::find($row->next_job_id);

                return [
                    'id' => $row->id,

                    'job_number' =>
                        $job->job_number ?? '-',

                    'job_name' =>
                        $job->job_name ?? '-',

                    'activity' =>
                        $row->activity,

                    'line' =>
                        $row->line,

                    'shift' =>
                        $row->shift,

                    'start_time' =>
                        $row->start_time
                        ? Carbon::parse($row->start_time)->format('H:i:s')
                        : '-',

                    'finish_time' =>
                        $row->finish_time
                        ? Carbon::parse($row->finish_time)->format('H:i:s')
                        : '-',

                    'duration_minutes' =>
                        number_format(
                            $row->duration_minutes ?? 0,
                            2
                        )
                ];
            });

        return response()->json($rows);
    }

    /* =====================================================
       AUTO OPEN DARI INPUT HARIAN
       SAAT KLIK BUTTON DANDORI
    ===================================================== */
    public function direct($jobId)
    {
        $job = JobMaster::findOrFail($jobId);

        return redirect()->route(
            'operational.dandori',
            [
                'job_id' => $job->id,
                'line'   => $job->line,
                'shift'  => 'Shift 1'
            ]
        );
    }

    public function create($jobId)
{
    $job = JobMaster::findOrFail($jobId);

    /*
    =====================================
    CEK SESSION SUDAH ADA / BELUM
    =====================================
    */

    $check = Dandori::where('next_job_id', $job->id)
        ->whereDate('work_date', now())
        ->first();

    if ($check) {
        return redirect()->route('operational.dandori', [
            'job_id' => $job->id,
            'line'   => $job->line,
            'shift'  => 'Shift 1'
        ]);
    }

    /*
    =====================================
    AUTO CREATE TEMPLATE DANDORI
    =====================================
    */

    $activities = [
        'Persiapan Material',
        'Setup Mesin',
        'Trial Produksi',
        'Fine Tuning',
        'Cleaning / 5S'
    ];

    foreach ($activities as $activity) {

        Dandori::create([
            'next_job_id' => $job->id,
            'line'        => $job->line,
            'shift'       => 'Shift 1',
            'activity'    => $activity,
            'work_date'   => now()->toDateString(),
            'created_by'  => auth()->id()
        ]);
    }

    /*
    =====================================
    REDIRECT KE PAGE DANDORI
    =====================================
    */

    return redirect()->route('operational.dandori', [
        'job_id' => $job->id,
        'line'   => $job->line,
        'shift'  => 'Shift 1'
    ]);
}
}