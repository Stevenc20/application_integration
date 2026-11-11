<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Redirect index user management langsung ke master data karyawan.
     */
    public function index()
    {
        return redirect()->route('master.karyawan');
    }

    public function create()
    {
        return redirect()->route('master.karyawan');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'nrp' => 'required|digits_between:4,20|unique:users,nrp',
            'password' => 'required|min:6',
            'role' => 'required'
        ], [
            'nrp.digits_between' => 'NRP harus 4-20 digit angka.',
            'nrp.unique' => 'NRP sudah terdaftar, gunakan NRP yang lain.',
        ]);

        User::create([
            'name' => $request->name,
            'nrp' => $request->nrp,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        return redirect()->route('master.karyawan')->with('success', 'User berhasil ditambahkan');
    }

    public function edit(User $user)
    {
        return redirect()->route('master.karyawan');
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required',
            'nrp' => 'required|digits_between:4,20|unique:users,nrp,' . $user->id,
            'role' => 'required'
        ], [
            'nrp.digits_between' => 'NRP harus 4-20 digit angka.',
            'nrp.unique' => 'NRP sudah digunakan user lain.',
        ]);

        $data = [
            'name' => $request->name,
            'nrp' => $request->nrp,
            'role' => $request->role,
        ];

        // password optional
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return redirect()->route('master.karyawan')->with('success', 'User berhasil diupdate');
    }

    public function destroy(User $user)
    {
        if (auth()->id() == $user->id) {
            return redirect()->route('master.karyawan')->with('error', 'Tidak bisa hapus akun sendiri');
        }

        $user->delete();

        return redirect()->route('master.karyawan')->with('success', 'User deleted');
    }
}
