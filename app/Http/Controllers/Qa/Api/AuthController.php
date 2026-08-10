<?php

namespace App\Http\Controllers\Qa\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'employee_id' => 'required|string',
            'password'    => 'required|string',
        ]);

        $user = User::where('employee_id', $request->employee_id)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'employee_id' => ['Employee ID atau password salah.'],
            ]);
        }

        if (! $user->is_active) {
            return response()->json([
                'message' => 'Akun Anda dinonaktifkan. Hubungi administrator.',
            ], 403);
        }

        $deviceName = $request->device_name ?? 'web';
        $user->tokens()->where('name', $deviceName)->delete();
        $token = $user->createToken($deviceName)->plainTextToken;
        $user->update(['last_login_at' => now()]);

        return response()->json([
            'message' => 'Login berhasil',
            'token'   => $token,
            'user'    => $this->userResource($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logout berhasil.']);
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'Logout dari semua device berhasil.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $this->userResource($request->user()),
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if (! Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Password lama tidak sesuai.'],
            ]);
        }

        $user->update(['password' => Hash::make($request->new_password)]);
        $user->tokens()->delete();

        return response()->json(['message' => 'Password berhasil diubah. Silakan login kembali.']);
    }

    private function userResource(User $user): array
    {
        return [
            'id'            => $user->id,
            'name'          => $user->name,
            'email'         => $user->email,
            'role'          => $user->role,
            'employee_id'   => $user->employee_id,
            'department'    => $user->department,
            'assigned_line' => $user->assigned_line,
            'company_name'  => $user->company_name,
            'phone'         => $user->phone,
            'is_active'     => $user->is_active,
            'last_login_at' => $user->last_login_at?->toIso8601String(),
            'permissions'   => [
                'can_inspect'          => $user->hasRole(['admin', 'qa', 'inspector']),
                'can_verify_ng'        => $user->hasRole(['admin', 'qa']),
                'can_trigger_stopline' => $user->hasRole(['admin', 'qa', 'foreman']),
                'can_manage_complaint' => $user->hasRole(['admin', 'qa']),
                'can_view_complaint'   => $user->hasRole(['admin', 'qa', 'customer']),
                'can_view_reports'     => $user->hasRole(['admin', 'qa', 'foreman']),
                'can_manage_users'     => $user->isAdmin(),
                'can_manage_master'    => $user->isAdmin(),
            ],
        ];
    }
}