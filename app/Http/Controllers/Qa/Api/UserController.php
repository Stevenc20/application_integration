<?php

namespace App\Http\Controllers\Qa\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * List semua user — bisa filter by role, status, search
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::query()->orderBy('name');

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%");
            });
        }

        if ($request->filled('department')) {
            $query->where('department', $request->department);
        }

        if ($request->filled('assigned_line')) {
            $query->where('assigned_line', $request->assigned_line);
        }

        $users = $query->paginate($request->get('per_page', 100));

        return response()->json([
            'data' => $users->map(fn($u) => $this->userResource($u)),
            'meta' => [
                'total'        => $users->total(),
                'per_page'     => $users->perPage(),
                'current_page' => $users->currentPage(),
                'last_page'    => $users->lastPage(),
            ],
        ]);
    }

    /**
     * Get users by role — endpoint publik (semua user login bisa akses)
     * GET /api/users/by-role?role=Foreman&role=Group+Leader
     */
    public function byRole(Request $request): JsonResponse
    {
        $roles = $request->query('role');
        // Support single role atau array: ?role=Foreman atau ?role[]=Foreman&role[]=Group+Leader
        if (is_string($roles)) $roles = [$roles];

        $departments = $request->query('department');
        if (is_string($departments)) $departments = [$departments];

        $query = User::query()
            ->where('is_active', true)
            ->orderBy('name');

        if (!empty($roles) && !empty($departments)) {
            // Filter by role ATAU department
            $query->where(function($q) use ($roles, $departments) {
                $q->whereIn('role', $roles)
                  ->orWhereIn('department', $departments);
            });
        } elseif (!empty($roles)) {
            $query->whereIn('role', $roles);
        } elseif (!empty($departments)) {
            $query->whereIn('department', $departments);
        }

        $users = $query->get();

        return response()->json(
            $users->map(fn($u) => [
                'id'          => $u->id,
                'name'        => $u->name,
                'role'        => $u->role,
                'department'  => $u->department,
                'employee_id' => $u->employee_id,
                'phone'       => $u->phone,
                'signature'   => $u->signature,  // TTD tersimpan untuk auto-fill QPR
            ])
        );
    }

    /**
     * Tambah user baru
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => 'required|email|unique:users,email',
            'password'      => 'required|string|min:6',
            'role'          => 'required|in:Admin,Group Leader,Foreman,Supervisor,Leader,Operator',
            'employee_id'   => 'required|string|unique:users,employee_id',
            'department'    => 'nullable|string|max:100',
            'assigned_line' => 'nullable|string|max:50',
            'phone'         => 'nullable|string|max:20',
        ]);

        $user = User::create([
            ...$validated,
            'password'  => Hash::make($validated['password']),
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'User berhasil dibuat.',
            'user'    => $this->userResource($user),
        ], 201);
    }

    /**
     * Detail satu user
     */
    public function show(string $id): JsonResponse
    {
        $user = User::findOrFail($id);

        return response()->json([
            'user' => $this->userResource($user),
        ]);
    }

    /**
     * Update user
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name'          => 'sometimes|string|max:255',
            'email'         => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
            'role'          => 'sometimes|in:Admin,Group Leader,Foreman,Supervisor,Leader,Operator',
            'employee_id'   => ['sometimes', 'required', Rule::unique('users')->ignore($user->id)],
            'department'    => 'sometimes|nullable|string|max:100',
            'assigned_line' => 'sometimes|nullable|string|max:50',
            'phone'         => 'sometimes|nullable|string|max:20',
            'password'      => 'sometimes|nullable|string|min:6',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'message' => 'User berhasil diupdate.',
            'user'    => $this->userResource($user->fresh()),
        ]);
    }

    /**
     * Hapus user (soft delete)
     */
    public function destroy(string $id): JsonResponse
    {
        $user = User::findOrFail($id);

        // Tidak boleh hapus diri sendiri
        if ($user->id === auth()->id()) {
            return response()->json([
                'message' => 'Tidak dapat menghapus akun sendiri.',
            ], 422);
        }

        // Hapus semua token user sebelum di-soft delete
        $user->tokens()->delete();
        $user->delete();

        return response()->json([
            'message' => 'User berhasil dihapus.',
        ]);
    }

    /**
     * Aktifkan / nonaktifkan user
     */
    public function toggleActive(string $id): JsonResponse
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            return response()->json([
                'message' => 'Tidak dapat menonaktifkan akun sendiri.',
            ], 422);
        }

        $user->update(['is_active' => ! $user->is_active]);

        // Kalau dinonaktifkan, hapus semua token aktifnya
        if (! $user->is_active) {
            $user->tokens()->delete();
        }

        return response()->json([
            'message' => $user->is_active ? 'User diaktifkan.' : 'User dinonaktifkan.',
            'user'    => $this->userResource($user),
        ]);
    }

    /**
     * Format resource user
     */
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
            'created_at'    => $user->created_at->toIso8601String(),
        ];
    }
}