<?php

namespace App\Http\Controllers\Qa\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class LoginWebController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect(url('/dashboard'));
        }
        return view('login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'employee_id' => 'required|string',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            
            return response()->json([
                'message' => 'Login berhasil',
                'user' => Auth::user(),
                'redirect' => url('/dashboard')
            ]);
        }

        return response()->json([
            'message' => 'Employee ID atau password salah.'
        ], 401);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect(url('/login'));
    }
}
