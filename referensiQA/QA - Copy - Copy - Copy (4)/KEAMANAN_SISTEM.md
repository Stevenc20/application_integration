# Dokumentasi Keamanan Sistem QA (Quality Assurance)
Dokumen ini menjelaskan fitur keamanan yang diterapkan pada sistem Quality Assurance (QA) untuk melindungi aplikasi dari berbagai ancaman siber, khususnya **Session Hijacking** (pencurian cookie) dan **SQL Injection**.

---

## 1. Perlindungan Session Hijacking (Session Fingerprinting)
Fitur ini mencegah hacker menggunakan cookie `laravel_session` yang disalin secara ilegal dari browser pengguna asli ke browser lain (pembajakan sesi).

### Lokasi File:
`app/Http/Middleware/SessionFingerprint.php`

### Cara Kerja Kode & Penjelasan Komentar:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SessionFingerprint
{
    /**
     * Mengatur setiap request masuk untuk diperiksa keamanannya.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. KONDISI AWAL:
        // Kita hanya memeriksa jika session web aktif dan user sudah berhasil login (terautentikasi).
        if ($request->hasSession() && Auth::check()) {
            $session = $request->session();
            
            // 2. PEMBUATAN SIDIK JARI (FINGERPRINT):
            // Kita mengambil IP Address ($request->ip()) dan jenis browser/perangkat ($request->header('User-Agent')).
            // Data ini kemudian digabungkan dan di-hash menggunakan algoritma SHA1 agar menjadi string unik.
            $ip = $request->ip();
            $userAgent = $request->header('User-Agent');
            $fingerprint = sha1($ip . '_' . $userAgent);

            // 3. REGISTRASI PERTAMA KALI:
            // Jika user baru login, sidik jari ini belum ada di session. 
            // Kita simpan sidik jari tersebut ke dalam session server dengan nama 'session_fingerprint'.
            if (!$session->has('session_fingerprint')) {
                $session->put('session_fingerprint', $fingerprint);
            } else {
                // 4. DETEKSI PEMBAJAKAN:
                // Jika sidik jari yang disimpan di server BERBEDA dengan sidik jari request saat ini,
                // berarti cookie ini telah disalin dan digunakan di browser/perangkat lain!
                if ($session->get('session_fingerprint') !== $fingerprint) {
                    
                    // TINDAKAN PENCEGAHAN:
                    Auth::logout(); // Logout paksa dari sistem
                    $session->invalidate(); // Hancurkan data session di server
                    $session->regenerateToken(); // Hancurkan token CSRF agar tidak bisa digunakan lagi

                    // Jika request datang dari API (Vite/AJAX), kirim respon JSON Error 401
                    if ($request->expectsJson() || $request->is('api/*')) {
                        return response()->json([
                            'error' => 'Unauthorized',
                            'message' => 'Session Hijacking Detected! Security fingerprint mismatch.'
                        ], 401);
                    }

                    // Jika request dari web biasa, tendang ke login dengan pesan error
                    return redirect()->route('login')->withErrors([
                        'session' => 'Sesi Anda tidak valid karena mendeteksi pergantian perangkat atau browser.'
                    ]);
                }
            }
        }

        // Jika semua aman, teruskan request ke halaman yang dituju
        return $next($request);
    }
}
```

---

## 2. Fitur Keamanan Lain pada Sistem QA

Selain Session Fingerprinting, berikut adalah pilar keamanan bawaan yang aktif pada aplikasi Anda:

| Fitur Keamanan | Deskripsi & Fungsi | Penerapan di Proyek |
| :--- | :--- | :--- |
| **HttpOnly Cookies** | Mencegah kode JavaScript jahat (serangan XSS) membaca cookie `laravel_session`. Hacker jarak jauh tidak bisa mencuri cookie Anda lewat script web. | Aktif secara otomatis di `config/session.php` (`'http_only' => true`). |
| **SQL Injection Prevention** | Mengamankan query database agar input dari form tidak bisa dimanipulasi dengan perintah SQL berbahaya. | Laravel Eloquent ORM secara otomatis menggunakan **PDO Parameter Binding** (mengubah input menjadi teks biasa, bukan kode SQL). |
| **CSRF Protection** | Mencegah serangan *Cross-Site Request Forgery* (pemalsuan permintaan dari situs lain). | Setiap form input menggunakan `@csrf` dan divalidasi oleh `VerifyCsrfToken` middleware. |
| **Role-Based Access Control (RBAC)** | Membatasi hak akses halaman berdasarkan peran pengguna (Admin, Leader, Supervisor, Operator). | Menggunakan package `spatie/laravel-permission` yang divalidasi lewat `RoleMiddleware.php`. |

---

## 3. Skenario Demonstrasi untuk Penguji / Dosen

Anda dapat mendemonstrasikan keandalan keamanan ini dengan langkah berikut:
1. Login ke aplikasi di browser **Chrome**.
2. Buka DevTools (F12) -> Application -> Cookies -> salin nilai `laravel_session`.
3. Buka browser **Edge / Firefox** (atau Incognito), buka aplikasi Anda.
4. Paste cookie `laravel_session` tersebut ke Cookies di Edge/Firefox.
5. Lakukan **Refresh (F5)**.
6. **Hasil:** Sistem akan mendeteksi perbedaan browser (User-Agent mismatch), langsung menghapus session tersebut di server, dan me-redirect Anda ke halaman login dengan status ter-logout di kedua browser tersebut.
