<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = User::latest()->paginate(10);
        return view('super_admin.users.index', compact('users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'nrp' => 'required|digits:4|unique:users,nrp',
            'password' => 'required|min:6',
            'role' => 'required',
        ], [
            'nrp.digits' => 'NRP harus 4 digit angka.',
            'nrp.unique' => 'NRP sudah terdaftar, gunakan NRP yang lain.',
        ]);

        User::create([
            'name' => $request->name,
            'nrp' => $request->nrp,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        return redirect()->back()->with('success', 'User created successfully.');
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required',
            'nrp' => 'required|digits:4|unique:users,nrp,' . $user->id,
            'role' => 'required',
        ], [
            'nrp.digits' => 'NRP harus 4 digit angka.',
            'nrp.unique' => 'NRP sudah digunakan user lain.',
        ]);

        $data = [
            'name' => $request->name,
            'nrp' => $request->nrp,
            'role' => $request->role,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        if ($user->role === 'superadmin' && $user->id === auth()->id() && $request->role !== 'superadmin') {
            return back()->with('error', 'You cannot remove your own super admin status.');
        }

        $user->update($data);

        return redirect()->back()->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        if ($user->role === 'superadmin') {
            $superadminCount = User::where('role', 'superadmin')->count();
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
