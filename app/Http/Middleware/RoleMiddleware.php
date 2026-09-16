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

        // 1. Superadmin overrides everything
        if ($user->isSuperadmin()) {
            return $next($request);
        }

        $normalizedRoles = array_map('strtolower', $roles);

        // 1b. Admin role
        if ($user->isAdmin() && in_array('admin', $normalizedRoles)) {
            return $next($request);
        }

        // 1c. Section-based access for module dashboards (PPC / Quality / Production).
        //     role:ppc has no ROLE_LEVEL_MAP entry, so position-hierarchy fallback
        //     would 403 users whose section is the module but whose role string differs.
        $sectionKeywordMap = [
            'ppc'        => ['ppc'],
            'quality'    => ['process quality'],
            'production' => ['produksi', 'production'],
        ];
        foreach ($normalizedRoles as $requiredRole) {
            $keywords = $sectionKeywordMap[$requiredRole] ?? [];
            if (empty($keywords)) {
                continue;
            }
            $section = strtolower($user->section ? $user->section->section_name : '');
            if ($section !== '' && $this->sectionMatchesAny($section, $keywords)) {
                return $next($request);
            }
        }

        // 2. Legacy Role logic
        $userRole = $user->role;
        $normalizedUserRole = strtolower($userRole);
        if (str_starts_with($normalizedUserRole, 'leader') || $normalizedUserRole === 'shearing' || $normalizedUserRole === 'handwork') {
            $normalizedUserRole = 'leader';
        }
        $hambatanRoles = ['dies_shop', 'plant_service', 'irm', 'logistik', 'produksi'];
        if (in_array($normalizedUserRole, $hambatanRoles)) {
            $normalizedUserRole = 'hambatan';
        }

        $normalizedRoles = array_map(function($role) use ($hambatanRoles) {
            $r = strtolower($role);
            if (str_starts_with($r, 'leader') || $r === 'shearing' || $r === 'handwork') {
                return 'leader';
            }
            if (in_array($r, $hambatanRoles)) {
                return 'hambatan';
            }
            return $r;
        }, $roles);

        // If the route requires 'operator', we also allow 'leader'
        if (in_array('operator', $normalizedRoles) && $normalizedUserRole === 'leader') {
            return $next($request);
        }

        // Direct match
        if (!in_array($normalizedUserRole, $normalizedRoles) && !in_array($userRole, $roles)) {
            // Position hierarchy check
            if (!$this->checkPositionHierarchy($user, $normalizedRoles)) {
                abort(403, 'Unauthorized');
            }
        }

        return $next($request);
    }

    private function sectionMatchesAny(string $section, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if (str_contains($section, $keyword)) {
                return true;
            }
        }
        return false;
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

        // Lower number is higher rank in target hierarchy: 
        // Wait, target hierarchy: Tim Member = 1, Presdir = 8.
        // If Presdir = 8, higher level means more access.
        // Wait! The user's new target hierarchy:
        // Tim Member (1), Leader (2), Foreman (3), SPV (4), Manager (5), Kadiv (6), Direktur (7), Presdir (8).
        // Let's rewrite checkPositionHierarchy to use the new canonical level logically.
        
        // Let's map required role to new canonical levels
        $targetMinLevel = null;
        foreach ($normalizedRoles as $role) {
            $reqLevel = null;
            switch ($role) {
                case 'operator': $reqLevel = 1; break;
                case 'leader': $reqLevel = 2; break;
                case 'foreman': $reqLevel = 3; break;
                case 'supervisor': $reqLevel = 4; break;
                case 'manager': $reqLevel = 5; break;
                case 'kadiv': $reqLevel = 6; break;
                case 'direktur': $reqLevel = 7; break;
                case 'presdir': $reqLevel = 8; break;
            }
            if ($reqLevel !== null) {
                if ($targetMinLevel === null || $reqLevel < $targetMinLevel) {
                    $targetMinLevel = $reqLevel;
                }
            }
        }

        if ($targetMinLevel === null) return false;

        return $userLevel >= $targetMinLevel;
    }
}
