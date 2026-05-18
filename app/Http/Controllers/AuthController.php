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
        return match($user->role) {

            'admin' => redirect()->route('admin.dashboard'),

            'supervisor' => redirect()->route('supervisor.dashboard'),

            'foreman' => redirect()->route('foreman.dashboard'),

            'operator', 'leader a', 'leader b', 'leader c', 'leader d', 'shearing', 'handwork' => redirect()->route('operator.dashboard'),

            'ppc' => redirect()->route('ppc.dashboard'),

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