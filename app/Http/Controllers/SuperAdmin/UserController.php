<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Position;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with(['position', 'section'])->latest()->paginate(10);
        $positions = Position::orderBy('level')->get();
        $sections = Section::orderBy('section_name')->get();
        return view('super_admin.users.index', compact('users', 'positions', 'sections'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'nrp' => 'required|digits:4|unique:users,nrp',
            'password' => 'required|min:6',
            'system_role' => ['required', Rule::in(['superadmin', 'admin', 'user'])],
            'position_id' => 'required_if:system_role,user',
            'section_id' => 'required_if:system_role,user',
        ], [
            'nrp.digits' => 'NRP harus 4 digit angka.',
            'nrp.unique' => 'NRP sudah terdaftar, gunakan NRP yang lain.',
            'position_id.required_if' => 'Jabatan wajib diisi untuk User biasa.',
            'section_id.required_if' => 'Section wajib diisi untuk User biasa.',
        ]);

        User::create([
            'name' => $request->name,
            'nrp' => $request->nrp,
            'password' => Hash::make($request->password),
            'system_role' => $request->system_role,
            'position_id' => $request->system_role === 'user' ? $request->position_id : null,
            'section_id' => $request->system_role === 'user' ? $request->section_id : null,
            'role' => 'user', // Compatibility column
        ]);

        return redirect()->back()->with('success', 'User created successfully.');
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required',
            'nrp' => 'required|digits:4|unique:users,nrp,' . $user->id,
            'system_role' => ['required', Rule::in(['superadmin', 'admin', 'user'])],
            'position_id' => 'required_if:system_role,user',
            'section_id' => 'required_if:system_role,user',
        ], [
            'nrp.digits' => 'NRP harus 4 digit angka.',
            'nrp.unique' => 'NRP sudah digunakan user lain.',
            'position_id.required_if' => 'Jabatan wajib diisi untuk User biasa.',
            'section_id.required_if' => 'Section wajib diisi untuk User biasa.',
        ]);

        $data = [
            'name' => $request->name,
            'nrp' => $request->nrp,
            'system_role' => $request->system_role,
            'position_id' => $request->system_role === 'user' ? $request->position_id : null,
            'section_id' => $request->system_role === 'user' ? $request->section_id : null,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        if ($user->system_role === 'superadmin' && $user->id === auth()->id() && $request->system_role !== 'superadmin') {
            return back()->with('error', 'You cannot remove your own super admin status.');
        }

        $user->update($data);

        return redirect()->back()->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        if ($user->system_role === 'superadmin' || $user->role === 'superadmin') {
            $superadminCount = User::where('system_role', 'superadmin')->orWhere('role', 'superadmin')->count();
            if ($superadminCount <= 1) {
                return back()->with('error', 'Cannot delete the last super admin.');
            }
        }

        if (auth()->id() == $user->id) {
            return back()->with('error', 'Cannot delete your own account.');
        }

        $user->delete();

        return back()->with('success', 'User deleted.');
    }
}
