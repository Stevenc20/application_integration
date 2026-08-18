<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Qpr;
use App\Models\QprAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ApprovalToken;
use Illuminate\Support\Str;

class QprController extends Controller
{
    private function processStateMachine($data, $actions)
    {
        $currentStatus = $data['status'] ?? 'OPEN';
        
        if (in_array($currentStatus, ['Draft', 'OPEN', 'Pending Approval', 'Revision'])) {
            return ['status' => $currentStatus, 'is_a3_required' => $data['is_a3_required'] ?? false];
        }

        if (empty($actions)) {
            return ['status' => $currentStatus === 'GL Approved' ? 'GL Approved' : 'Waiting Action 1', 'is_a3_required' => false];
        }

        $failedCount = 0;
        $lastAction = null;

        foreach ($actions as $index => $act) {
            $lastAction = $act;
            if (($act['verif_1_status'] ?? '') === 'NG' || 
                ($act['verif_2_status'] ?? '') === 'NG' || 
                ($act['verif_3_status'] ?? '') === 'NG') {
                $failedCount++;
            }
        }

        $is_a3_required = ($failedCount >= 3);

        // Jika QPR sudah pernah diclose oleh QA setelah verif A3, pertahankan status Close
        if ($failedCount >= 3 && $currentStatus === 'Close') {
            return ['status' => 'Close', 'is_a3_required' => true];
        }

        if ($failedCount >= 3) {
            // Signal dari frontend jika QA memverifikasi A3 dan klik OK
            if (($data['a3_status'] ?? '') === 'OK') {
                return ['status' => 'Close', 'is_a3_required' => true];
            }

            if (!empty($data['a3_document'])) {
                // Dokumen A3 disubmit
                return ['status' => 'Waiting Verif A3', 'is_a3_required' => true];
            }
            return ['status' => 'Waiting A3 Report', 'is_a3_required' => true];
        }

        // If the last action failed (under 3 fails), wait for the next action
        if (($lastAction['verif_1_status'] ?? '') === 'NG' || 
            ($lastAction['verif_2_status'] ?? '') === 'NG' || 
            ($lastAction['verif_3_status'] ?? '') === 'NG') {
            
            $nextActionNum = $failedCount + 1;
            return ['status' => "Waiting Action $nextActionNum", 'is_a3_required' => $is_a3_required];
        }

        if (empty($lastAction['action']) || !in_array($lastAction['pdca'] ?? '', ['C', 'A'])) {
            $currentActionNum = $failedCount + 1;
            $waitStatus = $failedCount == 0 ? (($currentStatus === 'GL Approved' && empty($lastAction['action'])) ? 'GL Approved' : 'Progress') : "Waiting Action $currentActionNum";
            return ['status' => $waitStatus, 'is_a3_required' => $is_a3_required];
        }

        // SIGNATURE GATE
        $signatures = [];
        if (isset($data['approval_signatures'])) {
            $sigData = $data['approval_signatures'];
            if (is_string($sigData)) {
                $signatures = json_decode($sigData, true) ?? [];
            } elseif (is_array($sigData)) {
                $signatures = $sigData;
            }
        }

        $seksiList = ["IRM", "Produksi SA", "Produksi Stamping", "Produksi Metal Finish", "Logistic", "PPC", "Delivery", "Procurement", "Dies Shop", "Plant Service", "Quality Assurance", "Maintenance"];
        
        $seksiSigners = array_filter($signatures, function($s) use ($seksiList) {
            return in_array($s['role'] ?? '', $seksiList);
        });

        $allSeksiSigned = count($seksiSigners) > 0;
        foreach ($seksiSigners as $s) {
            if (empty($s['signature'])) {
                $allSeksiSigned = false;
                break;
            }
        }

        // Before going to Verif 1, Seksi MUST have signed!
        if (!$allSeksiSigned) {
            $currentActionNum = $failedCount + 1;
            $waitStatus = $failedCount == 0 ? 'Progress' : "Waiting Action $currentActionNum";
            return ['status' => $waitStatus, 'is_a3_required' => $is_a3_required];
        }

        if (empty($lastAction['verif_1_status'])) return ['status' => 'Waiting Verif 1', 'is_a3_required' => $is_a3_required];
        if (empty($lastAction['verif_2_status'])) return ['status' => 'Waiting Verif 2', 'is_a3_required' => $is_a3_required];
        if (empty($lastAction['verif_3_status'])) return ['status' => 'Waiting Verif 3', 'is_a3_required' => $is_a3_required];

        if (($lastAction['verif_1_status'] ?? '') === 'OK' && 
            ($lastAction['verif_2_status'] ?? '') === 'OK' && 
            ($lastAction['verif_3_status'] ?? '') === 'OK') {
            
            // Cek apakah correction_items dan dampak_items sudah selesai semua (status 'A')
            $correctionItems = $data['correction_items'] ?? [];
            if (is_string($correctionItems)) {
                $correctionItems = json_decode($correctionItems, true) ?? [];
            }
            
            $dampakItems = $data['dampak_items'] ?? [];
            if (is_string($dampakItems)) {
                $dampakItems = json_decode($dampakItems, true) ?? [];
            }

            $hasPendingCorr = collect($correctionItems)->contains(fn($c) => !empty($c['text']) && ($c['status'] ?? '') !== 'A');
            $hasPendingDamp = collect($dampakItems)->contains(fn($d) => !empty($d['text']) && ($d['status'] ?? '') !== 'A');
            
            // Before closing, all PDCA actions must be 'A'
            if ($hasPendingCorr || $hasPendingDamp || ($lastAction['pdca'] ?? '') !== 'A') {
                return ['status' => 'Waiting Verif 3', 'is_a3_required' => $is_a3_required];
            }
            return ['status' => 'Close', 'is_a3_required' => $is_a3_required];
        }

        return ['status' => $currentStatus, 'is_a3_required' => $is_a3_required];
    }

    private function canEditSeksiSection($user, $qpr) {
        if (!$user) return true;
        if ($user->role === 'Admin') return true;
        if ($qpr->pic_seksi && $user->department === $qpr->pic_seksi) return true;
        return false;
    }

    /**
     * Auto-apply signatures dari profil user yang sudah pernah TTD.
     * - Slot Foreman: cek assigned_foreman_id → users.signature
     * - Slot Seksi: cek department → users.signature (user pertama dengan signature dari dept itu)
     * Jika foreman slot terisi → status diadvance ke GL Approved.
     * Jika semua seksi slot terisi → processStateMachine.
     */
    private function autoApplyStoredSignatures(Qpr $qpr): void
    {
        $sigs = $qpr->approval_signatures;
        if (!is_array($sigs)) {
            $sigs = is_string($sigs) ? json_decode($sigs, true) ?? [] : [];
        }

        $changed   = false;
        $foremanFilled = false;

        foreach ($sigs as &$sig) {
            if (!empty($sig['signature'])) continue; // Sudah ada TTD

            $position = $sig['position'] ?? '';

            if ($position === 'foreman') {
                // Cari TTD dari assigned foreman user
                $foremanId = $qpr->assigned_foreman_id;
                if ($foremanId) {
                    $user = \App\Models\User::find($foremanId);
                    if ($user && $user->signature) {
                        $sig['signature'] = $user->signature;
                        $sig['nama']      = $user->name;
                        $sig['signed_at'] = now()->toISOString();
                        $foremanFilled    = true;
                        $changed          = true;
                    }
                }

            } elseif ($position === 'seksi') {
                // Cari TTD dari user yang departemennya sesuai role seksi
                $dept = $sig['role'] ?? '';
                if ($dept) {
                    $user = \App\Models\User::where('department', $dept)
                        ->whereNotNull('signature')
                        ->where('signature', '!=', '')
                        ->first();
                    if ($user) {
                        $sig['signature'] = $user->signature;
                        $sig['nama']      = $user->name;
                        $sig['signed_at'] = now()->toISOString();
                        $changed          = true;
                    }
                }
            }
        }
        unset($sig);

        if (!$changed) return;

        // Advance status jika Foreman baru saja auto-filled
        if ($foremanFilled && in_array($qpr->status, ['OPEN', 'Pending Approval'])) {
            $qpr->status = 'GL Approved';
        }

        // Cek apakah semua seksi sudah TTD → jalankan state machine
        $seksiSlots  = array_filter($sigs, fn($s) => ($s['position'] ?? '') === 'seksi');
        $allSeksiOk  = count($seksiSlots) > 0 && collect($seksiSlots)->every(fn($s) => !empty($s['signature']));

        if ($allSeksiOk && $qpr->status === 'GL Approved') {
            $qprData = array_merge($qpr->toArray(), ['status' => 'Progress', 'approval_signatures' => $sigs]);
            $state   = $this->processStateMachine($qprData, $qpr->actions->toArray());
            $qpr->status = $state['status'];
            $qpr->is_a3_required = $state['is_a3_required'];
        }

        $qpr->approval_signatures = $sigs;
        $qpr->save();
    }

    // Department → Seksi name mapping (must match SEKSI_NAMA keys in qprForm.js)
    private const DEPT_TO_SEKSI = [
        'IRM'                  => 'IRM',
        'Produksi SA'          => 'Produksi SA',
        'Produksi Stamping'    => 'Produksi Stamping',
        'Produksi Metal Finish'=> 'Produksi Metal Finish',
        'Logistic'             => 'Logistic',
        'PPC'                  => 'PPC',
        'Delivery'             => 'Delivery',
        'Procurement'          => 'Procurement',
        'Dies Shop'            => 'Dies Shop',
        'Plant Service'        => 'Plant Service',
        'Quality Assurance'    => 'Quality Assurance',
        'Maintenance'          => 'Maintenance',
    ];

    // GET /api/qprs
    public function index(Request $request)
    {
        $query = Qpr::with(['actions', 'inspeksi'])
            ->orderBy('created_at', 'asc');

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->query('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->query('to'));
        }

        $qprs = $query->get();

        // Tambahkan computed fields untuk registrasi
        $result = $qprs->map(function ($qpr) {
            $li = $qpr->inspeksi;

            // 1. DATE OF ISSUE: dari tgl_bulan LI (tanggal item check NG), fallback ke tanggal QPR
            $dateOfIssue = $li?->tgl_bulan
                ? \Carbon\Carbon::parse($li->tgl_bulan)->toDateString()
                : ($qpr->tanggal ? \Carbon\Carbon::parse($qpr->tanggal)->toDateString() : null);

            // 2. PROBLEM DESCRIPTION: [nama_part/no_job], [OP / proses_repair], [defect]
            $noJob       = $li?->job_no ?? $qpr->no_job ?? '';
            $namaPart    = $li?->part_name ?? $qpr->nama_part ?? '';
            $prosesRepair = $qpr->proses_repair ?? '';
            $defectStr   = $qpr->defect ?: ($qpr->defect_keterangan ?: ($li ? $li->getDefectString() : ''));
            
            $problemParts = [];
            if ($noJob) $problemParts[] = $noJob;
            elseif ($namaPart) $problemParts[] = $namaPart;
            
            if ($prosesRepair) {
                // Tambahkan prefix 'OP' jika belum ada
                $cleanOp = trim($prosesRepair);
                if (stripos($cleanOp, 'op') === false) {
                    $problemParts[] = 'OP ' . $cleanOp;
                } else {
                    $problemParts[] = $cleanOp;
                }
            }
            
            if ($defectStr) $problemParts[] = $defectStr;
            
            $problemDesc = implode(', ', $problemParts);

            // 3. INVESTIGATOR: Prioritaskan Operator QC, lalu fallback ke yang lain
            $sigs = is_array($qpr->approval_signatures) ? $qpr->approval_signatures : json_decode($qpr->approval_signatures, true) ?? [];
            $opSig = collect($sigs)->firstWhere('position', 'operator');
            
            $creator = $qpr->created_by ? \App\Models\User::find($qpr->created_by) : null;
            
            $investigator = $li?->qc_name;
            $investigator = $investigator ?: (!empty($opSig['nama']) ? $opSig['nama'] : null);
            $investigator = $investigator ?: $li?->assignedOperator?->name;
            $investigator = $investigator ?: $creator?->name;
            $investigator = $investigator ?: $qpr->pic;
            $investigator = $investigator ?: $li?->qg_name;
            $investigator = $investigator ?: $li?->creator?->name;
            $investigator = $investigator ?: '';

            $investigatorSig = (!empty($opSig['signature']) ? $opSig['signature'] : null);

            // 4. REPORT NO: otomatis generate jika belum ada
            $noQpr = $qpr->no_qpr;
            if (empty($noQpr) && $dateOfIssue) {
                $noQpr = $this->generateNoQpr($qpr, $dateOfIssue);
            }

            // 5. TARGET SELESAI & VERIF dari actions (ambil yang paling AKHIR / LATEST karena jika Verif NG akan buat action baru)
            $lastAction   = $qpr->getRelation('actions')->sortByDesc('id')->first();
            $targetSelesai = $qpr->target_selesai
                ? \Carbon\Carbon::parse($qpr->target_selesai)->toDateString()
                : ($lastAction?->schedule ? \Carbon\Carbon::parse($lastAction->schedule)->toDateString() : null);
            $v1 = $qpr->verif_1 ?? ($lastAction?->tgl_verif_1 ? \Carbon\Carbon::parse($lastAction->tgl_verif_1)->toDateString() : null);
            $verif1 = $v1 ? $v1 . (($lastAction?->verif_1_status && !str_contains($v1, $lastAction->verif_1_status)) ? ' ' . $lastAction->verif_1_status : '') : null;
            
            $v2 = $qpr->verif_2 ?? ($lastAction?->tgl_verif_2 ? \Carbon\Carbon::parse($lastAction->tgl_verif_2)->toDateString() : null);
            $verif2 = $v2 ? $v2 . (($lastAction?->verif_2_status && !str_contains($v2, $lastAction->verif_2_status)) ? ' ' . $lastAction->verif_2_status : '') : null;
            
            $v3 = $qpr->verif_3 ?? ($lastAction?->tgl_verif_3 ? \Carbon\Carbon::parse($lastAction->tgl_verif_3)->toDateString() : null);
            $verif3 = $v3 ? $v3 . (($lastAction?->verif_3_status && !str_contains($v3, $lastAction->verif_3_status)) ? ' ' . $lastAction->verif_3_status : '') : null;

            // 6. HASIL: Computed based on Actions PCDA, Verifikasi, dan TTD
            $allVerifsFilled = !empty($verif1) && !empty($verif2) && !empty($verif3);
            
            $allSigsFilled = false;
            if (is_array($sigs) && count($sigs) > 0) {
                // Semua pihak yang terdaftar di approval_signatures harus sudah TTD
                $allSigsFilled = collect($sigs)->every(function ($s) {
                    return !empty($s['signature']);
                });
            }

            $actions = $qpr->getRelation('actions');
            if ($actions->count() > 0) {
                $pdcaMap = ['p' => 1, 'plan' => 1, 'd' => 2, 'do' => 2, 'c' => 3, 'check' => 3, 'a' => 4, 'action' => 4, 'ok' => 4];
                $lowestPdcaVal = 4;
                $lowestPdcaKey = 'a';

                foreach ($actions as $act) {
                    $valStr = strtolower($act->pdca ?? '');
                    $val = $pdcaMap[$valStr] ?? 1; // Default ke Plan jika kosong
                    if ($val < $lowestPdcaVal) {
                        $lowestPdcaVal = $val;
                        $lowestPdcaKey = $valStr;
                    }
                }

                // Jika semua Langkah Perbaikan sudah 'A' (Action/OK)
                if ($lowestPdcaVal === 4) {
                    // Wajib melengkapi Verif I, II, III dan semua TTD
                    if (!$allVerifsFilled || !$allSigsFilled) {
                        $hasil = 'c'; // Turun ke Check (C) karena masih menunggu verifikasi/TTD akhir
                    } else {
                        $hasil = 'a'; // Kasus Selesai (OK)
                    }
                } else {
                    $hasil = $lowestPdcaKey;
                }
            } else {
                $hasil = $qpr->hasil ?? ''; // Fallback
            }

            return array_merge($qpr->toArray(), [
                'date_of_issue'    => $dateOfIssue,
                'problem_desc'     => $problemDesc ?: '—',
                'investigator_name' => $investigator,
                'investigator_sig' => $investigatorSig,
                'reporter_name'    => $qpr->pic,
                'no_qpr'           => $noQpr,
                'target_selesai'   => $targetSelesai,
                'verif_1'          => $verif1,
                'verif_2'          => $verif2,
                'verif_3'          => $verif3,
                'hasil'            => $hasil,
            ]);
        });

        return response()->json($result);
    }

    /**
     * Generate REPORT NO format: 01/QG/IPPI/06/2026
     * Nomor urut dihitung dari QPR yang sudah complete (semua TTD ada)
     */
    private function generateNoQpr(Qpr $qpr, string $dateOfIssue): string
    {
        $date     = \Carbon\Carbon::parse($dateOfIssue);
        $month    = $date->format('m'); // e.g. 06
        $year     = $date->format('Y'); // e.g. 2026

        // Hitung urutan: semua QPR di bulan yang sama
        $seq = Qpr::whereMonth('created_at', $date->month)
            ->whereYear('created_at', $date->year)
            ->where('id', '<=', $qpr->id)
            ->count();

        return sprintf('%02d/QG/IPPI/%s/%s', $seq, $month, $year);
    }

    // GET /api/qprs/{id}
    public function show($id)
    {
        $qpr = Qpr::with(['actions', 'inspeksi'])->findOrFail($id);
        
        $li = $qpr->inspeksi;
        $dateOfIssue = $li?->tgl_bulan
            ? \Carbon\Carbon::parse($li->tgl_bulan)->toDateString()
            : ($qpr->tanggal ? \Carbon\Carbon::parse($qpr->tanggal)->toDateString() : null);

        $noQpr = $qpr->no_qpr;
        if (empty($noQpr) && $dateOfIssue) {
            $noQpr = $this->generateNoQpr($qpr, $dateOfIssue);
            $qpr->no_qpr = $noQpr;
        }

        // Hitung investigator untuk UI
        $sigs = is_array($qpr->approval_signatures) ? $qpr->approval_signatures : json_decode($qpr->approval_signatures, true) ?? [];
        $opSig = collect($sigs)->firstWhere('position', 'operator');
        
        $creator = $qpr->created_by ? \App\Models\User::find($qpr->created_by) : null;
        
        $investigator = $li?->qc_name;
        $investigator = $investigator ?: (!empty($opSig['nama']) ? $opSig['nama'] : null);
        $investigator = $investigator ?: $li?->assignedOperator?->name;
        $investigator = $investigator ?: $creator?->name;
        $investigator = $investigator ?: $qpr->pic;
        $investigator = $investigator ?: $li?->qg_name;
        $investigator = $investigator ?: $li?->creator?->name;
        $investigator = $investigator ?: '';
        $qpr->computed_investigator = $investigator;

        return response()->json($qpr);
    }

    // POST /api/qprs
    public function store(Request $request)
    {
        $validated = $request->validate([
        'no_job'               => 'nullable|string|max:255',
        'model'                => 'nullable|string|max:255',
        'tanggal'              => 'nullable|date',
        'nama_part'            => 'required|string|max:255',
        'no_qpr'               => 'nullable|string|max:255',
        'kontrol_part'         => 'nullable|string|max:255',
        'rework_qty'           => 'nullable|integer|min:0',
        'reject_qty'           => 'nullable|integer|min:0',
        'stock_ippi_qty'       => 'nullable|integer|min:0',
        'rencana_produksi'     => 'nullable|string',
        'proses_repair'        => 'nullable|string|max:255',
        'kategori_problem'     => 'nullable|string|max:255',
        'defect'               => 'nullable|string|max:255',
        'defect_keterangan'    => 'nullable|string',
        'area'                 => 'nullable|string|max:255',
        'area_problems'        => 'nullable|array',
        'lokasi'               => 'nullable|string|max:255',
        'shift'                => 'nullable|string|max:255',
        'jam'                  => 'nullable|string',
        'analisa_man'          => 'nullable|boolean',
        'analisa_method'       => 'nullable|boolean',
        'analisa_machine'      => 'nullable|boolean',
        'analisa_material'     => 'nullable|boolean',
        'analisa_environment'  => 'nullable|boolean',
        'analisa_man_ket'         => 'nullable|string|max:255',
        'analisa_method_ket'      => 'nullable|string|max:255',
        'analisa_machine_ket'     => 'nullable|string|max:255',
        'analisa_material_ket'    => 'nullable|string|max:255',
        'analisa_environment_ket' => 'nullable|string|max:255',
        'approval_signatures'     => 'nullable',
        'target'               => 'nullable|date',
        'pic'                  => 'nullable|string|max:255',
        'status'               => 'nullable|string|max:255',
        'pencegahan'           => 'nullable',
        'sketch'               => 'nullable|string|max:255',
        'created_by'           => 'nullable|integer',
        'actions'              => 'nullable|array',
        'actions.*.action'      => 'nullable|string',
        'actions.*.schedule'    => 'nullable|date',
        'actions.*.tgl_verif_1' => 'nullable|date',
        'actions.*.tgl_verif_2' => 'nullable|date',
        'actions.*.tgl_verif_3' => 'nullable|date',
        'actions.*.pdca'        => 'nullable|string|max:10',
        'actions.*.status'      => 'nullable|string|max:255',
        'actions.*.pic'         => 'nullable|string|max:255',
        'sketches'              => 'nullable',
        'last_date_problem'     => 'nullable|date',
        'correction'            => 'nullable',
        'correction_items'      => 'nullable|array',
        'dampak_items'          => 'nullable|array',
        'pic_seksi'             => 'nullable|string|max:255',
        'dokumen'               => 'nullable|string|max:255',
        'assigned_foreman_id'   => 'nullable|integer',
        'target_selesai'        => 'nullable|date',
        'verif_1'               => 'nullable|string|max:255',
        'verif_2'               => 'nullable|string|max:255',
        'verif_3'               => 'nullable|string|max:255',
        'hasil'                 => 'nullable|string|max:255',
        'remark'                => 'nullable|string|max:255',
        'is_a3_required'        => 'nullable|boolean',
        'a3_due_date'           => 'nullable|date',
        'a3_document'           => 'nullable|string|max:255',
        'parent_qpr_id'         => 'nullable|integer',
        'actions.*.verif_1_status' => 'nullable|string|max:255',
        'actions.*.verif_2_status' => 'nullable|string|max:255',
        'actions.*.verif_3_status' => 'nullable|string|max:255',
    ]);
        DB::beginTransaction();
        try {
            $actions = $validated['actions'] ?? [];
            unset($validated['actions']);

            // Tentukan status awal: passthroughkan Draft, OPEN, Pending Approval.
            // Status lain akan divalidasi oleh state machine (mencegah bypass).
            $incomingStatus = $validated['status'] ?? 'OPEN';
            $passthrough = ['Draft', 'OPEN', 'Pending Approval', 'Revision'];
            if (!in_array($incomingStatus, $passthrough)) {
                // Jalankan state machine agar status konsisten dengan data yang ada
                $state = $this->processStateMachine($validated, $actions);
                $validated['status'] = $state['status'];
                $validated['is_a3_required'] = $state['is_a3_required'];
            } else {
                $validated['status'] = $incomingStatus;
            }
            // Validate evidence before creating
            foreach ($actions as $idx => $action) {
                if (!empty($action['action']) && in_array($action['pdca'] ?? '', ['C', 'A'])) {
                    if (empty($action['evidence_file']) && empty($action['evidence_remarks'])) {
                        DB::rollBack();
                        return response()->json(['message' => 'Langkah Perbaikan ke-' . ($idx + 1) . ': Bukti Perbaikan (Foto / Keterangan) diperlukan untuk meminta Verifikasi QA.'], 422);
                    }
                }
            }

            $qpr = Qpr::create($validated);

            foreach ($actions as $action) {
                if (!empty($action['action'])) {
                    $qpr->actions()->create($action);
                }
            }

            DB::commit();
            return response()->json(['message' => 'QPR berhasil disimpan', 'data' => $qpr->load('actions')], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal menyimpan QPR', 'error' => $e->getMessage()], 500);
        }
    }

    // PUT /api/qprs/{id}
    public function update(Request $request, $id)
    {
        $qpr = Qpr::findOrFail($id);
        if ($request->user() && $request->user()->role === 'Operator' && $qpr->created_by !== $request->user()->id && $qpr->pic !== $request->user()->name) {
            return response()->json(['message' => 'Anda hanya dapat mengedit QPR yang Anda buat atau ditugaskan kepada Anda.'], 403);
        }

        DB::beginTransaction();
        try {
            $data = $request->except('actions');
            
            // PROTEKSI SEKSI TERKAIT: Cegah update pada kolom verifikasi dan action jika bukan PIC Seksi
            $isQA = in_array($request->user()->role ?? '', ['Kasie QA', 'Kasie', 'Group Leader', 'Foreman']);
            
            if (!$this->canEditSeksiSection($request->user(), $qpr)) {
                unset($data['correction_items']);
                unset($data['dampak_items']);
                // Hanya hapus actions jika BUKAN QA
                if (!$isQA) {
                    $request->request->remove('actions');
                }
            }

            if ($request->has('actions')) {
                foreach ($request->actions as $idx => $action) {
                    if (in_array($action['pdca'] ?? '', ['C', 'A'])) {
                        if (empty($action['evidence_file']) && empty($action['evidence_remarks'])) {
                            DB::rollBack();
                            return response()->json(['message' => 'Langkah Perbaikan ke-' . ($idx + 1) . ': Bukti Perbaikan (Foto / Keterangan) diperlukan untuk meminta Verifikasi QA.'], 422);
                        }
                    }
                }

                if (in_array($data['status'] ?? $qpr->status, ['GL Approved', 'Progress', 'Waiting Action 1', 'Waiting Verif 1', 'Waiting Action 2', 'Waiting Verif 2', 'Waiting Action 3', 'Waiting Verif 3', 'Waiting A3 Report', 'Close'])) {
                    $state = $this->processStateMachine(array_merge($qpr->toArray(), $data), $request->actions);
                    $data['status'] = $state['status'];
                    $data['is_a3_required'] = $state['is_a3_required'];
                }
            }

            $qpr->update($data);

            if ($request->has('actions')) {
                $qpr->actions()->delete();
                foreach ($request->actions as $action) {
                    if (!empty($action['action'])) {
                        $qpr->actions()->create($action);
                    }
                }
            }

            DB::commit();
            return response()->json(['message' => 'QPR berhasil diupdate', 'data' => $qpr->load('actions')]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal update QPR', 'error' => $e->getMessage()], 500);
        }
    }

    // PATCH /api/qprs/{id}/draft  — simpan draft tanpa validasi ketat
    public function saveDraft(Request $request, $id = null)
    {
        DB::beginTransaction();
        try {
            $data = $request->except('actions');
            $data['status'] = 'Draft';

            if ($id) {
                $qpr = Qpr::findOrFail($id);
                if ($request->user() && $request->user()->role === 'Operator' && $qpr->created_by !== $request->user()->id && $qpr->pic !== $request->user()->name) {
                    return response()->json(['message' => 'Anda hanya dapat mengedit QPR yang Anda buat atau ditugaskan kepada Anda.'], 403);
                }
                
                // PROTEKSI SEKSI TERKAIT
                if (!$this->canEditSeksiSection($request->user(), $qpr)) {
                    unset($data['correction_items']);
                    unset($data['dampak_items']);
                    $request->request->remove('actions');
                }

                $qpr->update($data);
                if ($request->has('actions')) {
                    $qpr->actions()->delete();
                    foreach ($request->actions as $action) {
                        if (!empty($action['action'])) {
                            $qpr->actions()->create($action);
                        }
                    }
                }
                }

                DB::commit();
                return response()->json(['message' => 'Draft disimpan', 'data' => $qpr->load('actions')]);
                } catch (\Exception $e) {
                    DB::rollBack();
                    \Log::error('QPR Store Error: ' . $e->getMessage() . ' | Line: ' . $e->getLine() . ' | File: ' . $e->getFile());
                    return response()->json(['message' => 'Gagal menyimpan QPR', 'error' => $e->getMessage()], 500);
                }
        }

        // DELETE /api/qprs/{id}
        public function destroy($id)
        {
            if (auth()->user()?->role === 'Operator') {
                return response()->json(['message' => 'Operator dilarang menghapus QPR'], 403);
            }

            $qpr = Qpr::findOrFail($id);

            // IMMORTAL QPRs: QPR yang kategorinya Kadang-Kadang atau Sering tidak boleh dihapus
            // karena akan digunakan sebagai komparasi histori masalah
            if (in_array($qpr->kategori_problem, ['Kadang-Kadang', 'Sering'])) {
                return response()->json(['message' => 'Dokumen QPR ini tidak dapat dihapus karena ditandai sebagai masalah yang Sering/Kadang terjadi, dan wajib disimpan sebagai histori pembanding.'], 403);
            }

            $qpr->actions()->delete();
            $qpr->delete();
            return response()->json(['message' => 'QPR dihapus']);
        }

        public function uploadSketch(Request $request)
        {
            $request->validate(['file' => 'required|image|max:5120']);
            $path = $request->file('file')->store('sketches', 'public');
            return response()->json(['url' => '/storage/' . $path]);
        }

        // POST /api/qprs/{id}/generate-tokens
        public function generateTokens($id)
        {
            $qpr  = Qpr::findOrFail($id);
            $sigs = is_array($qpr->approval_signatures)
                ? $qpr->approval_signatures
                : json_decode($qpr->approval_signatures, true) ?? [];

            ApprovalToken::where('qpr_id', $id)->delete();

            foreach ($sigs as $sig) {
                if (empty($sig['nama'])) continue;

                $user = \App\Models\User::where('name', $sig['nama'])->first();

                ApprovalToken::create([
                    'qpr_id'   => $id,
                    'user_id'  => $user?->id,
                    'role'     => $sig['role']     ?? '',
                    'position' => $sig['position'] ?? '',
                    'nama'     => $sig['nama'],
                    'token'    => Str::random(40),
                ]);
            }

            return response()->json(['message' => 'Tokens generated']);
        }

        // GET /api/qprs/pending-approval
        public function pendingApproval(Request $request)
        {
            $user = $request->user();
            $pending = [];

            // ── GL / Foreman dari QC → lihat QPR yang di-assign ke mereka ─────────
            $isQcForeman = in_array($user->role, ['Group Leader', 'GroupLeader', 'Foreman', 'foreman'])
                        && $user->department === 'Quality Control';

            if ($isQcForeman) {
                // TTD GL/Foreman Awal
                $qprs_ttd = QPR::whereIn('status', ['Pending Approval', 'OPEN'])
                    ->where('assigned_foreman_id', $user->id)
                    ->get();

                foreach ($qprs_ttd as $qpr) {
                    $sigsRaw = $qpr->approval_signatures;
                    $sigs = is_array($sigsRaw) ? $sigsRaw : (is_string($sigsRaw) ? json_decode($sigsRaw, true) ?? [] : []);
                    $foremanSig = collect($sigs)->firstWhere('position', 'foreman');

                    if ($foremanSig && empty($foremanSig['signature'])) {
                        $pending[] = [
                            'type'     => 'foreman',
                            'role'     => 'GL / Foreman',
                            'position' => 'foreman',
                            'qpr'      => $qpr,
                        ];
                    }
                }
            }

            // ── QA (Foreman/GL/Kasie) → lihat QPR yang butuh Verifikasi ─────────
            $isQaRole = in_array($user->role, ['Group Leader', 'GroupLeader', 'Foreman', 'foreman', 'Kasie QA', 'Kasie'])
                     && in_array($user->department, ['QA', 'Quality Assurance', 'Quality Control']);

            if ($isQaRole) {
                $qprs_verif = QPR::whereIn('status', ['Waiting Verif 1', 'Waiting Verif 2', 'Waiting Verif 3'])->get();
                foreach ($qprs_verif as $qpr) {
                    $pending[] = [
                        'type'     => 'qa_verif',
                        'role'     => 'QA Verificator',
                        'position' => 'qa',
                        'qpr'      => $qpr,
                    ];
                }
            }
            
            if ($isQcForeman || $isQaRole) {
                return response()->json($pending);
            }

            // ── Staff / Supervisor / Foreman dari seksi lain → lihat QPR Seksi ───
            $seksiName = self::DEPT_TO_SEKSI[$user->department ?? ''] ?? null;
            if (!$seksiName) {
                return response()->json([]);
            }

            // Ambil semua QPR yang GL sudah TTD atau butuh Action lanjutan
            $qprs = QPR::where(function($q) {
                    $q->whereIn('status', ['GL Approved', 'Progress', 'Waiting A3 Report'])
                      ->orWhere('status', 'like', 'Waiting Action%');
                })
                ->whereNotNull('approval_signatures')
                ->get();

            foreach ($qprs as $qpr) {
                $sigString = $qpr->approval_signatures;
                $sigsRaw = is_array($sigString) ? $sigString : (is_string($sigString) ? json_decode($sigString, true) ?? [] : []);
                $sigs = collect($sigsRaw);

                // GL/Foreman harus sudah TTD dulu (untuk semua kasus)
                $foremanSig = $sigs->firstWhere('position', 'foreman');
                if (!$foremanSig || empty($foremanSig['signature'])) continue;

                // 1. TTD Seksi: Jika status GL Approved / Progress dan seksi belum TTD
                if (in_array($qpr->status, ['GL Approved', 'Progress'])) {
                    $seksiSig = $sigs->first(fn($s) => ($s['position'] ?? '') === 'seksi' && ($s['role'] ?? '') === $seksiName);
                    if ($seksiSig && empty($seksiSig['signature'])) {
                        $pending[] = [
                            'type'     => 'seksi',
                            'role'     => $seksiName,
                            'position' => 'seksi',
                            'qpr'      => $qpr,
                        ];
                        continue; // Masuk pending, lanjut ke QPR berikutnya
                    }
                }

                // 2. Action Lanjutan: Jika status Waiting Action X ATAU Waiting A3 Report dan pic_seksi sesuai dengan departemen user
                if (str_starts_with($qpr->status, 'Waiting Action') || $qpr->status === 'Waiting A3 Report') {
                    if ($qpr->pic_seksi === $seksiName) {
                        $pending[] = [
                            'type'     => 'seksi_action',
                            'role'     => $seksiName,
                            'position' => 'seksi',
                            'qpr'      => $qpr,
                        ];
                    }
                }
            }

            return response()->json($pending);
        }

        // POST /api/qprs/{id}/sign
        public function sign(Request $request, $id)
        {
            $request->validate([
                'signature' => 'required|string',
                'position'  => 'nullable|string|in:foreman,seksi',
            ]);

            $qpr      = QPR::findOrFail($id);
            $user     = $request->user();
            $position = $request->input('position', 'foreman');
            
            $sigData = $qpr->approval_signatures;
            $sigs = is_array($sigData) ? $sigData : (is_string($sigData) ? json_decode($sigData, true) ?? [] : []);

            if ($position === 'foreman') {
                // Hanya assigned foreman boleh TTD
                if ($qpr->assigned_foreman_id !== $user->id) {
                    return response()->json(['message' => 'Anda tidak berhak menandatangani QPR ini'], 403);
                }

                $sigs = array_map(function ($sig) use ($request, $user) {
                    if (($sig['position'] ?? '') === 'foreman') {
                        $sig['signature'] = $request->signature;
                        $sig['signed_at'] = now()->toISOString();
                        $sig['nama']      = $user->name;
                    }
                    return $sig;
                }, $sigs);

                // Status berubah ke GL Approved → seksi terkait bisa mulai TTD
                $qpr->status = 'GL Approved';

            } elseif ($position === 'seksi') {
                $seksiName = self::DEPT_TO_SEKSI[$user->department ?? ''] ?? null;
                if (!$seksiName) {
                    return response()->json(['message' => 'Departemen Anda tidak terdaftar sebagai Seksi Terkait'], 403);
                }

                $found = false;
                $sigs = array_map(function ($sig) use ($request, $user, $seksiName, &$found) {
                    if (($sig['position'] ?? '') === 'seksi' && ($sig['role'] ?? '') === $seksiName) {
                        $sig['signature'] = $request->signature;
                        $sig['signed_at'] = now()->toISOString();
                        $sig['nama']      = $user->name;
                        $found = true;
                    }
                    return $sig;
                }, $sigs);

                if (!$found) {
                    return response()->json(['message' => 'Slot TTD seksi Anda tidak ditemukan di QPR ini'], 404);
                }

                // Jika semua seksi sudah TTD → Progress (Wait Action 1)
                $allSeksiSigned = collect($sigs)
                    ->filter(fn($s) => ($s['position'] ?? '') === 'seksi')
                    ->every(fn($s) => !empty($s['signature']));

                if ($allSeksiSigned) {
                    $qprData = $qpr->toArray();
                    $qprData['status'] = 'Progress';
                    $state = $this->processStateMachine($qprData, $qpr->actions->toArray());
                    $qpr->status = $state['status'];
                    $qpr->is_a3_required = $state['is_a3_required'];
                }
            }

            // Directly assign the array, Laravel cast handles JSON conversion
            $qpr->approval_signatures = $sigs;
            $qpr->save();

            return response()->json([
                'message' => 'TTD berhasil disimpan',
                'status'  => $qpr->status,
            ]);
        }

            // GET /api/qprs/{id}/signatures
            public function signatures($id)
            {
                $tokens = ApprovalToken::where('qpr_id', $id)->get();
                return response()->json($tokens);
            }

            // POST /api/qprs/{id}/revision
            public function requestRevision(Request $request, $id)
            {
                $request->validate(['catatan_revisi' => 'required|string|max:500']);

                $qpr  = QPR::findOrFail($id);
                $user = $request->user();

                // Hanya assigned GL/Foreman boleh minta revisi
                if ($qpr->assigned_foreman_id !== $user->id) {
                    return response()->json(['message' => 'Anda tidak berhak meminta revisi pada QPR ini'], 403);
                }

                $qpr->status = 'Revision';
                $qpr->remark = $request->catatan_revisi;
                $qpr->save();

                return response()->json([
                    'message' => 'QPR dikembalikan untuk revisi',
                    'status'  => $qpr->status,
                ]);
            }

        }