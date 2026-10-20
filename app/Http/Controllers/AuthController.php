<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    // ======================
    // LOGIN PAGE
    // ======================
    public function login()
    {
        $response = response()->view('auth.login');
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0');
        $response->headers->set('Pragma', 'no-cache');
        return $response;
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

        // ======================
        // REDIRECT ROLE CLEAN
        // ======================
        $roleStr = strtolower(trim($user->role ?? ''));

        return match($roleStr) {

            'admin' => redirect()->route('admin.dashboard'),

            'supervisor' => redirect()->route('supervisor.dashboard'),

            'foreman' => redirect()->route('operational.input_harian'),

            'operator' => redirect()->route('operator.dashboard'),

            'leader a', 'leader b', 'leader c', 'leader d', 'leader' => redirect()->route('operational.input_harian'),

            'shearing', 'handwork' => redirect()->route('supervisor.dashboard'),

            'ppc' => redirect()->route('ppc.dashboard'),

            'quality' => redirect()->route('quality.dashboard'),

            'production' => redirect()->route('production.dashboard'),

            'manager' => redirect()->route('manager.dashboard'),

            'kadiv' => redirect()->route('kadiv.dashboard'),

            'direktur' => redirect()->route('direktur.dashboard'),

            'presdir' => redirect()->route('presdir.dashboard'),

            'superadmin' => redirect()->route('super-admin.dashboard'),

            'dies_shop', 'plant_service', 'irm', 'logistik', 'produksi', 'hambatan' => redirect()->route('hambatan-jalur.index'),

            default => redirect('/login')
        };
    }

    // ======================
    // LOGOUT
    // ======================
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $response = redirect('/login');
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0');
        $response->headers->set('Pragma', 'no-cache');
        return $response;
    }
}