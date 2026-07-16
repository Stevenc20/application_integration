<?php

namespace App\Http\Controllers\Operational;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\JobMaster;
use App\Models\Dandori;
use App\Models\Downtime;
use Carbon\Carbon;

class DandoriController extends Controller
{
    /* =====================================================
       PAGE DANDORI
    ===================================================== */
    public function index(Request $request)
    {
        $lines = \App\Models\LineMaster::where('status', 'active')
            ->orderBy('line_name')
            ->distinct()
            ->pluck('line_name');

        $jobId = $request->job_id;
        $line  = $request->line;
        $shift = $request->shift ?? 'Shift 1';

        $todayStats = [
            'total_events' => \App\Models\Dandori::whereDate('work_date', now())->distinct('next_job_id')->count(),
            'avg_duration' => \App\Models\Dandori::whereDate('work_date', now())->avg('duration_minutes') ?? 0,
            'total_duration' => \App\Models\Dandori::whereDate('work_date', now())->sum('duration_minutes') ?? 0
        ];

        return view('operational.dandori', compact(
            'lines',
            'jobId',
            'line',
            'shift',
            'todayStats'
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
            $lineSearch = str_replace('Line ', '', $request->line);
            $q->where('line', $lineSearch);
        }

        $rows = $q->orderBy('sequence_no')
            ->paginate(10);

        return response()->json($rows);
    }

    /* =====================================================
       GET DETAIL JOB (FOR MODAL)
    ===================================================== */
    public function getDetail($id)
    {
        $job = JobMaster::findOrFail($id);

        $activityTypes = [
            'dandori_process' => 'DANDORI',
            'first_check'     => '1ST CHECK'
        ];

        $dandoriStatus = [];
        foreach ($activityTypes as $code => $display) {
            $record = Dandori::where('next_job_id', $id)
                ->where('activity', $display)
                ->whereDate('work_date', now()->toDateString())
                ->latest()
                ->first();

            $dandoriStatus[] = [
                'type_code'    => $code,
                'type_display' => $display,
                'record'       => $record
            ];
        }

        $totalDuration = Dandori::where('next_job_id', $id)
            ->whereDate('work_date', now()->toDateString())
            ->sum('duration_minutes');

        return response()->json([
            'job'           => $job,
            'dandoriStatus' => $dandoriStatus,
            'totalDuration' => round($totalDuration, 2)
        ]);
    }

    /* =====================================================
       START DANDORI
    ===================================================== */
    public function start(Request $request, $id, $type)
    {
        $job = JobMaster::findOrFail($id);
        
        $activityTypes = [
            'dandori_process' => 'DANDORI',
            'first_check'     => '1ST CHECK'
        ];

        $activityName = $activityTypes[$type] ?? 'DANDORI';
        $jenisDandori = $type === 'first_check' ? '1st_check' : 'dandori';
        $now = now();

        // Close any existing open downtime (e.g. auto-idle time) before starting Dandori
        // For 1st_check, only close non-dandori downtimes
        $openDowntime = Downtime::where('job_master_id', $id)
            ->whereNull('finish_time');
        if ($jenisDandori === '1st_check') {
            $openDowntime->where('jenis_downtime', '!=', 'dandori');
        }
        $openDowntime = $openDowntime->first();
        if ($openDowntime) {
            $durationSeconds = abs($now->diffInSeconds(Carbon::parse($openDowntime->start_time)));
            $openDowntime->update([
                'finish_time' => $now,
                'duration_seconds' => $durationSeconds
            ]);
        }

        if ($jenisDandori !== '1st_check') {
            // Create Downtime record so Input Harian timeline can render it
            Downtime::create([
                'job_master_id' => $id,
                'jenis_downtime' => 'dandori',
                'problem' => 'PERSIAPAN (DANDORI)',
                'start_time' => $now,
                'penyebab' => '-',
                'action' => '-',
                'pic' => auth()->user()->name ?? 'OPERATOR'
            ]);
        }

        Dandori::create([
            'next_job_id'   => $job->id,
            'line'          => $job->line,
            'shift'         => $request->shift ?? 'Shift 1',
            'activity'      => $activityName,
            'jenis_dandori' => $jenisDandori,
            'start_time'    => $now,
            'work_date'     => now()->toDateString(),
            'created_by'    => auth()->id()
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Dandori ' . $activityName . ' dimulai.']);
        }
        return redirect()->back()->with('success', 'Dandori ' . $activityName . ' dimulai.');
    }

    /* =====================================================
       STOP DANDORI
    ===================================================== */
    public function stop($id)
    {
        $row = Dandori::findOrFail($id);
        $finish = now();

        $minutes = Carbon::parse($row->start_time)->diffInSeconds($finish) / 60;

        $row->update([
            'finish_time'      => $finish,
            'duration_minutes' => round($minutes, 2)
        ]);

        // Also close the corresponding Downtime record so Input Harian timeline reflects it
        $openDowntime = Downtime::where('job_master_id', $row->next_job_id)
            ->where('jenis_downtime', 'dandori')
            ->whereNull('finish_time')
            ->first();
        if ($openDowntime) {
            $durationSeconds = abs($finish->diffInSeconds(Carbon::parse($openDowntime->start_time)));
            $openDowntime->update([
                'finish_time' => $finish,
                'duration_seconds' => $durationSeconds
            ]);
        }

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Dandori ' . $row->activity . ' selesai.']);
        }
        return redirect()->back()->with('success', 'Dandori ' . $row->activity . ' selesai.');
    }

    /* =====================================================
       RESTART DANDORI
    ===================================================== */
    public function restart($id)
    {
        $row = Dandori::findOrFail($id);
        $activityName = $row->activity;
        $row->delete();

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Dandori ' . $activityName . ' di-reset.']);
        }
        return redirect()->back()->with('warning', 'Dandori ' . $activityName . ' di-reset.');
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

        if ($request->jenis) {
            $q->where('jenis_dandori', $request->jenis);
        }

        // Group by job, date, line, and shift
        $paginated = $q->leftJoin('job_masters', 'dandoris.next_job_id', '=', 'job_masters.id')
            ->select(
                'dandoris.next_job_id', 
                'dandoris.work_date', 
                'dandoris.line', 
                'dandoris.shift',
                'job_masters.job_number',
                'job_masters.job_name'
            )
            ->groupBy('dandoris.next_job_id', 'dandoris.work_date', 'dandoris.line', 'dandoris.shift', 'job_masters.job_number', 'job_masters.job_name')
            ->orderBy('dandoris.work_date', 'desc')
            ->paginate(10);

        $paginated->getCollection()->transform(function ($group) {
            // Fetch all activities belonging to this group
            $activities = Dandori::where('next_job_id', $group->next_job_id)
                ->whereDate('work_date', $group->work_date)
                ->where('line', $group->line)
                ->where('shift', $group->shift)
                ->orderBy('created_at', 'asc')
                ->get();

            return [
                'job_id'         => $group->next_job_id,
                'job_number'     => $group->job_number ?? 'N/A',
                'job_name'       => $group->job_name ?? 'Master Data tidak ditemukan',
                'line'           => $group->line,
                'shift'          => $group->shift,
                'date'           => Carbon::parse($group->work_date)->format('d M Y'),
                'total_duration' => round($activities->sum('duration_minutes'), 2),
                'activities'     => $activities->map(function ($a) {
                    return [
                        'type'          => $a->activity,
                        'jenis_dandori' => $a->jenis_dandori ?? 'dandori',
                        'start'         => $a->start_time ? Carbon::parse($a->start_time)->format('H:i') : '-',
                        'finish'        => $a->finish_time ? Carbon::parse($a->finish_time)->format('H:i') : '-',
                        'duration'      => round($a->duration_minutes, 2),
                        'is_finished'   => !is_null($a->finish_time)
                    ];
                })
            ];
        });

        return response()->json($paginated);
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
        'DANDORI'
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

    // idle time creation removed — flow langsung Dandori

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