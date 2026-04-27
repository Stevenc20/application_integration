<?php

namespace App\Http\Controllers\Operational;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\JobMaster;
use App\Models\ProductionSession;
use Carbon\Carbon;
use App\Models\DailyProduction;

class InputHarianController extends Controller
{
    public function index(Request $request)
{
    $query = JobMaster::query();

    /*
    ===============================
    FILTER TANGGAL
    ===============================
    */
    if ($request->filled('date')) {
        $query->whereDate('created_at', $request->date);
    }

    /*
    ===============================
    FILTER LINE
    ===============================
    */
    if ($request->filled('line')) {
        $query->where('line', $request->line);
    }

    /*
    ===============================
    FILTER SEARCH
    ===============================
    */
    if ($request->filled('search')) {

        $search = trim($request->search);

        $query->where(function ($q) use ($search) {
            $q->where('job_number', 'like', "%{$search}%")
              ->orWhere('job_name', 'like', "%{$search}%");
        });
    }

   /*
    ===============================
    FILTER STATUS
    ===============================
    */
    $status = trim($request->status ?? '');

    if ($status !== '') {

        // pilih salah satu status
        $query->where('status', $status);

    }

    /*
    ===============================
    SORTING
    ===============================
    */
    $query->orderByRaw("
        FIELD(
            status,
            'running',
            'paused',
            'pending',
            'finished',
            'closed'
        )
    ");

    $query->orderBy('line')
          ->orderBy('sequence_no')
          ->orderBy('job_number');

    /*
    ===============================
    PAGINATION
    ===============================
    */
    $jobs = $query->paginate(10)->withQueryString();

    /*
    ===============================
    DROPDOWN LINE
    ===============================
    */
    $lines = JobMaster::select('line')
        ->whereNotNull('line')
        ->distinct()
        ->orderBy('line')
        ->pluck('line');

    return view('operational.input_harian', compact(
        'jobs',
        'lines'
    ));
}

   public function start($id)
    {
        $session = ProductionSession::firstOrCreate(
            [
                'job_master_id' => $id,
                'work_date' => now()->toDateString()
            ]
        );

        $session->start_time = now();
        $session->status = 'running';
        $session->save();

        JobMaster::where('id', $id)->update([
            'status' => 'running',
            'started_at' => now(),
            'finished_at' => null
        ]);

        return response()->json([
            'success' => true
        ]);
    }

   public function pause($id)
    {
        $session = ProductionSession::where('job_master_id', $id)
            ->whereDate('work_date', now())
            ->first();

        if ($session) {

            $seconds = Carbon::parse($session->start_time)
                ->diffInSeconds(now());

            $session->total_seconds += $seconds;
            $session->pause_time = now();
            $session->status = 'paused';
            $session->save();
        }

        JobMaster::where('id', $id)->update([
            'status' => 'paused'
        ]);

        return response()->json([
            'success' => true
        ]);
    }
    
    public function resume($id)
    {
        $session = ProductionSession::where('job_master_id', $id)
            ->whereDate('work_date', now())
            ->first();

        if ($session) {
            $session->start_time = now();
            $session->status = 'running';
            $session->save();
        }

        JobMaster::where('id', $id)->update([
            'status' => 'running'
        ]);

        return response()->json([
            'success' => true
        ]);
    }

    public function restart($id)
    {
        $session = ProductionSession::where('job_master_id',$id)
            ->whereDate('work_date', now())
            ->first();

        if($session){

            $session->total_seconds = 0;
            $session->start_time = now();
            $session->pause_time = null;
            $session->finish_time = null;
            $session->status = 'running';
            $session->save();
        }

        JobMaster::where('id',$id)->update([
            'status' => 'running',
            'started_at' => now(),
            'finished_at' => null
        ]);

        return response()->json([
            'success'=>true
        ]);
    }

   public function finish($id)
    {
        $session = ProductionSession::where('job_master_id', $id)
            ->whereDate('work_date', now())
            ->first();

        if ($session) {

            if ($session->status == 'running') {

                $seconds = now()->diffInSeconds($session->start_time);

                $session->total_seconds += $seconds;
            }

            $session->status = 'finished';
            $session->finish_time = now();
            $session->save();
        }

        // INI YANG KAMU BELUM ADA
        JobMaster::where('id', $id)->update([
            'status' => 'finished',
            'finished_at' => now()
        ]);

        return response()->json([
            'success' => true
        ]);
    }

   public function status($id)
    {
        $session = ProductionSession::where('job_master_id', $id)
            ->whereDate('work_date', now())
            ->first();

        $job = JobMaster::find($id);

        return response()->json([
            'status'        => $job->status ?? 'pending',
            'total_seconds' => $session->total_seconds ?? 0,
            'start_time'    => $session->start_time ?? null,
        ]);
    }
    
 public function saveQty(Request $request, $id)
    {
        $session = ProductionSession::where('job_master_id', $id)
            ->whereDate('work_date', now()->toDateString())
            ->first();

        $runtime = 0;
        $downtime = 0;

        if ($session) {

            // ambil total waktu sebelumnya
            $runtime = (int) $session->total_seconds;

            // jika masih running, tambahkan waktu realtime sejak start terakhir
            if (
                $session->status === 'running' &&
                !empty($session->start_time)
            ) {
                $runtime += Carbon::parse($session->start_time)
                    ->diffInSeconds(now());
            }

            // optional downtime kalau ada kolomnya
            $downtime = (int) ($session->downtime_seconds ?? 0);
        }

        // ambil data job master untuk target + line
        $job = JobMaster::find($id);

        $targetQty = $job?->capacity ?? 0;
        $actualQty = (int) $request->actual_qty;

        // hitung efficiency %
        $efficiency = 0;

        if ($targetQty > 0) {
            $efficiency = round(($actualQty / $targetQty) * 100, 2);
        }

        DailyProduction::updateOrCreate(
            [
                'job_master_id' => $id,
                'work_date'     => now()->toDateString()
            ],
            [
                'line'              => $job?->line,
                'shift'             => $this->getShift(),
                'target_qty'        => $targetQty,

                'actual_qty'        => $actualQty,
                'reject_qty'        => (int) $request->reject_qty,
                'repair_qty'        => (int) $request->repair_qty,

                'runtime_seconds'   => $runtime,
                'downtime_seconds'  => $downtime,
                'efficiency'        => $efficiency,

                'remarks'           => $request->remarks,
                'saved_by'          => auth()->id(),
                'status'            => 'open',
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil disimpan',
            'runtime_seconds' => $runtime,
            'efficiency' => $efficiency
        ]);
    }

    /**
     * AUTO SHIFT
     */
    private function getShift()
    {
        $hour = now()->format('H');

        if ($hour >= 7 && $hour < 15) {
            return 'Shift 1';
        }

        if ($hour >= 15 && $hour < 23) {
            return 'Shift 2';
        }

        return 'Shift 3';
    }

    public function nextList($id)
    {
        $current = JobMaster::find($id);

        if (!$current) {
            return response()->json([]);
        }

        $jobs = JobMaster::where('line', $current->line)
            ->where('job_number', '>', $current->job_number)
            ->orderBy('job_number')
            ->get(['id','job_number','job_name']);

        return response()->json($jobs);
    }
    
    public function nextProcess(Request $request, $id)
    {
        // job selesai
        JobMaster::where('id', $id)->update([
            'status' => 'finished',
            'finished_at' => now()
        ]);

        $next = null;

        /*
        =============================
        JIKA PILIH MANUAL DROPDOWN
        =============================
        */
        if ($request->filled('next_id')) {

            $next = JobMaster::where('id', $request->next_id)
                ->where('status', 'pending')
                ->first();
        }

        /*
        =============================
        JIKA AUTO / TIDAK PILIH
        =============================
        */
        if (!$next) {

            $current = JobMaster::find($id);

            $next = JobMaster::where('status', 'pending')
                ->where('line', $current->line)
                ->where('id', '!=', $id)
                ->orderBy('sequence_no')
                ->orderBy('id')
                ->first();
        }

        /*
        =============================
        JIKA ADA NEXT JOB
        =============================
        */
        if ($next) {

            $next->update([
                'status' => 'running',
                'started_at' => now()
            ]);

            ProductionSession::updateOrCreate(
                [
                    'job_master_id' => $next->id,
                    'work_date' => now()->toDateString()
                ],
                [
                    'start_time' => now(),
                    'status' => 'running'
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Next process: '.$next->job_number
            ]);
        }

        /*
        =============================
        TIDAK ADA NEXT JOB
        =============================
        */
        return response()->json([
            'success' => true,
            'message' => 'Semua job selesai'
        ]);
    }
}