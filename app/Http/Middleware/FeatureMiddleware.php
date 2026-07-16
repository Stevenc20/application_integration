<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FeatureMiddleware
{
    public function handle(Request $request, Closure $next, string $featureCode)
    {
        if (!Auth::check()) {
            return redirect('/login');
        }

        $user = Auth::user();

        if (strtolower($user->role) === 'superadmin') {
            return $next($request);
        }

        $role = strtolower($user->role);
        if (str_starts_with($role, 'leader') || $role === 'shearing' || $role === 'handwork') {
            $role = 'leader';
        }
        $hambatanRoles = ['dies_shop', 'plant_service', 'irm', 'logistik', 'produksi'];
        if (in_array($role, $hambatanRoles)) {
            $role = 'hambatan';
        }

        $hasFeature = \App\Models\RoleFeature::where('role', $role)
            ->whereHas('feature', function ($q) use ($featureCode) {
                $q->where('feature_code', $featureCode);
            })
            ->where('enabled', true)
            ->exists();

        if (!$hasFeature) {
            abort(403, 'Feature access denied.');
        }

        return $next($request);
    }
}
