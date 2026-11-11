<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Karyawan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class KaryawanController extends Controller
{
    /**
     * Tampilkan daftar karyawan / user dengan pagination & filter jabatan.
     */
    public function index(Request $request)
    {
        $query = User::query();

        // Filter pencarian
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nrp', 'like', "%{$search}%");
            });
        }

        // Filter jabatan / role
        if ($request->filled('jabatan')) {
            $query->where('role', $request->jabatan);
        }

        $karyawans = $query->orderBy('name')->paginate(15)->withQueryString();

        // Stats per jabatan / role
        $stats = [
            'total'      => User::count(),
            'operator'   => User::where('role', 'operator')->count(),
            'leader'     => User::whereIn('role', ['leader', 'leader a', 'leader b', 'leader c', 'leader d'])->count(),
            'leader a'   => User::where('role', 'leader a')->count(),
            'leader b'   => User::where('role', 'leader b')->count(),
            'leader c'   => User::where('role', 'leader c')->count(),
            'leader d'   => User::where('role', 'leader d')->count(),
            'shearing'   => User::where('role', 'shearing')->count(),
            'handwork'   => User::where('role', 'handwork')->count(),
            'foreman'    => User::where('role', 'foreman')->count(),
            'supervisor' => User::where('role', 'supervisor')->count(),
            'ppc'        => User::where('role', 'ppc')->count(),
            'admin'      => User::where('role', 'admin')->count(),
        ];

        return view('master.karyawan', compact('karyawans', 'stats'));
    }

    /**
     * Simpan karyawan / user baru.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama_karyawan' => 'required|string|max:100',
            'nrp_karyawan'  => 'required|digits_between:4,20|unique:users,nrp',
            'jabatan'       => 'required|in:admin,operator,leader,leader a,leader b,leader c,leader d,shearing,handwork,foreman,supervisor,ppc,quality,production,manager,kadiv,direktur,presdir,superadmin,dies_shop,plant_service,irm,logistik,produksi',
            'password'      => 'nullable|string|min:6',
        ], [
            'nrp_karyawan.digits_between' => 'NRP harus 4-20 digit angka.',
            'nrp_karyawan.unique'         => 'NRP sudah terdaftar, gunakan NRP yang lain.',
            'password.min'                => 'Password minimal 6 karakter.',
        ]);

        $password = $request->filled('password') ? Hash::make($request->password) : Hash::make('password123');

        $user = User::create([
            'name'      => $request->nama_karyawan,
            'nrp'       => $request->nrp_karyawan,
            'role'      => $request->jabatan,
            'password'  => $password,
            'is_active' => 1,
        ]);

        // Sync ke tabel karyawans (jika ada relasi legacy)
        Karyawan::updateOrCreate(
            ['nrp_karyawan' => $request->nrp_karyawan],
            ['nama_karyawan' => $request->nama_karyawan, 'jabatan' => $request->jabatan]
        );

        return redirect()->route('master.karyawan')->with('success', 'Karyawan dan akun user berhasil ditambahkan.');
    }

    /**
     * Update data karyawan / user.
     */
    public function update(Request $request, int|string $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'nama_karyawan' => 'required|string|max:100',
            'nrp_karyawan'  => 'required|digits_between:4,20|unique:users,nrp,' . $id,
            'jabatan'       => 'required|in:admin,operator,leader,leader a,leader b,leader c,leader d,shearing,handwork,foreman,supervisor,ppc,quality,production,manager,kadiv,direktur,presdir,superadmin,dies_shop,plant_service,irm,logistik,produksi',
            'password'      => 'nullable|string|min:6',
        ], [
            'nrp_karyawan.unique' => 'NRP sudah digunakan karyawan/user lain.',
            'password.min'        => 'Password minimal 6 karakter.',
        ]);

        $oldNrp = $user->nrp;

        $userData = [
            'name' => $request->nama_karyawan,
            'nrp'  => $request->nrp_karyawan,
            'role' => $request->jabatan,
        ];

        if ($request->filled('password')) {
            $userData['password'] = Hash::make($request->password);
        }

        $user->update($userData);

        // Sync ke tabel karyawans (berdasarkan NRP lama atau baru)
        $karyawan = Karyawan::where('nrp_karyawan', $oldNrp)->first();
        if ($karyawan) {
            $karyawan->update([
                'nama_karyawan' => $request->nama_karyawan,
                'nrp_karyawan'  => $request->nrp_karyawan,
                'jabatan'       => $request->jabatan,
            ]);
        } else {
            Karyawan::updateOrCreate(
                ['nrp_karyawan' => $request->nrp_karyawan],
                ['nama_karyawan' => $request->nama_karyawan, 'jabatan' => $request->jabatan]
            );
        }

        return redirect()->route('master.karyawan')->with('success', 'Data karyawan dan user login berhasil diperbarui.');
    }

    /**
     * Hapus karyawan / user.
     */
    public function delete(int|string $id)
    {
        $user = User::findOrFail($id);

        if (auth()->id() == $user->id) {
            return redirect()->route('master.karyawan')->with('error', 'Tidak bisa menghapus akun yang sedang login.');
        }

        $nrp = $user->nrp;
        $user->delete();

        // Hapus juga dari tabel karyawans jika ada
        Karyawan::where('nrp_karyawan', $nrp)->delete();

        return redirect()->route('master.karyawan')->with('success', 'Karyawan dan akun user berhasil dihapus.');
    }
}
