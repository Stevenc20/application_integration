<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = User::latest()->paginate(10);
        return view('admin.user_management.user_list', compact('users'));
    }

    public function create()
    {
        return view('admin.user_management.create');
    }

    public function store(Request $request)
    {
    $request->validate([
        'name' => 'required',
        'nip' => 'required|unique:users,nip',
        'password' => 'required|min:6',
        'role' => 'required'
    ]);

    User::create([
        'name' => $request->name,
        'nip' => $request->nip,
        'password' => Hash::make($request->password),
        'role' => $request->role,
    ]);

    return redirect()->back()->with('success', 'User berhasil ditambahkan');
    }

    public function edit(User $user)
    {
        return view('admin.user_management.edit', compact('user'));
    }

  public function update(Request $request, User $user)
{
    $request->validate([
        'name' => 'required',
        'nip' => 'required|unique:users,nip,' . $user->id,
        'role' => 'required'
    ]);

    $data = [
        'name' => $request->name,
        'nip' => $request->nip,
        'role' => $request->role,
    ];

    // password optional
    if ($request->filled('password')) {
        $data['password'] = Hash::make($request->password);
    }

    $user->update($data);

    return redirect()->back()->with('success', 'User berhasil diupdate');
}

    public function destroy(User $user)
    {
        if(auth()->id() == $user->id){
            return back()->with('error','Tidak bisa hapus akun sendiri');
        }

        $user->delete();

        return back()->with('success','User deleted');
    }
}