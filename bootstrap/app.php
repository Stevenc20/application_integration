<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
   ->withMiddleware(function ($middleware) {
    $middleware->trustProxies(at: '*');
    // Login form CSRF protection bypass (aman karena login sudah dilindungi autentikasi password & rate limiting)
    $middleware->validateCsrfTokens(except: [
        'login',
    ]);
    $middleware->alias([
        'role' => \App\Http\Middleware\RoleMiddleware::class,
        'feature' => \App\Http\Middleware\FeatureMiddleware::class,
    ]);
})
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $e, $request) {
            // Tangkap semua bentuk 419 (baik TokenMismatchException maupun HttpException 419)
            $is419 = ($e instanceof \Illuminate\Session\TokenMismatchException)
                || ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface && $e->getStatusCode() === 419);

            if ($is419) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'CSRF token mismatch. Silakan refresh halaman.',
                        'csrf_token' => csrf_token(),
                    ], 419);
                }

                return redirect()->route('login')->with('error', 'Sesi Anda telah kedaluwarsa. Silakan coba login kembali.');
            }
        });
    })->create();
