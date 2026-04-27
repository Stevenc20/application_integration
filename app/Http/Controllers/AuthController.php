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
        return view('auth.login');
    }

    // ======================
    // LOGIN PROCESS
    // ======================
    public function loginProcess(Request $request)
    {
        $request->validate([
            'nip' => 'required',
            'password' => 'required'
        ]);

        $user = User::where('nip', $request->nip)
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
        return match($user->role) {

            'admin' => redirect()->route('admin.dashboard'),

            'supervisor' => redirect()->route('supervisor.dashboard'),

            'foreman' => redirect()->route('foreman.dashboard'),

            'operator' => redirect()->route('operator.dashboard'),

            'ppic' => redirect()->route('ppic.dashboard'),

            'quality' => redirect()->route('quality.dashboard'),

            'production' => redirect()->route('production.dashboard'),

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

        return redirect('/login');
    }
}