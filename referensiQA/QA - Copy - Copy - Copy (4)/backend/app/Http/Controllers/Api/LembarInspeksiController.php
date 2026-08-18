<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LembarInspeksi;
use App\Models\Qpr;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LembarInspeksiController extends Controller
{
    // ── GET /api/inspeksi ──
    public function index(Request $request)
    {
        $user  = $request->user();
        $query = LembarInspeksi::with(['creator', 'foreman', 'assignedOperator:id,name', 'itemChecks:id,lembar_inspeksi_id,hasil_visual,hasil_dimensi'])
            ->orderBy('created_at', 'desc');

        // Role-based filtering (Bypass jika request untuk global report)
        $isGlobalReport = $request->query('report') == '1';

        if (!$isGlobalReport) {
            if ($user->role === 'Operator') {
                // Operator can only see completed/approved Master Templates
                $query->whereNotIn('status', ['draft', 'revision', 'waiting_foreman', 'waiting_supervisor']);
            } elseif ($user->role === 'Foreman') {
                // Foreman can see everything except other people's drafts maybe? Or just all non-drafts
                $query->whereNotIn('status', ['draft', 'revision'])->orWhere('created_by', $user->id);
            } elseif ($user->role === 'Supervisor' || $user->role === 'Leader') {
                // Supervisors/Leaders can see everything
            }
        }

        // Filter Arsip
        if ($request->query('archived') == '1') {
            $query->whereNotNull('archived_at');
        } elseif ($request->query('archived') !== 'all') {
            $query->whereNull('archived_at');
        }

        // Filter tanggal (untuk dashboard summary)
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->query('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->query('to'));
        }

        // Admin, Group Leader, Supervisor, dan QA bisa lihat SEMUA data sesuai filter di atas

        // Filter Bulan/Tahun/Tanggal (untuk Summary/LHI agar tidak tarik semua data)
        if ($request->filled('tanggal')) {
            $query->whereDate('tgl_bulan', $request->query('tanggal'));
        } else {
            if ($request->filled('tahun')) {
                $query->whereYear('tgl_bulan', $request->query('tahun'));
            }
            if ($request->filled('bulan')) {
                $query->whereMonth('tgl_bulan', $request->query('bulan'));
            }
        }
        // Filter status (bisa multiple, pisah koma: 'finished,approved')
        if ($request->filled('status')) {
            $statuses = explode(',', $request->query('status'));
            $query->whereIn('status', $statuses);
        }

        return response()->json($query->orderBy('id', 'asc')->get());
    }

    // ── POST /api/inspeksi/{id}/restore ──
    public function restore($id)
    {
        $user = auth()->user();
        if (!in_array($user->role, ['Admin', 'Supervisor'])) {
            return response()->json(['message' => 'Hanya Admin atau Supervisor yang dapat melakukan restore'], 403);
        }

        $item = LembarInspeksi::find($id);
        if (!$item) return response()->json(['message' => 'Data tidak ditemukan'], 404);

        $item->archived_at = null;
        $item->archive_reason = null;
        $item->save();

        return response()->json(['message' => 'Data berhasil dikembalikan dari arsip', 'data' => $item]);
    }

    // ── GET /api/inspeksi/rekap-bulanan ──
    public function rekapBulanan(Request $request)
    {
        $bulan = $request->query('bulan', ''); // kosong = semua bulan
        $tahun = $request->query('tahun', now()->format('Y'));

        // SELARASKAN dengan LI List index:
        // - Tidak pakai withTrashed() → exclude soft-deleted (user-deleted)
        // - whereNull('archived_at') → exclude arsip (sama seperti LI List default)
        // - Tidak filter status → hitung semua (termasuk draft, sesuai LI List)
        $query = \App\Models\ItemCheck::with('masterTemplate')
            ->whereYear('tanggal', $tahun);

        // Hanya filter bulan jika dipilih
        if (!empty($bulan)) {
            $query->whereMonth('tanggal', $bulan);
        }

        $allData = $query->get();

        $totalLi = $allData->count();
        $totalProduksi = $allData->sum('total_produksi');
        $totalOk = $allData->where('judgement', 'OK')->count();
        $totalNg = $allData->where('judgement', 'NG')->count();
        $ngRate = $totalLi > 0 ? round(($totalNg / $totalLi) * 100, 2) : 0;
        
        $totalRepair = $allData->sum('repair');
        $totalReject = $allData->sum('reject');

        // Breakdown per shift
        $perShift = [
            'Shift 1 (Pagi)' => $allData->whereIn('shift', ['1', 'Shift 1', 'Shift Pagi', 'Pagi'])->count(),
            'Shift 2 (Malam)' => $allData->whereIn('shift', ['2', 'Shift 2', 'Shift Malam', 'Malam', 'Shift Sore', 'Sore'])->count(),
        ];

        // Breakdown per part (Top 5)
        $perPartRaw = $allData->groupBy(function($item) {
            return $item->masterTemplate ? $item->masterTemplate->part_name : 'Unknown';
        })->map->count()->sortDesc();
        $topParts = $perPartRaw->take(5);

        // Agregasi Defect (ng_details)
        $defects = [];
        foreach ($allData as $item) {
            if ($item->judgement !== 'NG' || empty($item->ng_details)) continue;
            
            $ng = $item->ng_details;
            if (is_string($ng)) {
                $ng = json_decode($ng, true);
            }
            if (is_array($ng)) {
                // Loop ng_details untuk cari problems
                foreach ($ng as $detail) {
                    if (is_array($detail) && !empty($detail['problems'])) {
                        $probs = is_array($detail['problems']) ? $detail['problems'] : [$detail['problems']];
                        foreach ($probs as $p) {
                            if (!empty($p)) {
                                $defects[$p] = ($defects[$p] ?? 0) + 1;
                            }
                        }
                    } elseif (is_array($detail) && !empty($detail['problem'])) {
                        $p = $detail['problem'];
                        if (!empty($p)) {
                            $defects[$p] = ($defects[$p] ?? 0) + 1;
                        }
                    }
                }
            }
        }
        arsort($defects);
        $topDefects = array_slice($defects, 0, 5, true);

        // Trend Produksi Mingguan (sederhana: pisahkan berdasarkan minggu dalam bulan)
        $trendMingguan = [
            'Minggu 1' => ['OK' => 0, 'NG' => 0],
            'Minggu 2' => ['OK' => 0, 'NG' => 0],
            'Minggu 3' => ['OK' => 0, 'NG' => 0],
            'Minggu 4' => ['OK' => 0, 'NG' => 0],
            'Minggu 5' => ['OK' => 0, 'NG' => 0],
        ];

        foreach ($allData as $item) {
            if (!$item->tanggal) continue;
            $day = \Carbon\Carbon::parse($item->tanggal)->day;
            $week = ceil($day / 7);
            if ($week > 5) $week = 5; // maksimal masuk minggu 5

            $j = $item->judgement;
            if ($j === 'OK' || $j === 'NG') {
                $trendMingguan["Minggu {$week}"][$j]++;
            }
        }

        return response()->json([
            'bulan' => $bulan,
            'tahun' => $tahun,
            'metrik' => [
                'total_li' => $totalLi,
                'total_produksi' => $totalProduksi,
                'total_ok' => $totalOk,
                'total_ng' => $totalNg,
                'ng_rate' => $ngRate,
                'total_repair' => $totalRepair,
                'total_reject' => $totalReject,
            ],
            'per_shift' => $perShift,
            'top_parts' => $topParts,
            'top_defects' => $topDefects,
            'trend_mingguan' => $trendMingguan
        ]);
    }

    // ── GET /api/inspeksi/leaderboard ──
    public function leaderboard(Request $request)
    {
        // Hitung performa setiap Operator QC (role = Operator)
        $operators = User::where('role', 'Operator')->where('is_active', true)->get();
        $stats = [];

        foreach ($operators as $op) {
            $completedJobs = LembarInspeksi::where('assigned_operator_id', $op->id)
                ->whereIn('status', ['finished', 'approved', 'waiting_qc_approval'])
                ->get();
            
            $totalCompleted = $completedJobs->count();
            
            // Hitung kecepatan rata-rata (dalam menit) dari operator_claimed_at sampai qc_signed_at
            $totalMinutes = 0;
            $countWithTime = 0;
            foreach ($completedJobs as $job) {
                if ($job->operator_claimed_at && $job->qc_signed_at) {
                    $claimed = \Carbon\Carbon::parse($job->operator_claimed_at);
                    $signed = \Carbon\Carbon::parse($job->qc_signed_at);
                    $diff = $claimed->diffInMinutes($signed);
                    if ($diff >= 0 && $diff < 600) { // filter anomali ekstrim
                        $totalMinutes += $diff;
                        $countWithTime++;
                    }
                }
            }
            
            $avgSpeed = $countWithTime > 0 ? round($totalMinutes / $countWithTime) : 0;
            
            // Score sederhana: 10 * total_completed - avg_speed (semakin cepat, semakin bagus)
            // (Hanya simulasi gamifikasi sederhana)
            $score = ($totalCompleted * 50) + ($countWithTime > 0 ? max(0, 100 - $avgSpeed) : 0);
            
            $stats[] = [
                'id' => $op->id,
                'name' => $op->name,
                'line' => $op->assigned_line ?? '-',
                'total_completed' => $totalCompleted,
                'avg_speed_minutes' => $avgSpeed,
                'score' => $score
            ];
        }

        // Urutkan berdasarkan skor terbesar
        usort($stats, fn($a, $b) => $b['score'] <=> $a['score']);
        
        // Tambahkan atribut rank dan medal
        $medals = ['🥇', '🥈', '🥉'];
        foreach ($stats as $index => &$stat) {
            $stat['rank'] = $index + 1;
            $stat['medal'] = $index < 3 ? $medals[$index] : '';
        }

        return response()->json($stats);
    }

    // ── GET /api/inspeksi/{id} ──
    public function show($id)
    {
        $item = LembarInspeksi::with([
    'creator', 'foreman', 'qpr',
    'assignedOperator:id,name,role',
    'assignedGl:id,name,role',
    'assignedForeman:id,name,role',
])->findOrFail($id);
        return response()->json($item);
    }

    // ── POST /api/inspeksi ──
    public function store(Request $request)
    {
        $user = $request->user();
        
        // Log data yang masuk untuk debugging 422
        Log::info('LI Store Request Data:', $request->all());

        $validated = $request->validate([
            'job_no'         => 'required|string|max:255',
            'part_name'      => 'required|string|max:255',
            'part_no'        => 'required|string|max:255',
            'type'           => 'nullable|string|max:255',
            'spec_material'  => 'nullable|string|max:255',
            'type_pallet'    => 'nullable|string|max:255',
            'proses_route'   => 'nullable|string|max:255',
            'lokasi'         => 'required|string|max:255',
            'tgl_bulan'      => 'nullable|date',
            'shift'          => 'nullable|string|max:255',
            'total_produksi' => 'nullable|integer',
            'image_path'     => 'required|string',
            'view_package'   => 'nullable|string|max:255',
            'judgement'      => 'nullable|string|max:255',

            'dimensi1'       => 'nullable|string|max:255',
            'dimensi2'       => 'nullable|string|max:255',
            'dimensi3'       => 'nullable|string|max:255',
            'dimensi4'       => 'nullable|string|max:255',
            'dimensi5'       => 'nullable|string|max:255',
            'dimensi6'       => 'nullable|string',
            'dimensi7'       => 'nullable|string',
            'dimensi1_item'  => 'nullable|string|max:255',
            'dimensi2_item'  => 'nullable|string|max:255',
            'dimensi3_item'  => 'nullable|string|max:255',
            'dimensi4_item'  => 'nullable|string|max:255',
            'dimensi5_item'  => 'nullable|string|max:255',
            'dimensi6_item'  => 'nullable|string',
            'dimensi7_item'  => 'nullable|string',
            'dimensi1_method' => 'nullable|string|max:255',
            'dimensi2_method' => 'nullable|string|max:255',
            'dimensi3_method' => 'nullable|string|max:255',
            'dimensi4_method' => 'nullable|string|max:255',
            'dimensi5_method' => 'nullable|string|max:255',
            'dimensi6_method' => 'nullable|string',
            'dimensi7_method' => 'nullable|string',
            'appearance6'    => 'nullable|string',
            'appearance7'    => 'nullable|string',
            'appearance8'    => 'nullable|string',
            'appearance9'    => 'nullable|string',
            'appearance10'   => 'nullable|string',
            'appearance11'   => 'nullable|string',
            'appearance12'   => 'nullable|string',
            'appearance13'   => 'nullable|string',
            'appearance14'   => 'nullable|string',

            'max_sample'     => 'nullable|integer|min:0',
            'tact_time'        => 'nullable|numeric|min:0',
            'ct_dimensi'       => 'nullable|numeric|min:0',
            'ct_tanpa_dimensi' => 'nullable|numeric|min:0',

            // Dimensi hasil ukur
            'dimensi1_sample_1' => 'nullable|string',
            'dimensi1_sample_2' => 'nullable|string',
            'dimensi1_sample_3' => 'nullable|string',
            'dimensi2_sample_1' => 'nullable|string',
            'dimensi2_sample_2' => 'nullable|string',
            'dimensi2_sample_3' => 'nullable|string',
            'dimensi3_sample_1' => 'nullable|string',
            'dimensi3_sample_2' => 'nullable|string',
            'dimensi3_sample_3' => 'nullable|string',
            'dimensi4_sample_1' => 'nullable|string',
            'dimensi4_sample_2' => 'nullable|string',
            'dimensi4_sample_3' => 'nullable|string',
            'dimensi5_sample_1' => 'nullable|string',
            'dimensi5_sample_2' => 'nullable|string',
            'dimensi5_sample_3' => 'nullable|string',
            'dimensi6_sample_1' => 'nullable|string',
            'dimensi6_sample_2' => 'nullable|string',
            'dimensi6_sample_3' => 'nullable|string',
            'dimensi7_sample_1' => 'nullable|string',
            'dimensi7_sample_2' => 'nullable|string',
            'dimensi7_sample_3' => 'nullable|string',

            // Dimensi results JSON
            'dimensi1_results'  => 'nullable|array',
            'dimensi2_results'  => 'nullable|array',
            'dimensi3_results'  => 'nullable|array',
            'dimensi4_results'  => 'nullable|array',
            'dimensi5_results'  => 'nullable|array',
            'dimensi6_results'  => 'nullable|array',
            'dimensi7_results'  => 'nullable|array',

            // Appearance results JSON
            'appearance6_results'  => 'nullable|array',
            'appearance7_results'  => 'nullable|array',
            'appearance8_results'  => 'nullable|array',
            'appearance9_results'  => 'nullable|array',
            'appearance10_results' => 'nullable|array',
            'appearance11_results' => 'nullable|array',
            'appearance12_results' => 'nullable|array',
            'appearance13_results' => 'nullable|array',
            'appearance14_results' => 'nullable|array',

            'ng_details'     => 'nullable|array',
            'coil_numbers'   => 'nullable|array',

            'qg_judgement'   => 'nullable|string|max:10',
            'qg_name'        => 'nullable|string|max:255',
            'tgl_bulan'      => 'nullable|date',
            'shift'          => 'nullable|string|max:50',
            'total_produksi' => 'nullable|integer',
            'repair'         => 'nullable|integer',
            'reject'         => 'nullable|integer',
            'catatan'        => 'nullable|string',
            'prepared_paraf' => 'nullable|string',
            'paraf_gl_cols'  => 'nullable|array',

            'foreman_id'     => 'nullable|integer|exists:users,id',
            'frm_name'       => 'nullable|string|max:255',
            'gl_name'        => 'nullable|string|max:255',
            'status'         => 'nullable|string|max:50',

            'bundle_checks'   => 'nullable|array',
            'bundle_tindakan' => 'nullable|string',
            'paraf_gl_bottom' => 'nullable|string',
            'paraf_foreman_bottom' => 'nullable|string',
            'paraf_gl_bottom_name' => 'nullable|string|max:255',
            'paraf_fm_bottom_name' => 'nullable|string|max:255',
            'assigned_operator_id' => 'nullable|integer|exists:users,id',
            'assigned_gl_id'       => 'nullable|integer|exists:users,id',
            'assigned_foreman_id'  => 'nullable|integer|exists:users,id',
            'revision_records'     => 'nullable|array',
            'field_revisions'      => 'nullable|array',
            'sampling_cols'        => 'nullable|array',
        ]);

        DB::beginTransaction();
        try {
            $validated['created_by'] = $user->id;
            $validated['status']     = $validated['status'] ?? 'draft';

            // Generate no_form — format: LI-YYYY/MM-XXX (per bulan, race-condition safe)
            $tglBulan   = isset($validated['tgl_bulan'])
                ? \Carbon\Carbon::parse($validated['tgl_bulan'])
                : now();

            $count = LembarInspeksi::whereYear('tgl_bulan', $tglBulan->year)
                ->whereMonth('tgl_bulan', $tglBulan->month)
                ->lockForUpdate()   // cegah race condition jika 2 user submit bersamaan
                ->count() + 1;

            $validated['no_form'] = 'LI-' . $tglBulan->format('Y/m') . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);

            // Dan di bagian create, tambahkan default:
            $validated['repair'] = $validated['repair'] ?? 0;
            $validated['reject'] = $validated['reject'] ?? 0;

            if (isset($validated['image_path'])) {
                $validated['image_path'] = $this->_handleImage($validated['image_path']);
            }
            if (isset($validated['prepared_paraf'])) {
                $validated['prepared_paraf'] = $this->_handleImage($validated['prepared_paraf']);
            }
            if (isset($validated['paraf_gl_bottom'])) {
                $validated['paraf_gl_bottom'] = $this->_handleImage($validated['paraf_gl_bottom']);
            }
            if (isset($validated['paraf_foreman_bottom'])) {
                $validated['paraf_foreman_bottom'] = $this->_handleImage($validated['paraf_foreman_bottom']);
            }
            if (array_key_exists('ng_details', $validated)) {
                $validated['ng_details'] = $this->_normalizeNgDetails($validated['ng_details']);
            }

            $item = LembarInspeksi::create($validated);

            // Auto-upsert template standar berdasarkan part_no
            $this->_upsertTemplate($item, $user->id);

            DB::commit();
            return response()->json([
                'message' => 'Lembar Inspeksi berhasil disimpan',
                'data'    => $item->load(['creator', 'foreman']),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('LembarInspeksi Store Error: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal menyimpan', 'error' => $e->getMessage()], 500);
        }
    }

    // ── PUT /api/inspeksi/{id} ──
    public function update(Request $request, $id)
    {
        $item = LembarInspeksi::findOrFail($id);

        DB::beginTransaction();
        try {
            $data = $request->all();

            // Validasi TTD Pak Azriel (Foreman) sebelum ke Supervisor
            if (isset($data['status']) && $data['status'] === 'waiting_supervisor' && empty($data['paraf_gl'])) {
                return response()->json(['message' => 'Wajib Tanda Tangan Foreman sebelum konfirmasi!'], 422);
            }

            // Validasi TTD sebelum status finished (terima field bottom / prepared_paraf juga)
            if (isset($data['status']) && $data['status'] === 'finished') {
                $hasSupervisor = ! empty($data['paraf_foreman'])
                    || ! empty($item->paraf_foreman)
                    || ! empty($data['paraf_foreman_bottom'])
                    || ! empty($item->paraf_foreman_bottom);
                if (! $hasSupervisor) {
                    return response()->json(['message' => 'Wajib Tanda Tangan Foreman/Supervisor sebelum Selesai & Kunci!'], 422);
                }

                $hasQc = ! empty($data['paraf_qc'])
                    || ! empty($data['prepared_paraf'])
                    || ! empty($item->paraf_qc)
                    || ! empty($item->prepared_paraf);
                if (! $hasQc) {
                    return response()->json(['message' => 'Wajib Tanda Tangan Operator QC sebelum Selesai & Kunci!'], 422);
                }

                if (empty($data['paraf_qc']) && ! empty($data['prepared_paraf'])) {
                    $data['paraf_qc'] = $data['prepared_paraf'];
                }
            }

            // Validasi Khusus Totok (Leader) sebelum kirim ke Foreman (waiting_foreman)
            if (isset($data['status']) && ($data['status'] === 'waiting_foreman' || $data['status'] === 'waiting_verification') && ($item->status === 'draft' || $item->status === 'revision')) {
                // 1. Cek minimal 1 standar dimensi harus terisi penuh (item + method)
                // Mendukung dua format:
                // a) dimensi{i}_nominal (angka) + dimensi{i}_method  ← format baru dari DimModal
                // b) dimensi{i} (teks, mis: "Ø15MM +0.5/-0.5") + dimensi{i}_method  ← format lama dari form langsung
                $filledCount = 0;
                for ($i = 1; $i <= 7; $i++) {
                    $nominal  = $data["dimensi{$i}_nominal"] ?? $item->{"dimensi{$i}_nominal"};
                    $itemText = $data["dimensi{$i}"]         ?? $item->{"dimensi{$i}"};
                    $method   = $data["dimensi{$i}_method"]  ?? $item->{"dimensi{$i}_method"};

                    // "ada nominal" = angka > 0, ATAU teks item dimensi diisi
                    $hasNominal = (!empty($nominal) && floatval($nominal) > 0)
                                  || (!empty($itemText) && trim($itemText) !== '' && trim($itemText) !== '?');
                    $hasMethod  = !empty($method) && trim($method) !== '' && trim($method) !== '?';

                    if ($hasNominal && $hasMethod) {
                        $filledCount++;
                    } elseif ($hasNominal || $hasMethod) {
                        return response()->json(['message' => "Item Dimensi #{$i} diisi sebagian. Jika ingin digunakan, wajib isi Item Dimensi dan Metode Pengecekan."], 422);
                    }
                }
                
                if ($filledCount === 0) {
                    return response()->json(['message' => 'Wajib mengisi minimal 1 Standar Dimensi beserta Metode Pengecekannya!'], 422);
                }
                
                // 2. Cek Standar Jumlah Hole (cari dinamis dari appearance6 s/d appearance14)
                $holeCount = 0;
                for ($row = 6; $row <= 14; $row++) {
                    $appVal = $data["appearance{$row}"] ?? $item->{"appearance{$row}"};
                    if (!empty($appVal) && stripos($appVal, 'Jumlah Hole') !== false) {
                        if (preg_match('/\d+/', $appVal, $matches)) {
                            $holeCount = intval($matches[0]);
                        }
                        break;
                    }
                }
                if ($holeCount <= 0) {
                    return response()->json(['message' => 'Wajib diisi: Standar Jumlah Hole (harus lebih dari 0)'], 422);
                }
            }

            // Auto-set assigned_at when any assignment field changes for the first time
            $assignmentFields = ['assigned_foreman_id', 'assigned_gl_id', 'assigned_operator_id'];
            foreach ($assignmentFields as $field) {
                if ($request->has($field) && $request->$field && !$item->$field) {
                    $data['assigned_at'] = now();
                    break;
                }
            }

            // Jika dokumen dikirim ulang dari status revisi ke waiting_foreman, bersihkan sisa TTD lama (untuk handle data yang nyangkut)
            if (($item->status === 'revision' || $item->status === 'draft') && ($request->status === 'waiting_foreman' || $request->status === 'waiting_verification')) {
                $data['paraf_gl'] = null;
                $data['paraf_foreman'] = null;
                $data['paraf_qc'] = null;
                $data['paraf_gl_bottom'] = null;
                $data['paraf_foreman_bottom'] = null;
            }

            if (isset($data['image_path'])) {
                $data['image_path'] = $this->_handleImage($data['image_path'], $item->image_path);
            }
            if (isset($data['prepared_paraf'])) {
                $data['prepared_paraf'] = $this->_handleImage($data['prepared_paraf'], $item->prepared_paraf);
                if (empty($item->qg_name)) {
                    $data['qg_name'] = auth()->user()?->name;
                    $data['prepared_at'] = now();
                }
            }
            
            if (isset($data['paraf_qc'])) {
                $data['paraf_qc'] = $this->_handleImage($data['paraf_qc'], $item->paraf_qc);
                if (empty($item->qc_name)) {
                    $data['qc_name'] = auth()->user()?->name;
                    $data['qc_signed_at'] = now();
                }
            }

            // Auto-claim jika Operator mengisi form dan belum assigned
            if (auth()->user()?->role === 'Operator' && empty($item->assigned_operator_id) && empty($data['assigned_operator_id'])) {
                $data['assigned_operator_id'] = auth()->id();
                $data['operator_claimed_at'] = now();
            }

            if (array_key_exists('ng_details', $data)) {
                $data['ng_details'] = $this->_normalizeNgDetails($data['ng_details']);
            }

            $item->update($data);

            // Auto-upsert template standar (hanya update field standar, jangan overwrite data lain)
            $this->_upsertTemplate($item->fresh(), auth()->id());

            // Trigger QPR generation if status is finished/locked and has NG
            if (in_array($item->status, ['finished', 'locked']) && $item->hasNg() && !$item->qpr_generated) {
                $qpr = $this->_generateQprDraft($item);
                $item->qpr_generated = true;
                $item->qpr_id        = $qpr->id;
                $item->save();
            }

            DB::commit();
            return response()->json([
                'message' => 'Berhasil diupdate',
                'data'    => $item->fresh(['creator', 'foreman']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('LembarInspeksi Update Error: ' . $e->getMessage(), ['id' => $id, 'trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Gagal update', 'error' => $e->getMessage()], 500);
        }
    }

    // ── DELETE /api/inspeksi/{id} ── Admin: semua status; Leader: draft/revisi/awal saja
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $item = LembarInspeksi::findOrFail($id);

        if (! in_array($user->role, ['Admin', 'Leader'], true)) {
            return response()->json(['message' => 'Hanya Admin atau Leader yang dapat menghapus Lembar Inspeksi.'], 403);
        }

        if ($user->role === 'Leader') {
            $allowed = ['draft', 'revision', 'submitted', 'waiting_foreman'];
            if (! in_array($item->status, $allowed, true)) {
                return response()->json([
                    'message' => 'Leader hanya dapat menghapus LI Draft, Revisi, atau yang masih menunggu Foreman.',
                ], 403);
            }
        }

        $item->delete();

        return response()->json(['message' => 'Lembar Inspeksi berhasil dihapus.']);
    }

    /**
     * Auto-upsert template standar dari sebuah LembarInspeksi.
     * Dipanggil setiap kali LI dibuat atau diupdate.
     */
    private function _upsertTemplate(LembarInspeksi $item, int $userId): void
    {
        try {
            if (empty($item->part_no)) return;

            $templateData = [
                'job_no'           => $item->job_no,
                'part_name'        => $item->part_name,
                'type'             => $item->type,
                'spec_material'    => $item->spec_material,
                'sampling_cols'    => $item->sampling_cols,

                'type_pallet'      => $item->type_pallet,
                'image_path'       => $item->image_path,
                'tact_time'        => $item->tact_time,
                'ct_dimensi'       => $item->ct_dimensi,
                'ct_tanpa_dimensi' => $item->ct_tanpa_dimensi,
                'created_by'       => $userId,
            ];

            for ($i = 1; $i <= 7; $i++) {
                $templateData["dimensi{$i}"]        = $item->{"dimensi{$i}"};
                $templateData["dimensi{$i}_item"]   = $item->{"dimensi{$i}_item"};
                $templateData["dimensi{$i}_method"] = $item->{"dimensi{$i}_method"};
            }

            for ($i = 6; $i <= 14; $i++) {
                $templateData["appearance{$i}"] = $item->{"appearance{$i}"};
            }

            \App\Models\LiTemplate::updateOrCreate(
                ['part_no' => $item->part_no],
                $templateData
            );
        } catch (\Exception $e) {
            // Jangan gagalkan LI save hanya karena template upsert gagal
            \Illuminate\Support\Facades\Log::warning('LI Template upsert failed: ' . $e->getMessage());
        }
    }

    // ── GET /api/inspeksi/pending-ttd ── LI yang menunggu TTD user ini
    public function pendingTtd(Request $request)
    {
        $user = $request->user();

        $query = LembarInspeksi::with([
    'creator', 'foreman',
    'assignedOperator:id,name,role',
    'assignedGl:id,name,role',
    'assignedForeman:id,name,role',
])
            ->whereNull('deleted_at');

        if ($user->role === 'Admin') {
            // Admin bisa lihat semua yang statusnya belum final
            $query->whereIn('status', ['draft', 'waiting_foreman', 'waiting_qc_approval']);
        } else {
            $query->where(function($q) use ($user) {
                // 1. Kondisi sebagai Foreman (Checked / Approver)
                if ($user->role === 'Foreman') {
                    $q->orWhere(function($q2) use ($user) {
                        // Dokumen yang secara eksplisit ditujukan ke Foreman ini
                        $q2->whereIn('status', ['waiting_foreman', 'waiting_verification'])
                           ->where(function($q3) use ($user) {
                               $q3->where('foreman_id', $user->id)
                                  ->orWhere('assigned_foreman_id', $user->id)
                                  ->orWhere('frm_name', 'like', '%' . $user->name . '%');
                           });
                    });

                    // Catch-all: dokumen waiting_foreman tanpa assignment -> tampilkan ke SEMUA Foreman
                    $q->orWhere(function($q2) {
                        $q2->where('status', 'waiting_foreman')
                           ->whereNull('assigned_foreman_id')
                           ->whereNull('foreman_id')
                           ->where(function($q3) {
                               $q3->whereNull('frm_name')->orWhere('frm_name', '');
                           });
                    });

                    // QC Verification phase
                    $q->orWhere('status', 'waiting_qc_approval');
                }

                // 2. Kondisi sebagai Checker (Group Leader / Leader)
                if (in_array($user->role, ['Foreman', 'Group Leader', 'GroupLeader', 'Leader'])) {
                    $q->orWhere(function($q2) use ($user) {
                        $q2->whereIn('status', ['draft', 'waiting_foreman', 'waiting_verification', 'waiting_qc_approval'])
                           ->where(function($q3) use ($user) {
                               $q3->where('assigned_gl_id', $user->id)
                                  ->orWhere('gl_name', 'like', '%' . $user->name . '%');
                           });
                    });
                }

                // 3. Kondisi sebagai QC (Operator) 
                // (Dinonaktifkan agar notifikasi lonceng tidak membaca Master Template)
                /* if ($user->role === 'Operator') {
                    $q->orWhere(function($q2) use ($user) {
                        $q2->where('assigned_operator_id', $user->id)
                           ->whereIn('status', ['ready_for_qc', 'locked']);
                    })->orWhere(function($q2) use ($user) {
                        $q2->whereIn('status', ['ready_for_qc', 'locked'])
                           ->whereNull('assigned_operator_id')
                           ->whereNull('operator_claimed_at');
                        if (!empty($user->assigned_line) && $user->assigned_line !== 'Semua Line') {
                            $q2->where('lokasi', 'like', "%{$user->assigned_line}%");
                        }
                    });
                } */

                // 4. Kondisi sebagai Supervisor
                if ($user->role === 'Supervisor') {
                    $q->orWhere('status', 'waiting_supervisor');
                }

                // 5. Jika bukan role di atas, jangan kembalikan data apa-apa (hindari fetch all)
                if (!in_array($user->role, ['Foreman', 'Group Leader', 'GroupLeader', 'Leader', 'Supervisor'])) {
                    $q->whereRaw('1 = 0');
                }
            });
        }

        $items = $query->orderByDesc('created_at')->limit(50)->get();

        return response()->json($items);
    }

    // ── POST /api/inspeksi/{id}/sign ──

    public function sign(Request $request, $id)
    {
        $request->validate([
            'signature' => 'nullable|string',
            'role'      => 'required|in:gl,foreman,prepared,qc,gl_bottom,fm_bottom',
        ]);

        $item = LembarInspeksi::findOrFail($id);
        $user = $request->user();

        DB::beginTransaction();
        try {
            if ($request->role === 'gl') {
                // Pak Azriel (Foreman) harus bisa TTD Checked
                if ($user->role !== 'Foreman' && $user->role !== 'Admin' && $user->role !== 'Supervisor') {
                    return response()->json(['message' => 'Hanya Foreman/Admin yang bisa TTD Checked'], 403);
                }
                $item->paraf_gl     = $request->signature;
                $item->gl_signed_at = now();
                $item->gl_name      = $user->name;
                // Alur baru: GL TTD -> Menunggu Supervisor
                $item->status = 'waiting_supervisor';

            } elseif ($request->role === 'foreman') {
                // Approved (Top) biasanya Novina (Supervisor), tapi kita izinkan Admin/Supervisor
                if ($user->role !== 'Supervisor' && $user->role !== 'Admin' && $user->role !== 'Foreman') {
                    return response()->json(['message' => 'Hanya Supervisor/Foreman/Admin yang bisa Approve'], 403);
                }
                $item->paraf_foreman     = $request->signature;
                $item->foreman_signed_at = now();
                $item->frm_name          = $user->name;
                // Alur baru: Supervisor TTD -> Locked (Siap diisi QC)
                $item->status = 'locked';

            } elseif ($request->role === 'prepared') {
                $item->prepared_paraf = $request->signature;
                $item->prepared_at    = now();
                $item->qg_name        = $user->name;

            } elseif ($request->role === 'qc') {
                $item->paraf_qc     = $request->signature;
                $item->qc_name      = $user->name;
                $item->qc_signed_at = now();
                $item->status       = 'waiting_qc_approval'; 
                $item->qg_judgement = $item->hasNg() ? 'NG' : 'OK';

            } elseif ($request->role === 'gl_bottom') {
                $item->paraf_gl_bottom      = $request->signature;
                $item->paraf_gl_bottom_name = $user->name;

            } elseif ($request->role === 'fm_bottom') {
                $item->paraf_foreman_bottom = $request->signature;
                $item->paraf_fm_bottom_name = $user->name;
                if ($request->signature) {
                    $item->status = 'finished';
                }
            }

            $item->save();

            // Kalau status jadi 'finished' ATAU 'locked' dan ada NG → generate QPR draft otomatis
            // Kita kumpulkan NG di akhir flow (finished)
            $qprGenerated = false;
            if (in_array($item->status, ['finished', 'locked']) && $item->hasNg() && !$item->qpr_generated) {
                $qpr = $this->_generateQprDraft($item);
                $item->qpr_generated = true;
                $item->qpr_id        = $qpr->id;
                $item->save();
                $qprGenerated = true;
            }

            DB::commit();

            return response()->json([
                'message'       => 'TTD berhasil disimpan',
                'status'        => $item->status,
                'qpr_generated' => $qprGenerated,
                'qpr_id'        => $item->qpr_id,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Sign Error: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal TTD', 'error' => $e->getMessage()], 500);
        }
    }

    // ── POST /api/inspeksi/{id}/assign ──
    // Azriel assign Operator saat TTD Checked
    public function assign(Request $request, $id)
    {
        $request->validate([
            'assigned_operator_id' => 'required|integer|exists:users,id',
            'assigned_gl_id'       => 'nullable|integer|exists:users,id',
            'assigned_foreman_id'  => 'nullable|integer|exists:users,id',
        ]);

        $item = LembarInspeksi::findOrFail($id);
        $user = $request->user();

        // Hanya Foreman, Admin, atau Supervisor yang bisa assign
        if (!in_array($user->role, ['Foreman', 'Admin', 'Supervisor'])) {
            return response()->json(['message' => 'Hanya Supervisor, Foreman, atau Admin yang bisa assign Operator'], 403);
        }

        DB::beginTransaction();
        try {
            $item->assigned_operator_id = $request->assigned_operator_id;
            $item->assigned_gl_id       = $request->assigned_gl_id;
            $item->assigned_foreman_id  = $request->assigned_foreman_id;
            $item->assigned_at          = now();
            $item->save();

            DB::commit();
            return response()->json([
                'message' => 'Penugasan berhasil disimpan',
                'data'    => $item->fresh(['assignedOperator', 'assignedGl', 'assignedForeman']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal assign', 'error' => $e->getMessage()], 500);
        }
    }

    // ── POST /api/inspeksi/{id}/reject ──
    public function reject(Request $request, $id)
    {
        $request->validate([
            'catatan' => 'required|string',
            'role'    => 'required|in:gl,foreman,prepared,qc,gl_bottom,fm_bottom',
        ]);

        $item = LembarInspeksi::findOrFail($id);
        $user = $request->user();

        DB::beginTransaction();
        try {
            // Simpan catatan revisi ke field khusus (bukan tabel revision record permanent)
            $item->catatan_revisi = "REVISI: " . $request->catatan . " (Oleh " . $user->name . ")";
            $item->status = 'revision'; 
            
            // Bersihkan semua TTD agar dokumen bisa melewati proses approval dari awal lagi
            $item->paraf_gl = null;
            $item->paraf_foreman = null;
            $item->paraf_qc = null;
            $item->paraf_gl_bottom = null;
            $item->paraf_foreman_bottom = null;

            $item->save();
            DB::commit();

            return response()->json(['message' => 'Dokumen ditolak & dikembalikan untuk revisi']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal reject', 'error' => $e->getMessage()], 500);
        }
    }

    // ── POST /api/inspeksi/{id}/field-revisions ──
    // Foreman (Azriel) mengirim paket catatan revisi (bulk)
    public function saveFieldRevisions(Request $request, $id)
    {
        $request->validate([
            'field_revisions' => 'required|array',
        ]);

        $item = LembarInspeksi::findOrFail($id);
        $user = $request->user();

        if (!in_array($user->role, ['Foreman', 'Admin'])) {
            return response()->json(['message' => 'Hanya Foreman/Admin yang bisa menambahkan field revision'], 403);
        }

        DB::beginTransaction();
        try {
            $existing = $item->field_revisions ?? [];
            $incoming = $request->field_revisions;

            foreach ($incoming as $field => $data) {
                $existing[$field] = [
                    'catatan'  => $data['catatan'] ?? '-',
                    'by'       => $user->name,
                    'at'       => now()->toDateTimeString(),
                    'resolved' => false,
                ];
            }

            $item->field_revisions = $existing;
            $item->status = 'revision';
            
            // Reset TTD jika ada revisi
            $item->paraf_gl = null;
            $item->paraf_foreman = null;
            $item->paraf_qc = null;
            $item->paraf_gl_bottom = null;
            $item->paraf_foreman_bottom = null;

            $item->save();

            DB::commit();
            return response()->json([
                'message'         => 'Semua catatan revisi berhasil dikirim ke Leader',
                'field_revisions' => $item->field_revisions,
                'status'          => $item->status
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal menyimpan field revisions', 'error' => $e->getMessage()], 500);
        }
    }

    // ── POST /api/inspeksi/{id}/resolve-revision ──
    // Leader (Totok) menandai bahwa catatan revisi dari Foreman sudah diperbaiki
    public function resolveFieldRevision(Request $request, $id)
    {
        $request->validate([
            'field'    => 'required|string',
            'resolved' => 'required|boolean',
            'catatan'  => 'nullable|string',
        ]);

        $item = LembarInspeksi::findOrFail($id);
        $user = $request->user();

        // Leader (atau Admin/Foreman) berhak menandai bahwa revisi telah selesai
        if (!in_array($user->role, ['Leader', 'Foreman', 'Admin'])) {
            return response()->json(['message' => 'Hanya Leader/Foreman yang bisa konfirmasi revisi'], 403);
        }

        DB::beginTransaction();
        try {
            $revisions = $item->field_revisions ?? [];
            
            if (isset($revisions[$request->field])) {
                $revisions[$request->field]['resolved']    = $request->resolved;
                $revisions[$request->field]['resolved_at'] = now()->toDateTimeString();
                $revisions[$request->field]['resolved_by'] = $user->name;
                if ($request->catatan) {
                    $revisions[$request->field]['catatan_resolved'] = $request->catatan;
                }
            }

            $item->field_revisions = $revisions;
            $item->status = 'revision';
            $item->save();

            // Cek apakah semua sudah hijau (resolved)
            $allResolved = true;
            foreach ($revisions as $rev) {
                if (empty($rev['resolved'])) {
                    $allResolved = false;
                    break;
                }
            }

            DB::commit();

            return response()->json([
                'message'      => $request->resolved ? 'Revisi dikonfirmasi OK' : 'Revisi masih perlu diperbaiki',
                'all_resolved' => $allResolved,
                'status'       => $item->status,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal konfirmasi revisi', 'error' => $e->getMessage()], 500);
        }
    }

    // ── POST /api/inspeksi/{id}/claim ──
    // Operator ambil tugas sendiri (fallback jika belum di-assign)
    public function claim(Request $request, $id)
    {
        $item = LembarInspeksi::findOrFail($id);
        $user = $request->user();

        // Hanya Operator yang bisa claim
        if ($user->role !== 'Operator' && $user->role !== 'Admin') {
            return response()->json(['message' => 'Hanya Operator yang bisa claim tugas'], 403);
        }

        // Hanya bisa claim LI yang locked atau ready_for_qc
        if (!in_array($item->status, ['locked', 'ready_for_qc'])) {
            return response()->json(['message' => 'Lembar Inspeksi ini belum siap / sudah selesai dan tidak bisa di-claim'], 422);
        }

        // Cek apakah sudah di-claim orang lain
        if ($item->operator_claimed_at && $item->assigned_operator_id && $item->assigned_operator_id !== $user->id) {
            return response()->json(['message' => 'Mohon maaf, Lembar Inspeksi ini baru saja diambil oleh Operator lain'], 409);
        }

        // Validasi Anti-Overload: Maksimal 1 tugas aktif
        $activeJob = LembarInspeksi::where('assigned_operator_id', $user->id)
            ->whereIn('status', ['locked', 'ready_for_qc'])
            ->first();

        if ($activeJob && $activeJob->id !== $item->id) {
            return response()->json([
                'message' => 'Anda masih memiliki tugas aktif ('. $activeJob->no_form .'). Selesaikan terlebih dahulu sebelum mengambil tugas baru!'
            ], 403);
        }

        DB::beginTransaction();
        try {
            $item->assigned_operator_id = $user->id;
            $item->operator_claimed_at  = now();
            if (!$item->assigned_at) {
                $item->assigned_at = now();
            }
            $item->save();

            DB::commit();
            return response()->json([
                'message' => 'Berhasil mengambil tugas Item Check',
                'data'    => $item->fresh(['assignedOperator']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal claim', 'error' => $e->getMessage()], 500);
        }
    }


    // ── POST /api/inspeksi/{id}/sign-column ──
    public function signColumn(Request $request, $id)
    {
        $request->validate([
            'sample_index' => 'required|integer',
            'signature'    => 'required|string',
        ]);

        $item = LembarInspeksi::findOrFail($id);
        $user = $request->user();

        // Permission: Hanya GL yang ditugaskan yang bisa paraf per kolom
        if ($item->gl_name !== $user->name && $item->created_by !== $user->id) {
            return response()->json(['message' => 'Hanya Group Leader yang ditugaskan yang bisa paraf per kolom'], 403);
        }

        DB::beginTransaction();
        try {
            $cols = $item->paraf_gl_cols ?? [];
            $cols[$request->sample_index] = $request->signature;
            
            $item->paraf_gl_cols = $cols;
            $item->save();

            DB::commit();
            return response()->json([
                'message' => 'Paraf kolom berhasil disimpan',
                'data'    => $item->paraf_gl_cols
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal paraf kolom', 'error' => $e->getMessage()], 500);
        }
    }

    // ── POST /api/inspeksi/{id}/generate-qpr ──
    // Manual trigger (kalau mau generate ulang)
    public function generateQpr($id)
    {
        $item = LembarInspeksi::findOrFail($id);

        if (!$item->hasNg()) {
            return response()->json(['message' => 'Tidak ada item NG — QPR tidak perlu dibuat'], 422);
        }

        DB::beginTransaction();
        try {
            $qpr = $this->_generateQprDraft($item);
            $item->qpr_generated = true;
            $item->qpr_id        = $qpr->id;
            $item->save();

            DB::commit();
            return response()->json([
                'message' => 'QPR draft berhasil dibuat',
                'qpr_id'  => $qpr->id,
                'qpr'     => $qpr,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal generate QPR', 'error' => $e->getMessage()], 500);
        }
    }

    public function uploadSketch(Request $request)
    {
        $request->validate(['file' => 'required|image|max:5120']);
        $path = $request->file('file')->store('inspeksi-sketches', 'public');
        // Return relative path instead of full asset URL to avoid port/domain mismatch
        return response()->json(['url' => 'storage/' . $path]);
    }

    public function search(Request $request)
    {
        $q = $request->query('q');
        if (!$q) return response()->json([]);

        // Cari data LI terakhir berdasarkan Job No atau Part Name untuk autofill
        $items = LembarInspeksi::where('job_no', 'like', "%$q%")
            ->orWhere('part_name', 'like', "%$q%")
            ->orWhere('part_no', 'like', "%$q%")
            ->orderBy('created_at', 'desc')
            ->limit(15)
            ->get(['job_no', 'part_name', 'part_no', 'type', 'created_at', 'image_path', 'shift', 'lokasi', 'created_by']);

        return response()->json($items);
    }

    /** Normalisasi ng_details: keyed object {"6_1":{...}}, bukan [] kosong */
    private function _normalizeNgDetails(mixed $raw): array
    {
        if ($raw === null || $raw === '') {
            return [];
        }
        if (! is_array($raw)) {
            return [];
        }
        if ($raw === []) {
            return [];
        }
        // Format lama: list [{row, sample, problem, penyebab, ...}]
        if (array_is_list($raw)) {
            $out = [];
            foreach ($raw as $i => $item) {
                if (! is_array($item)) {
                    continue;
                }
                $key = (isset($item['row'], $item['sample']))
                    ? "{$item['row']}_{$item['sample']}"
                    : (string) $i;
                $out[$key] = [
                    'proses'     => $item['proses'] ?? '',
                    'problems'   => $item['problems'] ?? $item['problem'] ?? [],
                    'causes'     => array_map('strtolower', $item['causes'] ?? $item['penyebab'] ?? []),
                    'catatan'    => $item['catatan'] ?? '',
                    'disposisi'  => $item['disposisi'] ?? 'repair',
                ];
            }
            return $out;
        }
        // Format keyed: {"2_1": {...}}
        $out = [];
        foreach ($raw as $key => $item) {
            if (! is_array($item)) {
                continue;
            }
            $out[(string) $key] = [
                'proses'    => $item['proses'] ?? '',
                'problems'  => $item['problems'] ?? $item['problem'] ?? [],
                'causes'    => array_map('strtolower', $item['causes'] ?? $item['penyebab'] ?? []),
                'catatan'   => $item['catatan'] ?? '',
                'disposisi' => $item['disposisi'] ?? 'repair',
            ];
        }
        return $out;
    }

    private function _generateQprDraft(LembarInspeksi $item): Qpr
    {
        $ngItems = $item->getNgItems();

        // Kumpulkan defect dari problem NG
        $defects = [];
        $defectKet = [];
        $prosesRepair = [];
        $jamTerakhir = null;
        $causes = ['man' => false, 'method' => false, 'machine' => false, 'material' => false, 'environment' => false];

        foreach ($ngItems as $ng) {
            foreach ($ng['problem'] ?? [] as $p) {
                if (!in_array($p, $defects)) $defects[] = $p;
            }
            foreach ($ng['penyebab'] ?? [] as $c) {
                $cLower = strtolower($c);
                if (str_contains($cLower, 'man') || str_contains($cLower, 'orang')) $causes['man'] = true;
                if (str_contains($cLower, 'method') || str_contains($cLower, 'metode')) $causes['method'] = true;
                if (str_contains($cLower, 'machine') || str_contains($cLower, 'mesin') || str_contains($cLower, 'die') || str_contains($cLower, 'msn')) $causes['machine'] = true;
                if (str_contains($cLower, 'material') || str_contains($cLower, 'mtr')) $causes['material'] = true;
                if (str_contains($cLower, 'environment') || str_contains($cLower, 'lingkungan')) $causes['environment'] = true;
            }
            // Extract proses repair
            $prs = $ng['proses'] ?? [];
            if (is_string($prs)) $prs = [$prs];
            foreach ($prs as $pr) {
                if ($pr && !in_array($pr, $prosesRepair)) $prosesRepair[] = $pr;
            }
            // Keterangan: "Pcs {sample}: {standar} = {val}"
            $defectKet[] = "Pcs {$ng['sample']}: {$ng['standar']} = {$ng['val']}";
            
            // Extract jam
            if (!empty($ng['jam'])) {
                $jamTerakhir = $ng['jam'];
            }
        }

        // Buat approval_signatures awal — operator pemilik QPR ini
        $operatorName = '';
        if ($item->assigned_operator_id) {
            $op = \App\Models\User::find($item->assigned_operator_id);
            $operatorName = $op?->name ?? '';
        } elseif ($item->qc_name) {
            $operatorName = $item->qc_name;
        }

        $signatures = [
            [
                'id'        => 1,
                'position'  => 'operator',
                'role'      => 'Dibuat',
                'sub'       => 'Operator',
                'nama'      => $operatorName,
                'signature' => null,
                'signed_at' => null,
                'required'  => true,
            ],
            [
                'id'        => 2,
                'position'  => 'foreman',
                'role'      => 'Diperiksa',
                'sub'       => 'GL / Foreman',
                'nama'      => $item->gl_name ?? '',
                'signature' => null,
                'signed_at' => null,
                'required'  => true,
            ],
        ];

        $qprSketches = [];
        if ($item->image_path) {
            $qprSketches[] = $item->image_path;
        }
        foreach ($ngItems as $ng) {
            if (!empty($ng['photo']) && !in_array($ng['photo'], $qprSketches)) {
                $qprSketches[] = $ng['photo'];
            }
        }

        $qpr = Qpr::create([
            'no_job'              => $item->job_no,
            'nama_part'           => $item->part_name,
            'lokasi'              => $item->lokasi,
            'shift'               => $item->shift,
            'tanggal'             => $item->tgl_bulan ?? now()->toDateString(),
            'jam'                 => $jamTerakhir ?? now()->format('H:i'),
            'defect'              => implode(', ', $defects),
            'defect_keterangan'   => implode("\n", $defectKet),
            'analisa_man'         => $causes['man'],
            'analisa_method'      => $causes['method'],
            'analisa_machine'     => $causes['machine'],
            'analisa_material'    => $causes['material'],
            'analisa_environment' => $causes['environment'],
            'reject_qty'          => $item->reject,
            'rework_qty'          => $item->repair,
            'proses_repair'       => implode(', ', $prosesRepair),
            'rencana_produksi'    => now()->addDay()->toDateString(),
            'sketches'            => !empty($qprSketches) ? json_encode(array_values($qprSketches)) : null,
            'status'              => 'Draft',
            'source'              => 'inspeksi',
            'inspeksi_id'         => $item->id,
            'assigned_foreman_id' => $item->foreman_id,
            'approval_signatures' => json_encode($signatures),
            // Operator yang sign 'qc' adalah yang bertanggung jawab mengisi QPR
            'created_by'          => $item->assigned_operator_id ?? $item->created_by,
        ]);

        return $qpr;
    }

    /**
     * Handle base64 image storage
     */
    private function _handleImage($base64Data, $existingPath = null)
    {
        if (!$base64Data || !str_starts_with($base64Data, 'data:image')) {
            if (is_string($base64Data) && str_contains($base64Data, 'storage/')) {
                $parts = explode('storage/', $base64Data);
                return '/storage/' . end($parts);
            }
            return $base64Data;
        }

        try {
            // Delete old file if exists
            if ($existingPath && \Storage::disk('public')->exists($existingPath)) {
                \Storage::disk('public')->delete($existingPath);
            }

            $format = explode('/', explode(':', substr($base64Data, 0, strpos($base64Data, ';')))[1])[1];
            if (str_contains($format, 'svg')) {
                $format = 'svg';
            }
            $image = str_replace(' ', '+', explode(',', $base64Data)[1]);
            $fileName = 'li_' . time() . '_' . uniqid() . '.' . $format;
            $path = 'li/' . $fileName;

            \Storage::disk('public')->put($path, base64_decode($image));
            return '/storage/' . $path;
        } catch (\Exception $e) {
            \Log::error('Image Storage Error: ' . $e->getMessage());
            return $base64Data;
        }
    }

    /**
     * Ekspansi singkatan appearance dari Excel.
     * "t/neck"        → "Tidak Neck"
     * "flange t/miring" → "Flange Tidak Miring"
     * Mendukung t/ di MANA SAJA dalam token (bukan hanya di awal).
     * Teks tanpa pola t/ dibiarkan apa adanya (misal: "Marking XR harus jelas / nyata").
     */
    private function expandAppearanceText(string $text): string
    {
        // Pecah berdasarkan koma atau titik koma
        $parts = preg_split('/[,;]+/', $text);
        $expanded = [];

        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part)) continue;

            // Cek apakah ada pola t/xxx di MANA SAJA dalam token
            if (preg_match('/\bt\/\s*\S+/i', $part)) {
                // Ganti setiap kemunculan t/xxx → Tidak Xxx
                $result = preg_replace_callback('/\bt\/\s*(\S+)/i', function ($m) {
                    $word = mb_convert_case(trim($m[1]), MB_CASE_TITLE, 'UTF-8');
                    return 'Tidak ' . $word;
                }, $part);
                // Title-case seluruh token (misal: "flange Tidak Miring" → "Flange Tidak Miring")
                $result = mb_convert_case($result, MB_CASE_TITLE, 'UTF-8');
                $expanded[] = $result;
            } else {
                // Tidak ada t/ → biarkan apa adanya
                $expanded[] = $part;
            }
        }

        return implode(', ', $expanded);
    }

    private function _parsePartNames($inputStr)
    {
        $suffixes = ['FNS', 'WIP', 'BLK', 'GAL', 'ASSY'];
        $foundSuffix = '';
        
        foreach ($suffixes as $s) {
            if (preg_match("/\s+{$s}$/i", $inputStr)) {
                $foundSuffix = " " . strtoupper($s);
                $inputStr = preg_replace("/\s+{$s}$/i", '', $inputStr);
                break;
            }
        }

        if (!str_contains($inputStr, '/')) {
            return [$inputStr . $foundSuffix];
        }

        $parts = explode('/', $inputStr);
        $results = [];
        $base = trim($parts[0]);
        $results[] = $base . $foundSuffix;

        for ($i = 1; $i < count($parts); $i++) {
            $p = trim($parts[$i]);
            $len = strlen($p);
            $prefix = substr($base, 0, -$len);
            $results[] = $prefix . $p . $foundSuffix;
        }
        return $results;
    }

    // ── POST /api/inspeksi/import-excel ──
    public function importExcel(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:51200'
        ]);

        $file = $request->file('file');
        
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getPathname());
            $sheets = $spreadsheet->getAllSheets();
            
            $importedCount = 0;
            $errors = [];

            // Ambil TTD terakhir yang ada di database sebagai default
            $defaultGlSignature = \App\Models\LembarInspeksi::whereNotNull('paraf_gl')->orderBy('id', 'desc')->value('paraf_gl');
            $defaultForemanSignature = \App\Models\LembarInspeksi::whereNotNull('paraf_foreman')->orderBy('id', 'desc')->value('paraf_foreman');
            $defaultPreparedSignature = \App\Models\LembarInspeksi::whereNotNull('prepared_paraf')->orderBy('id', 'desc')->value('prepared_paraf');

            foreach ($sheets as $index => $sheet) {
                // Helper untuk mencari nilai di row tertentu dari kolom M ke Z
                $findValueInRow = function($rowNum) use ($sheet) {
                    foreach (range('L', 'Z') as $col) {
                        $val = $sheet->getCell($col . $rowNum)->getCalculatedValue() ?? $sheet->getCell($col . $rowNum)->getValue();
                        $val = trim((string)$val);
                        // Abaikan jika kosong atau berisi teks label seperti "JOB NO", "PART NAME", dll
                        if (!empty($val) && !preg_match('/^(JOB|PART|TYPE|MODEL)/i', $val) && $val !== ':') {
                            return $val;
                        }
                    }
                    return null;
                };

                $jobNo = $findValueInRow(3);
                $partName = $findValueInRow(4);
                $partNo = $findValueInRow(5);
                $type = $findValueInRow(6);

                // Jika tidak ada part_name atau part_no, maka bukan sheet yang valid, skip
                if (!$partName || !$partNo) continue;

                // Cari Lokasi (Normalisasi agar pas dengan select option: "PRESS A", "PRESS B", dst)
                $lokasi = 'PRESS A';
                foreach (range('A', 'Z') as $col) {
                    $val = $sheet->getCell($col . '2')->getCalculatedValue() ?? $sheet->getCell($col . '2')->getValue();
                    $valStr = (string)$val;
                    if ($valStr && preg_match('/PRESS|LINE/i', $valStr)) {
                        // Cari pola huruf (A, B, C, D) setelah kata PRESS atau LINE
                        if (preg_match('/(?:PRESS|LINE)\s*[-:]*\s*([A-Z0-9])/i', $valStr, $m)) {
                            $lokasi = 'PRESS ' . strtoupper($m[1]);
                        }
                        break;
                    }
                }

                // Ambil nilai dari sel
                $data = [
                    'no_form' => 'MASTER ' . $lokasi, // Tambahkan ini agar tidak kosong di UI
                    'job_no' => (string) $jobNo,
                    'part_name' => (string) $partName,
                    'part_no' => (string) $partNo,
                    'type' => (string) $type,
                    'lokasi' => $lokasi, 
                    'spec_material' => (string) ($sheet->getCell('W11')->getCalculatedValue() ?? ''),
                    'image_path' => null,
                    
                    // Inject Default Signatures
                    'paraf_gl' => $defaultGlSignature,
                    'gl_name'  => 'Azriel M (Auto)',
                    'gl_signed_at' => now(),
                    
                    'paraf_foreman' => $defaultForemanSignature,
                    'frm_name' => 'Novina (Auto)',
                    'foreman_signed_at' => now(),
                    
                    'prepared_paraf' => $defaultPreparedSignature,
                    'qg_name' => 'Totok A (Auto)',
                    'prepared_at' => now(),
                ];

                // Pre-fill Dimensi & Appearance with nulls to clear old data during updateOrCreate
                for ($i = 1; $i <= 7; $i++) {
                    $data["dimensi{$i}_item"] = null;
                    $data["dimensi{$i}_method"] = null;
                }
                for ($i = 6; $i <= 14; $i++) {
                    $data["appearance{$i}"] = null;
                }

                // Ekstrak gambar (Sketch Part) dari sheet ini
                // Jangan ambil sembarang gambar pertama (karena bisa jadi itu Logo IPPI di pojok atas)
                // Kita cari gambar dengan ukuran (Width x Height) paling besar!
                $largestArea = 0;
                $bestImagePath = null;

                foreach ($sheet->getDrawingCollection() as $drawing) {
                    $imageContents = null;
                    $extension = 'png';
                    
                    // Cek koordinat penempatan gambar di Excel (misal: 'A1', 'B5')
                    $coordinates = $drawing->getCoordinates();
                    $rowNumber = (int) preg_replace('/[^0-9]/', '', $coordinates);
                    
                    // Abaikan gambar yang berada di baris 1 sampai 7 (Area Kop Surat / Logo Perusahaan)
                    if ($rowNumber <= 7) {
                        continue;
                    }

                    $width = $drawing->getWidth();
                    $height = $drawing->getHeight();
                    $area = $width * $height;

                    if ($drawing instanceof \PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing) {
                        ob_start();
                        call_user_func(
                            $drawing->getRenderingFunction(),
                            $drawing->getImageResource()
                        );
                        $imageContents = ob_get_contents();
                        ob_end_clean();
                        $extension = strtolower(explode('/', $drawing->getMimeType())[1]);
                    } elseif ($drawing instanceof \PhpOffice\PhpSpreadsheet\Worksheet\Drawing) {
                        $zipReader = fopen($drawing->getPath(), 'r');
                        $imageContents = '';
                        while (!feof($zipReader)) {
                            $imageContents .= fread($zipReader, 1024);
                        }
                        fclose($zipReader);
                        $extension = $drawing->getExtension();
                    }

                    if ($imageContents && $area > $largestArea) {
                        $largestArea = $area;
                        $fileName = 'sketch_' . time() . '_' . uniqid() . '.' . $extension;
                        $path = 'inspeksi-sketches/' . $fileName;
                        \Storage::disk('public')->put($path, $imageContents);
                        
                        // Hapus gambar bestImagePath yang sebelumnya jika ada (biar hemat storage)
                        if ($bestImagePath) {
                            \Storage::disk('public')->delete(str_replace('/storage/', '', $bestImagePath));
                        }
                        
                        $bestImagePath = '/storage/' . $path;
                    }
                }

                if ($bestImagePath) {
                    $data['image_path'] = $bestImagePath;
                }

                // Ekstrak baris (horizontal scanning) untuk Standard & Method
                $extractRowData = function($rowNum) use ($sheet) {
                    $strings = [];
                    // Kita perluas jangkauan scan dari T sampai AG untuk mencegah data terlewat
                    $cols = ['T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG'];
                    foreach ($cols as $col) {
                        $val = $sheet->getCell($col . $rowNum)->getCalculatedValue() ?? $sheet->getCell($col . $rowNum)->getValue();
                        $val = trim((string)$val);
                        if (!empty($val)) {
                            // Abaikan nomor urut (1, 2, 3...) atau karakter aneh yang sendirian
                            if (is_numeric($val) && strlen($val) <= 2) continue;
                            if (strlen($val) === 1 && !preg_match('/[A-Za-z]/', $val)) continue; // Abaikan simbol aneh
                            
                            $strings[] = $val;
                        }
                    }
                    return $strings;
                };

                // Cari batas baris DIMENSI dan APPEARANCE secara dinamis!
                $dimensiStart = 8;
                $appearanceStart = 16; // default fallback
                for ($r = 6; $r <= 35; $r++) {
                    foreach (['Q', 'R', 'S', 'T', 'U', 'V'] as $c) {
                        $txt = trim(strtoupper((string)($sheet->getCell($c . $r)->getCalculatedValue() ?? $sheet->getCell($c . $r)->getValue())));
                        if ($txt === 'DIMENSI') $dimensiStart = $r;
                        if ($txt === 'APPEARANCE' || $txt === 'APPEREANCE') $appearanceStart = $r;
                    }
                }

                // Ekstrak custom sampling columns dengan mencari baris yang memiliki deretan angka terbanyak (biasanya baris No. SAMPLE)
                $samplingCols = [];
                $maxNumericCount = 0;
                
                for ($r = 5; $r <= 45; $r++) {
                    $tempCols = [];
                    // Mulai dari 'A' agar tidak melewatkan angka awal (seperti 1, 32, 60 dll)
                    for ($colChar = 'A'; $colChar !== 'BZ'; $colChar++) {
                        $val = $sheet->getCell($colChar . $r)->getCalculatedValue() ?? $sheet->getCell($colChar . $r)->getValue();
                        $val = trim((string)$val);
                        if (is_numeric($val) && intval($val) > 0) {
                            $tempCols[] = intval($val);
                        }
                    }
                    if (count($tempCols) > $maxNumericCount) {
                        $maxNumericCount = count($tempCols);
                        $samplingCols = $tempCols;
                    }
                }
                
                $samplingCols = array_unique($samplingCols);
                sort($samplingCols);
                $data['sampling_cols'] = !empty($samplingCols) ? $samplingCols : null;
                
                // --- PROSES EKSTRAKSI CT / TACT TIME DARI EXCEL ---
                $tactTime = 0;
                $ctDimensi = 0;
                $ctVisual = 0;
                
                for ($r = 1; $r <= 80; $r++) {
                    // Cek kolom dari AN, AO, AP atau sekitarnya
                    for ($colChar = 'A'; $colChar !== 'BZ'; $colChar++) {
                        $cell = $sheet->getCell($colChar . $r);
                        $val = trim((string)($cell->getCalculatedValue() ?? $cell->getValue()));
                        
                        if (empty($val)) continue;

                        // Cari label "Dalam Detik"
                        if (stripos($val, 'Dalam Detik') !== false) {
                            // Sesuai petunjuk user, data selalu ada di kolom AO dan AP pada baris ini
                            $valAO = trim((string)($sheet->getCell('AO' . $r)->getCalculatedValue() ?? $sheet->getCell('AO' . $r)->getValue()));
                            $valAP = trim((string)($sheet->getCell('AP' . $r)->getCalculatedValue() ?? $sheet->getCell('AP' . $r)->getValue()));
                            
                            if (is_numeric($valAO)) $ctDimensi = (int) $valAO;
                            if (is_numeric($valAP)) $ctVisual = (int) $valAP;
                        }

                        // Cari label "TT per pcs"
                        if (stripos($val, 'TT per pcs') !== false) {
                            // Ekstrak angka desimal dari teks "TT per pcs 6,5 Detik"
                            if (preg_match('/(\d+[\.,]?\d*)/', $val, $m)) {
                                $tactTime = (float) str_replace(',', '.', $m[1]);
                            }
                        }
                    }
                }
                
                if ($tactTime > 0) $data['tact_time'] = $tactTime;
                if ($ctDimensi > 0) $data['ct_dimensi'] = $ctDimensi;
                if ($ctVisual > 0) $data['ct_tanpa_dimensi'] = $ctVisual;



                $specMaterial = '';
                $typePallet = '';
                
                // --- PROSES DIMENSI & SPEC MATERIAL ---
                $dimensiIndex = 1;
                $holeItemText = null;

                // Scan dari awal tabel Dimensi sampai tepat sebelum tabel Appearance
                for ($row = $dimensiStart; $row < $appearanceStart; $row++) {
                    $rowData = $extractRowData($row);
                    if (empty($rowData)) continue;

                    $itemText = (string) $rowData[0];
                    
                    // Cek apakah ini baris Spec Material
                    if (stripos($itemText, 'Spec material') !== false) {
                        $specMaterial = trim(preg_replace('/Spec material\s*[:-]*\s*/i', '', $itemText));
                        continue; 
                    }

                    // Mencegah judul tabel tertangkap
                    if (strtoupper($itemText) === 'STANDARD') continue;

                    // Pindahkan HOLE secara otomatis ke Appearance jika terdeteksi di Dimensi Excel
                    if (stripos($itemText, 'HOLE') !== false) {
                        $holeItemText = $itemText;
                        continue; // Skip jangan masukkan ke Dimensi
                    }

                    // Masukkan ke tabel Dimensi
                    $data['dimensi' . $dimensiIndex . '_item'] = $itemText;
                    $method = isset($rowData[1]) ? $rowData[1] : '';
                    $data['dimensi' . $dimensiIndex . '_method'] = trim(str_replace(['(', ')', 'Check by '], '', $method));

                    // Parse nominal dan toleransi otomatis (contoh: "Ø 10 mm ±0,1" atau "10 +0.1/-0.2")
                    $nominal = null;
                    $plus = null;
                    $minus = null;

                    // Regex 1: Format ± (misal: Ø 10 mm ±0,1 atau 10±0.1)
                    if (preg_match('/(?:Ø|R)?\s*(\d+[\.,]?\d*)\s*(?:mm)?\s*(?:±|v)\s*(\d+[\.,]?\d*)/iu', $itemText, $m)) {
                        $nominal = str_replace(',', '.', $m[1]);
                        $tol = str_replace(',', '.', $m[2]);
                        $plus = $tol;
                        $minus = $tol;
                    } 
                    // Regex 2: Format + / - (misal: Ø 10 mm +0.1/-0.2)
                    elseif (preg_match('/(?:Ø|R)?\s*(\d+[\.,]?\d*)\s*(?:mm)?\s*\+\s*(\d+[\.,]?\d*)\s*\/\s*-\s*(\d+[\.,]?\d*)/iu', $itemText, $m)) {
                        $nominal = str_replace(',', '.', $m[1]);
                        $plus = str_replace(',', '.', $m[2]);
                        $minus = str_replace(',', '.', $m[3]);
                    }
                    // Regex 3: Hanya nominal (misal: Ø 10 mm)
                    elseif (preg_match('/(?:Ø|R)?\s*(\d+[\.,]?\d*)\s*(?:mm)/iu', $itemText, $m)) {
                        $nominal = str_replace(',', '.', $m[1]);
                        $plus = 0;
                        $minus = 0;
                    }

                    if ($nominal !== null) {
                        $data['dimensi' . $dimensiIndex . '_nominal'] = $nominal;
                        $data['dimensi' . $dimensiIndex . '_plus'] = $plus;
                        $data['dimensi' . $dimensiIndex . '_minus'] = $minus;
                    }

                    $dimensiIndex++;
                    
                    if ($dimensiIndex > 7) break;
                }
                $data['spec_material'] = $specMaterial;
                $data['type_pallet'] = ''; 

                // --- PROSES APPEARANCE & TYPE PALLET ---
                $appIndex = 6;

                // Masukkan Hole ke urutan pertama Appearance (appearance6 / No 8)
                if (!empty($holeItemText)) {
                    $data['appearance' . $appIndex] = $this->expandAppearanceText($holeItemText);
                    $appIndex++;
                }

                // Scan area Appearance ke bawah
                for ($row = $appearanceStart; $row <= $appearanceStart + 15; $row++) {
                    $rowData = $extractRowData($row);
                    if (empty($rowData)) continue;

                    $itemText = (string) $rowData[0];

                    // Jika ketemu tabel Revision Record (Date, Rev, dll) atau pure angka tanggal, STOP scan Appearance
                    $lowerText = strtolower(trim($itemText));
                    if ($lowerText === 'date' || str_starts_with($lowerText, 'rev') || is_numeric(trim($itemText))) {
                        break;
                    }
                    
                    // Cek apakah ini baris Type Pallet
                    if (stripos($itemText, 'Type pallet') !== false) {
                        $typePallet = trim(preg_replace('/Type pallet\s*[:-]*\s*/i', '', $itemText));
                        $data['type_pallet'] = $typePallet;
                        continue; 
                    }

                    // Masukkan ke tabel Appearance (ekspansi singkatan t/xxx → Tidak Xxx)
                    $data['appearance' . $appIndex] = $this->expandAppearanceText($itemText);
                    $appIndex++;
                    
                    if ($appIndex > 14) break;
                }

                // Simpan ke Master Template (LembarInspeksi)
                $parsedPartNos = $this->_parsePartNames($data['part_no']);
                foreach ($parsedPartNos as $idx => $parsedPartNo) {
                    $currentData = $data;
                    $currentData['part_no'] = $parsedPartNo;
                    
                    try {
                        $currentData['created_by'] = auth()->id();
                        $currentData['status'] = 'locked'; // Jadikan template yang siap pakai
                        
                        // Cek apakah template dengan job_no dan part_no ini sudah ada dan sedang dipakai
                        $existingTemplate = \App\Models\LembarInspeksi::where('job_no', $currentData['job_no'])
                                                                      ->where('part_no', $currentData['part_no'])
                                                                      ->where('status', 'locked')
                                                                      ->first();
                        
                        if ($existingTemplate && \App\Models\ItemCheck::where('lembar_inspeksi_id', $existingTemplate->id)->exists()) {
                            // Jika sudah dipakai oleh Item Check, jangan overwrite! 
                            $existingTemplate->status = 'archived_template';
                            $existingTemplate->save();
                            $existingTemplate->delete();
                            
                            \App\Models\LembarInspeksi::create($currentData);
                        } else {
                            // Jika belum dipakai, aman untuk di-overwrite
                            \App\Models\LembarInspeksi::updateOrCreate(
                                ['job_no' => $currentData['job_no'], 'part_no' => $currentData['part_no'], 'status' => 'locked'],
                                $currentData
                            );
                        }
                        
                        // Sekalian simpan ke LiTemplate (cache standar)
                        \App\Models\LiTemplate::updateOrCreate(
                            ['job_no' => $currentData['job_no'], 'part_no' => $currentData['part_no']],
                            $currentData
                        );
                        
                        $importedCount++;
                    } catch (\Exception $e) {
                        $errors[] = "Gagal menyimpan sheet {$sheet->getTitle()} untuk part {$parsedPartNo}: " . $e->getMessage();
                    }
                }
            }

            return response()->json([
                'message' => "Berhasil mengimport {$importedCount} Part Master.",
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            \Log::error('Excel Import Error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Gagal membaca file Excel. Pastikan format file sesuai.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}