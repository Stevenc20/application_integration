<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use App\Models\User;

class AuthController extends Controller
{
    // ======================
    // LOGIN PAGE
    // ======================
    public function login()
    {
        return view('auth.login');
    }

    // ======================
    // LOGIN PROCESS
    // ======================
    public function loginProcess(Request $request)
    {
        $request->validate([
            'nrp' => 'required',
            'password' => 'required'
        ]);

        $user = User::where('nrp', $request->nrp)
                    ->where('is_active', 1)
                    ->first();

        if (!$user) {
            return back()->with('error', 'User tidak ditemukan / tidak aktif');
        }

        if (!Hash::check($request->password, $user->password)) {
            return back()->with('error', 'Password salah');
        }

        Auth::login($user);
        $request->session()->regenerate();

        // Clear RateLimiter throttle keys on successful login
        $throttleKey = Str::lower($request->input('nrp')) . '|' . $request->ip();
        RateLimiter::clear($throttleKey);
        RateLimiter::clear('login:' . $request->ip());

        // ======================
        // REDIRECT ROLE CLEAN
        // ======================
        if ($user->isSuperadmin()) {
            return redirect()->route('super-admin.dashboard');
        }
        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        $pos = strtolower($user->position ? $user->position->position_name : '');
        $sec = strtolower($user->section ? $user->section->section_name : '');
        $legacyRole = strtolower($user->role ?? '');

        // Section-based redirects FIRST (PPC/Quality/Production need their dashboards).
        // RoleMiddleware grants these routes to users whose SECTION matches the module
        // keyword, so redirecting by section is safe (no 403). 'Incoming Quality' (IRM)
        // must NOT match the quality module, hence the 'process quality' check.
        if (str_contains($sec, 'ppc') || $legacyRole === 'ppc') return redirect()->route('ppc.dashboard');
        if (str_contains($sec, 'process quality') || $legacyRole === 'quality') return redirect()->route('quality.dashboard');
        if (str_contains($sec, 'produksi') || str_contains($sec, 'production') || $legacyRole === 'production') return redirect()->route('production.dashboard');

        if ($pos === 'tim member' || $legacyRole === 'operator') {
            return redirect()->route('operator.dashboard');
        } elseif ($pos === 'leader' || str_starts_with($legacyRole, 'leader') || in_array($legacyRole, ['shearing', 'handwork'])) {
            return redirect()->route('operational.input_harian');
        } elseif ($pos === 'foreman' || $legacyRole === 'foreman') {
            return redirect()->route('supervisor.dashboard');
        } elseif ($pos === 'spv' || $legacyRole === 'supervisor') {
            return redirect()->route('supervisor.dashboard');
        } elseif ($pos === 'manager' || $legacyRole === 'manager') {
            return redirect()->route('manager.dashboard');
        } elseif ($pos === 'kadiv' || $legacyRole === 'kadiv') {
            return redirect()->route('kadiv.dashboard');
        } elseif ($pos === 'direktur' || $legacyRole === 'direktur') {
            return redirect()->route('direktur.dashboard');
        } elseif ($pos === 'presdir' || $legacyRole === 'presdir') {
            return redirect()->route('presdir.dashboard');
        }
        
        // Hambatan Jalur fallback
        $hambatanRoles = ['dies_shop', 'plant_service', 'irm', 'logistik', 'produksi', 'hambatan', 'mesin'];
        if (in_array($legacyRole, $hambatanRoles) || in_array($sec, $hambatanRoles)) {
            return redirect()->route('hambatan-jalur.index');
        }

        return redirect('/');
    }

    // ======================
    // LOGOUT
    // ======================
    public function logout(Request $request)
    {
        RateLimiter::clear('login:' . $request->ip());

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}