<?php

namespace App\Http\Controllers;

use App\Models\HambatanJalur;
use App\Models\RepairRejectLog;
use App\Models\User;
use App\Notifications\HambatanJalurSigned;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class HambatanJalurController extends Controller
{
    private function roleToJenis(): ?string
    {
        $map = [
            'dies_shop' => 'DT',
            'plant_service' => 'MT',
            'irm' => 'MST',
            'logistik' => 'LOGT',
            'produksi' => 'Prot',
        ];
        return $map[strtolower(Auth::user()->role ?? '')] ?? null;
    }

    public function index()
    {
        $jenis = $this->roleToJenis();
        $statusFilter = request('status');

        $query = HambatanJalur::with('downtime.jobMaster');

        if ($jenis) {
            $query->where('jenis_hambatan', $jenis);
        }

        if ($statusFilter && in_array($statusFilter, ['open', 'pic_signed', 'signed'])) {
            $query->where('status', $statusFilter);
        }

        $items = $query->orderByRaw("CASE status WHEN 'open' THEN 0 WHEN 'pic_signed' THEN 1 ELSE 2 END")
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        // Counts for badges
        $counts = [];
        $baseQuery = $jenis ? HambatanJalur::where('jenis_hambatan', $jenis) : HambatanJalur::query();
        $counts['open'] = (clone $baseQuery)->where('status', 'open')->count();
        $counts['pic_signed'] = (clone $baseQuery)->where('status', 'pic_signed')->count();
        $counts['signed'] = (clone $baseQuery)->where('status', 'signed')->count();

        $currentTab = $statusFilter ?: 'all';

        return view('hambatan-jalur.index', compact('items', 'jenis', 'counts', 'currentTab'));
    }

    public function show($id)
    {
        $item = HambatanJalur::with('downtime.jobMaster', 'signer', 'leaderSigner')->findOrFail($id);

        $allowed = $this->roleToJenis();
        if ($allowed && $item->jenis_hambatan !== $allowed) {
            abort(403);
        }

        $isLineLeader = $this->isLineLeader($item);

        $repairRejectLogs = collect();
        if ($item->downtime && $item->downtime->job_master_id) {
            $repairRejectLogs = RepairRejectLog::where('job_master_id', $item->downtime->job_master_id)->get();
        }

        return view('hambatan-jalur.show', compact('item', 'isLineLeader', 'repairRejectLogs'));
    }

    public function sign($id, Request $request)
    {
        $item = HambatanJalur::findOrFail($id);

        $allowed = $this->roleToJenis();
        if ($allowed && $item->jenis_hambatan !== $allowed) {
            return back()->with('error', 'Anda tidak berhak menandatangani hambatan ini.');
        }

        if ($item->status === 'signed' && $item->signature_image) {
            return back()->with('error', 'Sudah ditandatangani.');
        }

        $updateData = [
            'problem' => $request->input('problem', $item->problem),
            'penyebab' => $request->input('penyebab', $item->penyebab),
            'penanggulangan' => $request->input('penanggulangan', $item->penanggulangan),
            'signed_at' => now(),
            'signed_by' => Auth::id(),
            'status' => 'pic_signed',
        ];

        if ($request->has('signature_image')) {
            $updateData['signature_image'] = $request->input('signature_image');
        }

        $item->update($updateData);

        // Notify line leader
        $notified = $this->notifyLineLeader($item);

        if ($notified) {
            session()->flash('success', 'Tanda tangan PIC berhasil. Notifikasi telah dikirim ke leader.');
        } else {
            session()->flash('warning', 'Tanda tangan PIC berhasil. Namun tidak ada leader/foreman yang terdaftar untuk line ' . ($item->line_name ?? '-') . '. Hubungi supervisor untuk tindak lanjut.');
        }

        return redirect()->route('hambatan-jalur.show', $item->id);
    }

    public function leaderSign($id, Request $request)
    {
        $item = HambatanJalur::findOrFail($id);

        if ($item->status !== 'pic_signed') {
            return back()->with('error', 'Status tidak valid. PIC harus menandatangani terlebih dahulu.');
        }

        if (!$this->isLineLeader($item)) {
            return back()->with('error', 'Anda bukan leader line ini.');
        }

        $request->validate([
            'leader_signature_image' => 'required|string',
        ]);

        $item->update([
            'status' => 'signed',
            'leader_signature_image' => $request->input('leader_signature_image'),
            'leader_signed_at' => now(),
            'leader_signed_by' => Auth::id(),
        ]);

        // Mark all HambatanJalurSigned notifications for this hambatan as read
        auth()->user()->unreadNotifications()
            ->where('type', \App\Notifications\HambatanJalurSigned::class)
            ->where('data->hambatan_id', $item->id)
            ->get()->each->markAsRead();

        return redirect()->route('hambatan-jalur.show', $item->id)->with('success', 'Tanda tangan leader berhasil. Laporan selesai.');
    }

    private function isLineLeader(HambatanJalur $hambatan): bool
    {
        $letter = trim(str_ireplace('press', '', $hambatan->line_name ?? ''));
        $roleName = 'leader ' . strtolower($letter);

        $userRole = strtolower(Auth::user()->role ?? '');
        if ($userRole === $roleName) {
            return true;
        }

        // Foreman can also sign
        if ($userRole === 'foreman') {
            return true;
        }

        return false;
    }

    public function notifyLineLeader(HambatanJalur $hambatan): bool
    {
        if (!$hambatan->line_name) {
            Log::warning('notifyLineLeader: line_name is null', [
                'hambatan_id' => $hambatan->id,
            ]);
            return false;
        }

        $letter = trim(str_ireplace('press', '', $hambatan->line_name));
        $roleName = 'leader ' . strtolower($letter);

        $leaders = User::where('role', $roleName)->get();

        if ($leaders->isNotEmpty()) {
            foreach ($leaders as $leader) {
                $leader->notify(new HambatanJalurSigned($hambatan));
            }
            return true;
        }

        // Fallback to foreman
        $foremen = User::where('role', 'foreman')->get();
        if ($foremen->isNotEmpty()) {
            foreach ($foremen as $foreman) {
                $foreman->notify(new HambatanJalurSigned($hambatan));
            }
            return true;
        }

        Log::warning('notifyLineLeader: no leader/foreman user found by role', [
            'hambatan_id' => $hambatan->id,
            'line_name'   => $hambatan->line_name,
            'role_needed' => $roleName,
        ]);

        return false;
    }
}
