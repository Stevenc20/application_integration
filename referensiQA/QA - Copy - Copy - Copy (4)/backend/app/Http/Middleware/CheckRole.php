<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (! $user->is_active) {
            return response()->json(['message' => 'Akun Anda dinonaktifkan.'], 403);
        }

        // Support both `role:Admin,Leader` (single comma-separated string) and `role:Admin:Leader` (multiple args)
        $normalizedRoles = [];
        foreach ($roles as $r) {
            foreach (explode(',', $r) as $part) {
                $normalizedRoles[] = trim($part);
            }
        }

        if (! empty($normalizedRoles) && ! in_array($user->role, $normalizedRoles)) {
            return response()->json([
                'message'        => 'Anda tidak memiliki akses ke halaman ini.',
                'required_roles' => $roles,
                'your_role'      => $user->role,
            ], 403);
        }

        return $next($request);
    }
}