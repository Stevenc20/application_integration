<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    private const ROLE_LEVEL_MAP = [
        'presdir'    => 0,
        'direktur'   => 1,
        'kadiv'      => 2,
        'manager'    => 3,
        'supervisor' => 4,
        'foreman'    => 5,
        'leader'     => 6,
        'operator'   => 7,
    ];

    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!Auth::check()) {
            return redirect('/login');
        }

        $user = Auth::user();

        if (strtolower($user->role) === 'superadmin') {
            return $next($request);
        }

        $userRole = $user->role;
        $normalizedUserRole = strtolower($userRole);
        if (str_starts_with($normalizedUserRole, 'leader') || $normalizedUserRole === 'shearing' || $normalizedUserRole === 'handwork') {
            $normalizedUserRole = 'leader';
        }
        $hambatanRoles = ['dies_shop', 'plant_service', 'irm', 'logistik', 'produksi'];
        if (in_array($normalizedUserRole, $hambatanRoles)) {
            $normalizedUserRole = 'hambatan';
        }

        $normalizedRoles = array_map(function($role) {
            $r = strtolower($role);
            if (str_starts_with($r, 'leader') || $r === 'shearing' || $r === 'handwork') {
                return 'leader';
            }
            return $r;
        }, $roles);
        $normalizedRoles = array_map(fn($r) => in_array($r, $hambatanRoles) ? 'hambatan' : $r, $normalizedRoles);

        // If the route requires 'operator', we also allow 'leader'
        if (in_array('operator', $normalizedRoles) && $normalizedUserRole === 'leader') {
            return $next($request);
        }

        if (!in_array($normalizedUserRole, $normalizedRoles) && !in_array($userRole, $roles)) {
            // Position hierarchy check: allow higher positions to access lower role pages
            if (!$this->checkPositionHierarchy($user, $normalizedRoles)) {
                abort(403, 'Unauthorized');
            }
        }

        return $next($request);
    }

    private function checkPositionHierarchy($user, array $normalizedRoles): bool
    {
        if (!$user->position_id || !$user->position) {
            return false;
        }

        $userLevel = $user->position->level;

        $minRequiredLevel = null;
        foreach ($normalizedRoles as $role) {
            if (isset(self::ROLE_LEVEL_MAP[$role])) {
                $level = self::ROLE_LEVEL_MAP[$role];
                if ($minRequiredLevel === null || $level < $minRequiredLevel) {
                    $minRequiredLevel = $level;
                }
            }
        }

        if ($minRequiredLevel === null) {
            return false;
        }

        return $userLevel <= $minRequiredLevel;
    }
}
