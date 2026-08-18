<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SessionFingerprint
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Hanya verifikasi jika session sudah dimulai dan user sudah login
        if ($request->hasSession() && Auth::check()) {
            $session = $request->session();
            
            // Buat fingerprint unik berdasarkan IP Address dan User-Agent (browser)
            $ip = $request->ip();
            $userAgent = $request->header('User-Agent');
            $fingerprint = sha1($ip . '_' . $userAgent);

            // Jika fingerprint belum ada di session (misal baru pertama kali login)
            if (!$session->has('session_fingerprint')) {
                $session->put('session_fingerprint', $fingerprint);
            } else {
                // Jika fingerprint di session tidak cocok dengan request saat ini
                if ($session->get('session_fingerprint') !== $fingerprint) {
                    // Force logout & hancurkan session (terdeteksi pembajakan / session hijacking)
                    Auth::logout();
                    $session->invalidate();
                    $session->regenerateToken();

                    // Respon error sesuai tipe request (API atau Web biasa)
                    if ($request->expectsJson() || $request->is('api/*')) {
                        return response()->json([
                            'error' => 'Unauthorized',
                            'message' => 'Session Hijacking Detected! Security fingerprint mismatch.'
                        ], 401);
                    }

                    return redirect()->route('login')->withErrors([
                        'session' => 'Sesi Anda tidak valid karena mendeteksi pergantian perangkat atau browser.'
                    ]);
                }
            }
        }

        return $next($request);
    }
}
