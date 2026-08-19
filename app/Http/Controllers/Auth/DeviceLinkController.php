<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\DeviceLinkRequest;
use App\Services\DeviceLinkService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DeviceLinkController extends Controller
{
    public function __construct(
        protected DeviceLinkService $service
    ) {}

    /**
     * Create a new pairing request and return the QR payload (token).
     */
    public function create(Request $request)
    {
        try {
            $data = $this->service->createPairingRequest($request);
            
            // Generate full URL for the QR code
            $scanUrl = route('device_link.scan_page', ['token' => $data->token]);

            return response()->json([
                'success' => true,
                'token' => $data->token,
                'token_hash' => $data->token_hash,
                'scan_url' => $scanUrl,
                'expires_at' => $data->expires_at->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            Log::error('DeviceLink create error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal membuat QR Code'], 500);
        }
    }

    /**
     * Poll the pairing request status from the guest browser (Monitor).
     */
    public function status(Request $request, $tokenHash)
    {
        $this->service->expirePairingRequests();
        $sessionHash = hash('sha256', $request->session()->getId());

        $deviceLink = DeviceLinkRequest::where('token_hash', $tokenHash)->first();

        if (!$deviceLink || $deviceLink->client_session_id_hash !== $sessionHash) {
            return response()->json(['success' => false, 'message' => 'Pairing request tidak ditemukan.'], 404);
        }

        return response()->json([
            'success' => true,
            'status' => $deviceLink->status,
            'expires_at' => $deviceLink->expires_at->toIso8601String(),
        ]);
    }

    /**
     * Consume an approved pairing request from the browser that created it (Monitor).
     */
    public function consume(Request $request, $tokenHash)
    {
        try {
            $deviceLink = DeviceLinkRequest::where('token_hash', $tokenHash)->firstOrFail();
            $user = $this->service->consumePairingRequest($deviceLink, $request);

            $redirectUrl = match($user->role) {
                'admin' => route('admin.dashboard'),
                'supervisor' => route('supervisor.dashboard'),
                'foreman' => route('supervisor.dashboard'),
                'operator' => route('operator.dashboard'),
                'leader a', 'leader b', 'leader c', 'leader d', 'leader', 'shearing', 'handwork' => route('operational.input_harian'),
                'ppc' => route('ppc.dashboard'),
                'quality' => route('quality.dashboard'),
                'production' => route('production.dashboard'),
                'manager' => route('manager.dashboard'),
                'kadiv' => route('kadiv.dashboard'),
                'direktur' => route('direktur.dashboard'),
                'presdir' => route('presdir.dashboard'),
                'superadmin' => route('super-admin.dashboard'),
                'dies_shop', 'plant_service', 'irm', 'logistik', 'produksi', 'hambatan' => route('hambatan-jalur.index'),
                default => url('/')
            };

            return response()->json([
                'success' => true,
                'message' => 'Login berhasil',
                'redirect_url' => $redirectUrl,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('DeviceLink consume error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * GET: Show the scanner interface to the authenticated HP user.
     */
    public function scanner()
    {
        return view('auth.device-link-scanner');
    }

    /**
     * GET: Show the confirmation page for the authenticated HP user.
     */
    public function showScanPage(Request $request)
    {
        $token = $request->query('token');
        if (!$token) {
            return redirect('/')->with('error', 'Token QR tidak ditemukan.');
        }

        try {
            // Validate the token and update status to scanned
            $model = $this->service->scanPairingRequest($token, $request->user());

            return view('auth.device-link-approve', [
                'tokenHash' => $model->token_hash,
                'expiresAt' => $model->expires_at,
                'device' => $this->deviceSummary($model),
            ]);
        } catch (\Exception $e) {
            return redirect('/')->with('error', $e->getMessage());
        }
    }

    /**
     * POST: Approve a scanned pairing request from the HP confirmation dialog.
     */
    public function approve(Request $request, $tokenHash)
    {
        try {
            $deviceLink = DeviceLinkRequest::where('token_hash', $tokenHash)->firstOrFail();
            $this->service->approvePairingRequest($deviceLink, $request->user());

            return response()->json(['success' => true, 'message' => 'Perangkat berhasil ditautkan.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * POST: Cancel a pairing request (from HP).
     */
    public function cancel(Request $request, $tokenHash)
    {
        try {
            $deviceLink = DeviceLinkRequest::where('token_hash', $tokenHash)->firstOrFail();
            $this->service->cancelPairingRequest($deviceLink, $request->user());

            return response()->json(['success' => true, 'message' => 'Permintaan dibatalkan.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    protected function deviceSummary(DeviceLinkRequest $model): array
    {
        $ua = $model->user_agent;
        $browser = 'Peramban';
        $os = 'Sistem Operasi';

        if ($ua) {
            if (preg_match('/Edg\/([\d.]+)/i', $ua)) $browser = 'Microsoft Edge';
            elseif (preg_match('/OPR\/([\d.]+)/i', $ua)) $browser = 'Opera';
            elseif (preg_match('/Chrome\/([\d.]+)/i', $ua)) $browser = 'Chrome';
            elseif (preg_match('/Firefox\/([\d.]+)/i', $ua)) $browser = 'Firefox';
            elseif (preg_match('/Safari\/([\d.]+)/i', $ua)) $browser = 'Safari';

            if (preg_match('/Windows/i', $ua)) $os = 'Windows';
            elseif (preg_match('/Android/i', $ua)) $os = 'Android';
            elseif (preg_match('/iPhone|iPad|iOS/i', $ua)) $os = 'iOS';
            elseif (preg_match('/Mac OS X/i', $ua)) $os = 'macOS';
            elseif (preg_match('/Linux/i', $ua)) $os = 'Linux';
        }

        return ['browser' => $browser, 'os' => $os];
    }
}
