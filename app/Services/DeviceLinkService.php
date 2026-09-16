<?php

namespace App\Services;

use App\Models\DeviceLinkRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class DeviceLinkService
{
    public function createPairingRequest(Request $request): DeviceLinkRequest
    {
        $this->expirePairingRequests();

        $tokenStr = Str::random(64);
        $tokenHash = hash('sha256', $tokenStr);
        $sessionHash = hash('sha256', $request->session()->getId());

        $model = DeviceLinkRequest::create([
            'token_hash' => $tokenHash,
            'client_session_id_hash' => $sessionHash,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'expires_at' => now()->addMinutes(5),
            'status' => 'pending',
        ]);

        $model->token = $tokenStr; // attach for returning to client
        return $model;
    }

    public function scanPairingRequest(string $token, User $user): DeviceLinkRequest
    {
        $this->expirePairingRequests();

        $tokenHash = hash('sha256', $token);
        $model = DeviceLinkRequest::where('token_hash', $tokenHash)->first();

        if (!$model) {
            throw new \Exception('Kode QR tidak valid atau tidak ditemukan.', 404);
        }

        if ($model->status === 'expired' || $model->expires_at->isPast()) {
            throw new \Exception('Kode QR sudah kedaluwarsa. Silakan muat ulang halaman login di perangkat tersebut.', 400);
        }

        if ($model->status !== 'pending' && $model->status !== 'scanned') {
            throw new \Exception('Kode QR sudah digunakan atau dibatalkan.', 400);
        }

        $model->update([
            'status' => 'scanned',
            'user_id' => $user->id,
        ]);

        return $model;
    }

    public function approvePairingRequest(DeviceLinkRequest $model, User $user): void
    {
        if ($model->user_id !== $user->id) {
            throw new \Exception('Unauthorized', 403);
        }

        if ($model->status !== 'scanned') {
            throw new \Exception('Status request tidak valid.', 400);
        }

        $model->update([
            'status' => 'approved',
        ]);
    }

    public function cancelPairingRequest(DeviceLinkRequest $model, User $user = null): void
    {
        if ($user && $model->user_id !== $user->id) {
            throw new \Exception('Unauthorized', 403);
        }

        $model->update([
            'status' => 'cancelled',
        ]);
    }

    public function consumePairingRequest(DeviceLinkRequest $model, Request $request): User
    {
        $sessionHash = hash('sha256', $request->session()->getId());

        if ($model->client_session_id_hash !== $sessionHash) {
            throw new \Exception('Session mismatch', 403);
        }

        if ($model->status !== 'approved') {
            throw new \Exception('Request belum disetujui', 400);
        }

        $user = $model->user;

        if (!$user) {
            throw new \Exception('Pengguna tidak ditemukan', 404);
        }

        $model->update([
            'status' => 'consumed',
            'consumed_at' => now(),
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return $user;
    }

    public function expirePairingRequests(): void
    {
        DeviceLinkRequest::where('status', 'pending')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);
            
        DeviceLinkRequest::where('status', 'scanned')
            ->where('expires_at', '<', now()->subMinutes(10))
            ->update(['status' => 'expired']);
    }
}
