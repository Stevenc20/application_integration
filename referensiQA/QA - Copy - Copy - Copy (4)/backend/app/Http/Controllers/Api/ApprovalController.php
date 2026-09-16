<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApprovalToken;
use App\Models\Qpr;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ApprovalController extends Controller
{
    // POST /api/qprs/{id}/generate-tokens
    // Dipanggil setelah QPR disubmit — generate token per approver
    public function generateTokens(Request $request, $id)
    {
        $qpr = Qpr::findOrFail($id);

        // Ambil approval_signatures dari QPR
        $signatures = is_string($qpr->approval_signatures)
            ? json_decode($qpr->approval_signatures, true)
            : $qpr->approval_signatures;

        if (empty($signatures)) {
            return response()->json(['message' => 'Tidak ada approver'], 400);
        }

        DB::beginTransaction();
        try {
            $tokens = [];

            // Hapus token lama kalau ada
            ApprovalToken::where('qpr_id', $id)->delete();

            foreach ($signatures as $signer) {
                $token = Str::random(32);
                ApprovalToken::create([
                    'qpr_id'  => $id,
                    'token'   => $token,
                    'role'    => $signer['role'],
                    'nama'    => $signer['nama'] ?? null,
                    'is_used' => false,
                ]);
                $tokens[] = [
                    'role'  => $signer['role'],
                    'token' => $token,
                    'url'   => "http://localhost:5173/sign/{$token}",
                ];
            }

            DB::commit();
            return response()->json(['message' => 'Token berhasil dibuat', 'tokens' => $tokens]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal generate token', 'error' => $e->getMessage()], 500);
        }
    }

    // GET /api/sign/{token}
    // Dibuka dari QR — ambil info QPR untuk ditampilkan
    public function getByToken($token)
    {
        $approval = ApprovalToken::with('qpr')->where('token', $token)->first();

        if (!$approval) {
            return response()->json(['message' => 'Token tidak valid'], 404);
        }

        if ($approval->is_used) {
            return response()->json(['message' => 'Token sudah digunakan', 'signed_at' => $approval->signed_at], 410);
        }

        return response()->json([
            'role'      => $approval->role,
            'nama'      => $approval->nama,
            'qpr'       => [
                'no_qpr'    => $approval->qpr->no_qpr,
                'nama_part' => $approval->qpr->nama_part,
                'tanggal'   => $approval->qpr->tanggal,
                'defect'    => $approval->qpr->defect,
            ],
        ]);
    }

    // POST /api/sign/{token}
    // Approver submit TTD
    public function sign(Request $request, $token)
    {
        $approval = ApprovalToken::with('qpr')->where('token', $token)->first();

        if (!$approval) {
            return response()->json(['message' => 'Token tidak valid'], 404);
        }

        if ($approval->is_used) {
            return response()->json(['message' => 'Token sudah digunakan'], 410);
        }

        $request->validate([
            'nama'      => 'required|string|max:255',
            'signature' => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            // Simpan TTD di approval_tokens
            $approval->update([
                'nama'      => $request->nama,
                'signature' => $request->signature,
                'is_used'   => true,
                'signed_at' => now(),
            ]);

            // Update approval_signatures di tabel qprs
            $qpr = $approval->qpr;
            $sigs = is_string($qpr->approval_signatures)
                ? json_decode($qpr->approval_signatures, true)
                : ($qpr->approval_signatures ?? []);

            $sigs = array_map(function ($s) use ($approval, $request) {
                if ($s['role'] === $approval->role) {
                    $s['nama']      = $request->nama;
                    $s['signature'] = $request->signature;
                }
                return $s;
            }, $sigs);

            $qpr->update(['approval_signatures' => json_encode($sigs)]);

            DB::commit();
            return response()->json(['message' => 'Tanda tangan berhasil disimpan']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal menyimpan TTD', 'error' => $e->getMessage()], 500);
        }
    }
    public function getTokens($id)
    {
        $tokens = ApprovalToken::where('qpr_id', $id)->get()->map(function($t) {
            return [
                'role'      => $t->role,
                'nama'      => $t->nama,
                'is_used'   => $t->is_used,
                'signed_at' => $t->signed_at,
                'url'       => "http://localhost:5173/sign/{$t->token}",
            ];
        });
        return response()->json(['tokens' => $tokens]);
    }
}