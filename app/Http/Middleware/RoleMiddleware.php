<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!Auth::check()) {
            return redirect('/login');
        }

        $userRole = Auth::user()->role;
        $normalizedUserRole = strtolower($userRole);
        if (str_starts_with($normalizedUserRole, 'leader') || $normalizedUserRole === 'shearing' || $normalizedUserRole === 'handwork') {
            $normalizedUserRole = 'operator';
        }

        $normalizedRoles = array_map(function($role) {
            $r = strtolower($role);
            if (str_starts_with($r, 'leader') || $r === 'shearing' || $r === 'handwork') {
                return 'operator';
            }
            return $r;
        }, $roles);

        if (!in_array($normalizedUserRole, $normalizedRoles) && !in_array($userRole, $roles)) {
            abort(403, 'Unauthorized'); 
        }

        return $next($request);
    }
}