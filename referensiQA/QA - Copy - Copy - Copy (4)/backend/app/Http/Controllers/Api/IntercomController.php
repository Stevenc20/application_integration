<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IntercomCall;
use App\Models\LembarInspeksi;
use Illuminate\Http\Request;

class IntercomController extends Controller
{
    // Operator memulai panggilan ke GL atau Foreman
    public function initiateCall(Request $request)
    {
        $request->validate([
            'lembar_inspeksi_id' => 'required|exists:lembar_inspeksi,id',
            'role_type'          => 'required|in:gl,foreman',
            'assigned_user_id'   => 'required|exists:users,id',
        ]);

        $liId   = $request->lembar_inspeksi_id;
        $status = $request->role_type === 'gl' ? 'calling_gl' : 'calling_foreman';

        // Simpan penugasan di Lembar Inspeksi agar query active-incoming tepat
        $li = LembarInspeksi::find($liId);
        if ($li) {
            if ($request->role_type === 'gl') {
                $li->assigned_gl_id = $request->assigned_user_id;
            } else {
                $li->assigned_foreman_id = $request->assigned_user_id;
            }
            $li->save();
        }

        $call = IntercomCall::updateOrCreate(
            ['lembar_inspeksi_id' => $liId],
            [
                'status'        => $status,
                'responder_name'=> null,
                'response_msg'  => null,
                'arrived_at'    => null,
                'arrived_name'  => null,
                'called_at'     => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Panggilan interkom berhasil diinisiasi.',
            'data'    => $call,
        ]);
    }

    // Cek status panggilan aktif untuk suatu Lembar Inspeksi (Operator memantau layar)
    public function checkCallStatus($liId)
    {
        $call = IntercomCall::where('lembar_inspeksi_id', $liId)->first();

        return response()->json([
            'success' => true,
            'data'    => $call,
        ]);
    }

    // GL atau Foreman merespons panggilan (Terima / Tolak) — hanya dari device mereka
    public function respondCall(Request $request)
    {
        $request->validate([
            'lembar_inspeksi_id' => 'required|exists:lembar_inspeksi,id',
            'action'             => 'required|in:accept,decline',
            'responder_name'     => 'required|string',
            'message'            => 'nullable|string',
        ]);

        $call = IntercomCall::where('lembar_inspeksi_id', $request->lembar_inspeksi_id)->first();

        if (!$call) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada panggilan aktif ditemukan.',
            ], 422);
        }

        $call->status        = $request->action === 'accept' ? 'answered' : 'declined';
        $call->responder_name = $request->responder_name;
        $call->response_msg  = $request->message ?: ($request->action === 'accept' ? 'Dalam Perjalanan' : 'Sibuk/Tolak');
        $call->save();

        return response()->json([
            'success' => true,
            'message' => 'Panggilan berhasil direspons.',
            'data'    => $call,
        ]);
    }

    /**
     * GL/Foreman melakukan check-in fisik di tablet operator.
     * Ini hanya bisa dilakukan dari tablet operator (bukan device GL sendiri).
     * Setelah check-in, notif di device GL/Foreman akan padam secara otomatis.
     */
    public function arriveAtLine(Request $request)
    {
        $request->validate([
            'lembar_inspeksi_id' => 'required|exists:lembar_inspeksi,id',
            'arrived_name'       => 'required|string',
        ]);

        $call = IntercomCall::where('lembar_inspeksi_id', $request->lembar_inspeksi_id)->first();

        if (!$call) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada panggilan aktif ditemukan.',
            ], 422);
        }

        $call->status       = 'arrived';
        $call->arrived_at   = now();
        $call->arrived_name = $request->arrived_name;
        $call->save();

        return response()->json([
            'success' => true,
            'message' => $request->arrived_name . ' telah check-in di tablet operator.',
            'data'    => $call,
        ]);
    }

    // Menyelesaikan panggilan (reset status)
    public function completeCall($liId)
    {
        \Log::info('[INTERCOM] completeCall', ['liId' => $liId]);
        $call = IntercomCall::where('lembar_inspeksi_id', $liId)->first();

        if ($call) {
            $call->status = 'completed';
            $call->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Panggilan interkom diselesaikan.',
        ]);
    }

    /**
     * GL atau Foreman memantau panggilan masuk secara real-time (GET polling).
     * Digunakan oleh SEMUA halaman (global, bukan hanya form LI).
     * Status 'arrived' = GL sudah di tablet → overlay di device GL harus padam.
     */
    public function checkActiveIncoming(Request $request)
    {
        $userId = $request->query('user_id');
        $role   = strtolower($request->query('role', ''));

        if (!$userId || !$role) {
            return response()->json(['success' => false, 'data' => null, 'message' => 'Missing user_id or role']);
        }

        // \Log::info('[INTERCOM] checkActiveIncoming', [
        //     'user_id' => $userId,
        //     'role'    => $role,
        // ]);

        $call = null;

        if (str_contains($role, 'leader') || str_contains($role, 'gl') || str_contains($role, 'group')) {
            $call = IntercomCall::whereIn('status', ['calling_gl', 'answered', 'arrived'])
                ->whereHas('lembarInspeksi', function ($q) use ($userId) {
                    $q->where('assigned_gl_id', $userId);
                })
                ->with('lembarInspeksi')
                ->latest('called_at')
                ->first();
        } elseif (str_contains($role, 'foreman') || str_contains($role, 'fm')) {
            $call = IntercomCall::whereIn('status', ['calling_foreman', 'answered', 'arrived'])
                ->whereHas('lembarInspeksi', function ($q) use ($userId) {
                    $q->where('assigned_foreman_id', $userId);
                })
                ->with('lembarInspeksi')
                ->latest('called_at')
                ->first();
        }

        // \Log::info('[INTERCOM] checkActiveIncoming result', [
        //     'call' => $call ? $call->id : null,
        // ]);

        return response()->json([
            'success' => true,
            'data'    => $call,
        ]);
    }
}
