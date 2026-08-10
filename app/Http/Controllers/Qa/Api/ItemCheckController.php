<?php

namespace App\Http\Controllers\Qa\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ItemCheck;

class ItemCheckController extends Controller
{
    public function pendingTtd(Request $request)
    {
        $user = $request->user();
        $query = ItemCheck::with(['masterTemplate']);

        if ($user->role === 'Admin') {
            $query->where('status', 'waiting_qc_approval');
        } elseif ($user->role === 'Operator') {
            $query->where('status', 'revision')
                  ->where('operator_id', $user->id);
        } else {
            $query->where(function($q) use ($user) {
                if (in_array($user->role, ['Group Leader', 'Leader'])) {
                    $q->where('status', 'waiting_qc_approval')
                      ->whereNull('paraf_foreman')
                      ->where('assigned_gl_id', $user->id);
                }
                if ($user->role === 'Foreman') {
                    $q->orWhere(function($q2) use ($user) {
                        $q2->where('status', 'waiting_qc_approval')
                           ->whereNotNull('paraf_foreman')
                           ->whereNull('paraf_leader')
                           ->where('assigned_foreman_id', $user->id);
                    });
                }
            });
        }

        $itemChecks = $query->orderBy('updated_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data'    => $itemChecks,
        ]);
    }

    public function show($id)
    {
        $itemCheck = ItemCheck::with(['masterTemplate', 'schedule', 'operator'])->find($id);

        if (!$itemCheck) {
            return response()->json(['message' => 'Data tidak ditemukan.'], 404);
        }

        $data = [
            'id' => $itemCheck->id,
            'job_no' => $itemCheck->schedule->job_no ?? 'DUMMY-JOB-001',
            'tanggal' => $itemCheck->tanggal ? $itemCheck->tanggal->format('Y-m-d') : '',
            'tgl_bulan' => $itemCheck->schedule->tanggal_produksi ? \Carbon\Carbon::parse($itemCheck->schedule->tanggal_produksi)->format('Y-m-d') : ($itemCheck->tanggal ? $itemCheck->tanggal->format('Y-m-d') : ''),
            'shift' => $itemCheck->shift ?? '',
            'total_pcs' => !is_null($itemCheck->total_produksi) ? $itemCheck->total_produksi : ($itemCheck->schedule->actual_qty ?? 0),
            'repair' => $itemCheck->repair ?? 0,
            'reject' => $itemCheck->reject ?? 0,
            'status' => $itemCheck->status ?? 'in_progress',
            'catatan' => $itemCheck->catatan ?? '',
            'catatan_revisi' => $itemCheck->catatan_revisi ?? '',
            'field_revisions' => is_string($itemCheck->field_revisions) ? json_decode($itemCheck->field_revisions, true) : ($itemCheck->field_revisions ?? new \stdClass()),
            'judgement' => $itemCheck->judgement ?? '',
            'dimData' => $itemCheck->hasil_dimensi ?? new \stdClass(),
            'appData' => $itemCheck->hasil_visual ?? new \stdClass(),
            'ngDetails' => $itemCheck->ng_details ?? new \stdClass(),
            'glSig' => $itemCheck->paraf_foreman ? true : null,
            'fmSig' => $itemCheck->paraf_leader ? true : null,
            'operator_name' => $itemCheck->operator->name ?? '',
            'gl_name' => '',
            'fm_name' => '',
            'assigned_operator_id' => $itemCheck->operator_id ?? null,
            'assigned_gl_id' => $itemCheck->assigned_gl_id ?? null,
            'assigned_foreman_id' => $itemCheck->assigned_foreman_id ?? null,
            'operator_claimed_at' => $itemCheck->waktu_mulai ? $itemCheck->waktu_mulai->toIso8601String() : null,
            'waktu_mulai' => $itemCheck->waktu_mulai ? $itemCheck->waktu_mulai->toIso8601String() : null,
            'waktu_selesai' => $itemCheck->waktu_selesai ? $itemCheck->waktu_selesai->toIso8601String() : null,
            'bundle_checks' => is_string($itemCheck->bundle_checks) ? json_decode($itemCheck->bundle_checks, true) : ($itemCheck->bundle_checks ?? []),
            'bundle_tindakan' => $itemCheck->bundle_tindakan ?? '',
        ];

        // Safely extract ONLY the standards/definitions from Master Template
        // DO NOT merge the whole array because it contains transactional data (signatures, ng_details) from when the master was created.
        if ($itemCheck->masterTemplate) {
            $masterArray = $itemCheck->masterTemplate->toArray();
            $safeKeys = [
                'job_no', 'part_no', 'part_name', 'spec_material', 'type_pallet', 'type', 'lokasi', 'proses_route',
                'image_path', 'max_sample', 'tact_time', 'ct_dimensi', 'ct_tanpa_dimensi',
                'prepared_paraf', 'qg_name', 'sampling_cols'
            ];

            // Add dimension standard keys
            for ($i = 1; $i <= 7; $i++) {
                $safeKeys = array_merge($safeKeys, [
                    "dimensi{$i}", "dimensi{$i}_item", "dimensi{$i}_method",
                    "dimensi{$i}_nominal", "dimensi{$i}_plus", "dimensi{$i}_minus"
                ]);
            }

            // Add appearance standard keys
            for ($i = 6; $i <= 14; $i++) {
                $safeKeys[] = "appearance{$i}";
            }

            foreach ($safeKeys as $key) {
                if (array_key_exists($key, $masterArray)) {
                    $data[$key] = $masterArray[$key];
                }
            }
            $data['master_paraf_gl'] = $itemCheck->masterTemplate->paraf_gl;
            $data['master_paraf_foreman'] = $itemCheck->masterTemplate->paraf_foreman;
            $data['master_frm_name'] = $itemCheck->masterTemplate->frm_name;
            $data['master_gl_name'] = $itemCheck->masterTemplate->gl_name;
        }

        // Add explicit paraf variables for Item Check form, ensuring we don't use master template's parafs
        $data['paraf_gl'] = $itemCheck->paraf_foreman; // GL uses paraf_foreman slot in ItemCheck
        $data['paraf_foreman'] = $itemCheck->paraf_leader; // Foreman uses paraf_leader slot
        $data['paraf_qc'] = $itemCheck->paraf_operator;
        $data['qpr_judgement'] = $itemCheck->judgement;
        $data['ng_details'] = $itemCheck->ng_details ?? [];
        $data['qpr_id'] = $itemCheck->qpr_id;
        $data['qpr_generated'] = $itemCheck->qpr_generated;

        return response()->json($data);
    }

    public function summaryList(Request $request)
    {
        $tanggal = $request->query('tanggal');
        $from = $request->query('from');
        $to = $request->query('to');

        $query = ItemCheck::with(['masterTemplate', 'schedule', 'operator']);
            
        if ($tanggal) {
            $query->whereDate('tanggal', $tanggal);
        } else {
            if ($from) {
                $query->whereDate('tanggal', '>=', $from);
            }
            if ($to) {
                $query->whereDate('tanggal', '<=', $to);
            }
        }

        $itemChecks = $query->orderBy('id', 'desc')->get();

        $formatted = $itemChecks->map(function($ic) {
            $master = $ic->masterTemplate;
            return [
                'id' => $ic->id,
                'no_form' => 'IC-' . str_pad($ic->id, 5, '0', STR_PAD_LEFT),
                'tgl_bulan' => $ic->tanggal ? $ic->tanggal->toIso8601String() : null,
                'created_at' => $ic->created_at ? $ic->created_at->toIso8601String() : null,
                'updated_at' => $ic->updated_at ? $ic->updated_at->toIso8601String() : null,
                'job_no' => $ic->schedule->job_no ?? '',
                'part_name' => $master->part_name ?? '',
                'part_no' => $master->part_no ?? '',
                'schedule_id' => $ic->production_schedule_id,
                'lokasi' => $master->lokasi ?? '',
                'line_name' => $master->lokasi ?? '',
                'proses_route' => $master->proses_route ?? '',
                'qg_judgement' => $ic->judgement,
                'status' => $ic->status,
                'ng_details' => $ic->ng_details,
                'repair' => $ic->repair,
                'reject' => $ic->reject,
                'total_produksi' => !is_null($ic->total_produksi) ? $ic->total_produksi : ($ic->schedule->actual_qty ?? 0),
                'gl_signed' => !empty($ic->paraf_foreman),
                'foreman_signed' => !empty($ic->paraf_leader),
                // Fields needed for sample counting on dashboard
                'hasil_visual' => $ic->hasil_visual,
                'hasil_dimensi' => $ic->hasil_dimensi,
                'sampling_cols' => $master->sampling_cols ?? [],
                'max_sample' => $master->max_sample ?? 0,
            ];
        });

        return response()->json($formatted);
    }

    public function store(Request $request)
    {
        // Actually, creating is usually done via Web start route.
        return response()->json(['message' => 'Create via Web /start route.'], 400);
    }

    public function start(Request $request, $id)
    {
        $itemCheck = ItemCheck::find($id);
        if (!$itemCheck) {
            return response()->json(['message' => 'Data tidak ditemukan.'], 404);
        }

        if (!$itemCheck->waktu_mulai) {
            $itemCheck->waktu_mulai = now();
            if ($itemCheck->status === 'draft') {
                $itemCheck->status = 'in_progress';
            }
            $itemCheck->save();
        }

        return response()->json([
            'message' => 'Pengukuran dimulai',
            'waktu_mulai' => $itemCheck->waktu_mulai->toIso8601String()
        ]);
    }

    public function update(Request $request, $id)
    {
        // \Log::info('ItemCheck update called', ['id' => $id, 'status' => $request->input('status')]);
        $itemCheck = ItemCheck::find($id);

        if (!$itemCheck) {
            return response()->json(['message' => 'Data tidak ditemukan.'], 404);
        }

        $newStatus = $request->input('status', $itemCheck->status);
        
        // Timer tracking logic:
        // Stop timer (set waktu_selesai) if submitted to next steps
        if (!in_array($newStatus, ['in_progress', 'draft', 'revision']) && !$itemCheck->waktu_selesai) {
            $itemCheck->waktu_selesai = now();
        }

        // IF changing from revision to waiting_qc_approval, clear the revision notes because operator has resolved them
        if ($itemCheck->status === 'revision' && $newStatus === 'waiting_qc_approval') {
            $itemCheck->catatan_revisi = null;
            $itemCheck->field_revisions = null;
        } else {
            if ($request->has('catatan_revisi')) {
                $itemCheck->catatan_revisi = $request->input('catatan_revisi');
            }
            if ($request->has('field_revisions')) {
                $itemCheck->field_revisions = $request->input('field_revisions');
            }
        }
        
        $itemCheck->status = $newStatus;
        $itemCheck->shift = $request->input('shift', $itemCheck->shift);
        $itemCheck->catatan = $request->input('catatan', $itemCheck->catatan);
        $itemCheck->judgement = $request->input('qg_judgement', $itemCheck->judgement);
        
        if ($request->has('total_produksi')) {
            $itemCheck->total_produksi = $request->input('total_produksi');
            
            // Sync total_produksi ke part tandem (jika ada) agar tidak perlu diisi dua kali
            if ($itemCheck->production_schedule_id) {
                ItemCheck::where('production_schedule_id', $itemCheck->production_schedule_id)
                    ->where('id', '!=', $itemCheck->id)
                    ->update(['total_produksi' => $itemCheck->total_produksi]);
            }
        }
        
        if ($request->has('repair')) {
            $itemCheck->repair = $request->input('repair');
        }
        if ($request->has('reject')) {
            $itemCheck->reject = $request->input('reject');
        }
        if ($request->has('assigned_gl_id')) {
            $itemCheck->assigned_gl_id = $request->input('assigned_gl_id');
        }
        if ($request->has('assigned_foreman_id')) {
            $itemCheck->assigned_foreman_id = $request->input('assigned_foreman_id');
        }
        
        if ($request->has('dimData')) {
            $itemCheck->hasil_dimensi = $request->input('dimData');
        }
        
        if ($request->has('appData')) {
            $itemCheck->hasil_visual = $request->input('appData');
        }

        if ($request->has('ng_details')) {
            $itemCheck->ng_details = $request->input('ng_details');
        }

        if ($request->has('bundle_checks')) {
            $itemCheck->bundle_checks = $request->input('bundle_checks');
        }
        if ($request->has('bundle_tindakan')) {
            $itemCheck->bundle_tindakan = $request->input('bundle_tindakan');
        }

        if ($request->has('paraf_qc')) {
            $itemCheck->paraf_operator = $request->input('paraf_qc');
        }
        if ($request->has('paraf_gl')) {
            $itemCheck->paraf_foreman = $request->input('paraf_gl'); // Hack for DB
        }
        if ($request->has('paraf_foreman')) {
            $itemCheck->paraf_leader = $request->input('paraf_foreman'); // Hack for DB
        }

        $itemCheck->save();

        // ── QPR AUTO-GENERATION ──
        // Trigger QPR generation if there is any NG (Visual or Dimension) AND the document is being locked/finished
        $hasNg = $itemCheck->hasNg();
        $isSubmitting = in_array($itemCheck->status, ['waiting_qc_approval', 'finished']);

        if ($hasNg && !$itemCheck->qpr_generated && $isSubmitting) {
            try {
                // Initialize signatures array for QPR Routing
                $sigs = [
                    ['position' => 'operator', 'role' => 'Operator', 'nama' => $itemCheck->operator->name ?? 'QA Operator', 'signature' => $itemCheck->paraf_operator ?? '', 'signed_at' => now()->toISOString()],
                    ['position' => 'foreman', 'role' => 'Foreman', 'nama' => '', 'signature' => '', 'signed_at' => null],
                    // Slot Seksi Terkait dikosongkan agar dipilih oleh QA Foreman nanti
                    ['position' => 'kasie', 'role' => 'Kasie QA', 'nama' => '', 'signature' => '', 'signed_at' => null],
                ];

                // Generate a draft QPR
                $qpr = \App\Models\Qpr::create([
                    'inspeksi_id'       => $itemCheck->lembar_inspeksi_id,
                    'no_job'            => $itemCheck->schedule->job_no ?? '',
                    'nama_part'         => $itemCheck->masterTemplate->part_name ?? '',
                    'model'             => $itemCheck->masterTemplate->type ?? '',
                    'tanggal'           => now(),
                    'proses_repair'     => $itemCheck->getProsesRepairString(),
                    'sketch'            => $itemCheck->masterTemplate->image_path ?? null,
                    'rework_qty'        => $itemCheck->repair ?? 0,
                    'reject_qty'        => $itemCheck->reject ?? 0,
                    'defect'            => $itemCheck->getDefectTypesString(),
                    'defect_keterangan' => $itemCheck->getDefectKeteranganString(),
                    'area'              => $itemCheck->getAreaKejadianString(),
                    'area_problems'     => $itemCheck->getAreaProblemsArray(),
                    'shift'             => $itemCheck->shift ?? '',
                    'jam'               => $itemCheck->getJamKejadianString(),
                    'lokasi'            => $itemCheck->masterTemplate->lokasi ?? '',
                    'status'            => 'Draft', // Draft so Operator can finish filling it out
                    'source'            => 'Item Check',
                    'pic_seksi'         => null, // Dinamis, bisa Dies Shop, Produksi SA, dll
                    'assigned_foreman_id' => $itemCheck->assigned_foreman_id ?? $itemCheck->assigned_gl_id ?? null,
                    'approval_signatures'=> json_encode($sigs),
                    'created_by'        => auth()->id() ?? $itemCheck->operator_id ?? 1,
                ]);

                $itemCheck->qpr_generated = true;
                $itemCheck->qpr_id = $qpr->id;
                $itemCheck->save();
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Gagal auto-generate QPR untuk Keeper NG: ' . $e->getMessage());
            }
        } elseif ($hasNg && $itemCheck->qpr_generated && $itemCheck->qpr_id) {
            try {
                // Update existing QPR's reject and rework quantities if NG data changes later (e.g. from Keeper findings)
                $qpr = \App\Models\Qpr::find($itemCheck->qpr_id);
                if ($qpr) {
                    $qpr->rework_qty = $itemCheck->repair ?? 0;
                    $qpr->reject_qty = $itemCheck->reject ?? 0;
                    $qpr->defect = $itemCheck->getDefectTypesString();
                    $qpr->defect_keterangan = $itemCheck->getDefectKeteranganString();
                    $qpr->area = $itemCheck->getAreaKejadianString();
                    $qpr->area_problems = $itemCheck->getAreaProblemsArray();
                    $qpr->proses_repair = $itemCheck->getProsesRepairString();
                    $qpr->shift = $itemCheck->shift ?? '';
                    $qpr->jam = $itemCheck->getJamKejadianString();
                    $qpr->lokasi = $itemCheck->masterTemplate->lokasi ?? '';
                    $qpr->save();
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Gagal sinkronisasi update QPR qty: ' . $e->getMessage());
            }
        }

        return response()->json([
            'message' => 'Data berhasil disimpan.',
            'data' => $itemCheck
        ]);
    }

    /**
     * Resume timer ketika Operator membuka kembali dokumen yang direvisi
     */
    public function resumeTimer(Request $request, $id)
    {
        $itemCheck = ItemCheck::find($id);
        if (!$itemCheck) {
            return response()->json(['message' => 'Data tidak ditemukan.'], 404);
        }

        if ($itemCheck->waktu_selesai && in_array($itemCheck->status, ['in_progress', 'revision'])) {
            $idleSeconds = now()->diffInSeconds($itemCheck->waktu_selesai);
            $itemCheck->waktu_mulai = \Carbon\Carbon::parse($itemCheck->waktu_mulai)->addSeconds($idleSeconds);
            $itemCheck->waktu_selesai = null;
            $itemCheck->save();

            return response()->json([
                'message' => 'Timer dilanjutkan.',
                'waktu_mulai' => $itemCheck->waktu_mulai->toIso8601String(),
                'waktu_selesai' => null
            ]);
        }

        return response()->json(['message' => 'Timer tidak perlu dilanjutkan.']);
    }

    public function sign(Request $request, $id)
    {
        $itemCheck = ItemCheck::find($id);
        if (!$itemCheck) {
            return response()->json(['message' => 'Data tidak ditemukan.'], 404);
        }

        $role = $request->input('role');
        $signature = $request->input('signature'); // base64 image
        $user = auth()->user();

        // Validate or authorize

        if ($role === 'gl_bottom') {
            $itemCheck->paraf_foreman = $signature;
        } elseif ($role === 'fm_bottom') {
            $itemCheck->paraf_leader = $signature;
        } elseif ($role === 'operator') {
            $itemCheck->paraf_operator = $signature;
        }

        // Evaluate Status dynamically based on presence of signatures
        // Jika GL dan Foreman sudah tanda tangan (bottom QC verification), otomatis status menjadi finished
        if (!empty($itemCheck->paraf_foreman) && !empty($itemCheck->paraf_leader)) {
            $itemCheck->status = 'finished';
            if (!$itemCheck->waktu_selesai) {
                $itemCheck->waktu_selesai = now();
            }
        }
        // Jika belum lengkap, biarkan status pada state saat ini (in_progress atau waiting_qc_approval)
        // karena waiting_foreman dan waiting_gl digunakan untuk routing Master Template (bagian atas).


        $itemCheck->save();

        $qprGenerated = false;
        $qprId = null;

        if ($itemCheck->status === 'finished') {
            $hasNg = $itemCheck->hasNg();

            if ($hasNg && !$itemCheck->qpr_generated) {
                try {
                    // Initialize signatures array for QPR Routing
                    $sigs = [
                        ['position' => 'operator', 'role' => 'Operator', 'nama' => $itemCheck->operator->name ?? 'QA Operator', 'signature' => $itemCheck->paraf_operator ?? '', 'signed_at' => now()->toISOString()],
                        ['position' => 'foreman', 'role' => 'Foreman', 'nama' => '', 'signature' => '', 'signed_at' => null],
                        // Slot Seksi Terkait dikosongkan agar dipilih oleh QA Foreman nanti
                        ['position' => 'kasie', 'role' => 'Kasie QA', 'nama' => '', 'signature' => '', 'signed_at' => null],
                    ];

                    $qpr = \App\Models\Qpr::create([
                        'inspeksi_id'       => $itemCheck->lembar_inspeksi_id,
                        'no_job'            => $itemCheck->schedule->job_no ?? '',
                        'nama_part'         => $itemCheck->masterTemplate->part_name ?? '',
                        'model'             => $itemCheck->masterTemplate->type ?? '',
                        'tanggal'           => now(),
                        'proses_repair'     => $itemCheck->getProsesRepairString(),
                        'sketch'            => $itemCheck->masterTemplate->image_path ?? null,
                        'rework_qty'        => $itemCheck->repair ?? 0,
                        'reject_qty'        => $itemCheck->reject ?? 0,
                        'defect'            => $itemCheck->getDefectTypesString(),
                        'defect_keterangan' => $itemCheck->getDefectKeteranganString(),
                        'area'              => $itemCheck->getAreaKejadianString(),
                        'area_problems'     => $itemCheck->getAreaProblemsArray(),
                        'shift'             => $itemCheck->shift ?? '',
                        'jam'               => $itemCheck->getJamKejadianString(),
                        'lokasi'            => $itemCheck->masterTemplate->lokasi ?? '',
                        'status'            => 'Draft', // Draft so Operator can finish filling it out
                        'source'            => 'Item Check',
                        'pic_seksi'         => null, // Dinamis
                        'assigned_foreman_id' => $itemCheck->assigned_foreman_id ?? $itemCheck->assigned_gl_id ?? null,
                        'approval_signatures'=> json_encode($sigs),
                        'created_by'        => auth()->id() ?? $itemCheck->operator_id ?? 1,
                    ]);

                    $itemCheck->qpr_generated = true;
                    $itemCheck->qpr_id = $qpr->id;
                    $itemCheck->save();

                    $qprGenerated = true;
                    $qprId = $qpr->id;
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Gagal auto-generate QPR untuk Keeper NG saat sign: ' . $e->getMessage());
                }
            }
        }

        return response()->json([
            'message' => 'Tanda tangan berhasil disimpan.',
            'status' => $itemCheck->status,
            'waktu_selesai' => $itemCheck->waktu_selesai ? $itemCheck->waktu_selesai->toIso8601String() : null,
            'qpr_generated' => $qprGenerated,
            'qpr_id' => $itemCheck->qpr_id,
        ]);
    }

    /**
     * GL mengirim catatan revisi per-field kembali ke Operator.
     * Ini akan mengubah status menjadi 'revision' dan menyimpan field_revisions.
     */
    public function saveFieldRevisions(Request $request, $id)
    {
        $itemCheck = ItemCheck::find($id);
        if (!$itemCheck) {
            return response()->json(['message' => 'Data tidak ditemukan.'], 404);
        }

        $fieldRevisions = $request->input('field_revisions', []);

        // Merge dengan field_revisions yang sudah ada (kalau ada)
        $existing = [];
        if ($itemCheck->field_revisions) {
            $existing = is_string($itemCheck->field_revisions)
                ? json_decode($itemCheck->field_revisions, true)
                : $itemCheck->field_revisions;
        }

        $merged = array_merge($existing ?? [], $fieldRevisions);

        $itemCheck->field_revisions = $merged;
        $itemCheck->catatan_revisi = $request->input('catatan_revisi', 'Revisi dari GL');
        $itemCheck->status = 'revision';

        // Hapus TTD operator agar harus TTD ulang setelah memperbaiki
        $itemCheck->paraf_operator = null;

        $itemCheck->save();

        return response()->json([
            'message' => 'Catatan revisi berhasil dikirim ke Operator.',
            'status' => $itemCheck->status,
            'field_revisions' => $merged,
        ]);
    }

    /**
     * Operator menandai satu field revisi sebagai "sudah diselesaikan".
     */
    public function resolveFieldRevision(Request $request, $id)
    {
        $itemCheck = ItemCheck::find($id);
        if (!$itemCheck) {
            return response()->json(['message' => 'Data tidak ditemukan.'], 404);
        }

        $field = $request->input('field');
        $resolved = $request->input('resolved', true);

        $revisions = [];
        if ($itemCheck->field_revisions) {
            $revisions = is_string($itemCheck->field_revisions)
                ? json_decode($itemCheck->field_revisions, true)
                : $itemCheck->field_revisions;
        }

        if (isset($revisions[$field])) {
            $revisions[$field]['resolved'] = $resolved;
            $revisions[$field]['resolved_at'] = now()->toIso8601String();
        }

        $itemCheck->field_revisions = $revisions;

        // Jika semua sudah resolved, update status otomatis
        $allResolved = count($revisions) > 0 && !collect($revisions)->contains(fn($r) => !($r['resolved'] ?? false));
        if ($allResolved && $itemCheck->status === 'revision') {
            $itemCheck->status = 'revision'; // Tetap revision, operator harus submit ulang secara sadar
        }

        $itemCheck->save();

        return response()->json([
            'message' => 'Revisi berhasil ditandai selesai.',
            'status' => $itemCheck->status,
            'field_revisions' => $revisions,
        ]);
    }

    /**
     * Assign operator ke dokumen item check.
     */
    public function assign(Request $request, $id)
    {
        $itemCheck = ItemCheck::find($id);
        if (!$itemCheck) {
            return response()->json(['message' => 'Data tidak ditemukan.'], 404);
        }

        if ($request->has('assigned_operator_id')) {
            $itemCheck->operator_id = $request->input('assigned_operator_id');
        }
        $itemCheck->save();

        return response()->json(['message' => 'Operator berhasil ditugaskan.']);
    }

    /**
     * Cari item check berdasarkan job_no atau part_no (untuk history load).
     */
    public function search(Request $request)
    {
        $q = $request->query('q', '');
        $results = ItemCheck::with(['masterTemplate', 'schedule'])
            ->whereHas('schedule', fn($query) => $query->where('job_no', 'like', "%{$q}%"))
            ->orWhereHas('masterTemplate', fn($query) => $query->where('part_no', 'like', "%{$q}%")->orWhere('part_name', 'like', "%{$q}%"))
            ->whereIn('status', ['finished', 'locked', 'approved'])
            ->orderBy('updated_at', 'desc')
            ->limit(20)
            ->get()
            ->map(fn($ic) => [
                'id'        => $ic->id,
                'job_no'    => $ic->schedule->job_no ?? '',
                'part_no'   => $ic->masterTemplate->part_no ?? '',
                'part_name' => $ic->masterTemplate->part_name ?? '',
                'status'    => $ic->status,
            ]);

        return response()->json(['data' => $results]);
    }
}
