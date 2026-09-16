<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Karyawan;
use App\Models\User;
use Illuminate\Http\Request;

class KaryawanController extends Controller
{
    /**
     * Tampilkan daftar karyawan dengan pagination & filter jabatan.
     */
    public function index(Request $request)
    {
        $query = Karyawan::query();

        // Filter pencarian
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_karyawan', 'like', "%{$search}%")
                  ->orWhere('nrp_karyawan', 'like', "%{$search}%");
            });
        }

        // Filter jabatan
        if ($request->filled('jabatan')) {
            $query->where('jabatan', $request->jabatan);
        }

        $karyawans = $query->orderBy('nama_karyawan')->paginate(15)->withQueryString();

        // Stats per jabatan
        $stats = [
            'total'      => Karyawan::count(),
            'operator'   => Karyawan::where('jabatan', 'operator')->count(),
            'leader'     => Karyawan::whereIn('jabatan', ['leader','leader a','leader b','leader c','leader d'])->count(),
            'leader a'   => Karyawan::where('jabatan', 'leader a')->count(),
            'leader b'   => Karyawan::where('jabatan', 'leader b')->count(),
            'leader c'   => Karyawan::where('jabatan', 'leader c')->count(),
            'leader d'   => Karyawan::where('jabatan', 'leader d')->count(),
            'shearing'   => Karyawan::where('jabatan', 'shearing')->count(),
            'handwork'   => Karyawan::where('jabatan', 'handwork')->count(),
            'foreman'    => Karyawan::where('jabatan', 'foreman')->count(),
            'supervisor' => Karyawan::where('jabatan', 'supervisor')->count(),
            'ppc'        => Karyawan::where('jabatan', 'ppc')->count(),
            'admin'      => Karyawan::where('jabatan', 'admin')->count(),
        ];

        return view('master.karyawan', compact('karyawans', 'stats'));
    }

    /**
     * Simpan karyawan baru.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama_karyawan' => 'required|string|max:100',
            'nrp_karyawan'  => 'required|digits:4|unique:karyawans,nrp_karyawan',
            'jabatan'       => 'required|in:admin,operator,leader,leader a,leader b,leader c,leader d,shearing,handwork,foreman,supervisor,ppc,quality,production,manager,kadiv,direktur,presdir,superadmin,dies_shop,plant_service,irm,logistik,produksi',
        ], [
            'nrp_karyawan.digits' => 'NRP harus 4 digit angka.',
            'nrp_karyawan.unique' => 'NRP sudah terdaftar, gunakan NRP yang lain.',
        ]);

        $karyawan = Karyawan::create($request->only('nama_karyawan', 'nrp_karyawan', 'jabatan'));

        User::where('nrp', $karyawan->nrp_karyawan)->update(['role' => $karyawan->jabatan]);

        return redirect()->route('master.karyawan')->with('success', 'Karyawan berhasil ditambahkan.');
    }

    /**
     * Update data karyawan.
     */
    public function update(Request $request, $id)
    {
        $karyawan = Karyawan::findOrFail($id);

        $request->validate([
            'nama_karyawan' => 'required|string|max:100',
            'nrp_karyawan'  => 'required|digits:4|unique:karyawans,nrp_karyawan,' . $id . ',id_karyawan',
            'jabatan'       => 'required|in:admin,operator,leader,leader a,leader b,leader c,leader d,shearing,handwork,foreman,supervisor,ppc,quality,production,manager,kadiv,direktur,presdir,superadmin,dies_shop,plant_service,irm,logistik,produksi',
        ], [
            'nrp_karyawan.unique' => 'NRP sudah digunakan karyawan lain.',
        ]);

        $karyawan->update($request->only('nama_karyawan', 'nrp_karyawan', 'jabatan'));

        User::where('nrp', $karyawan->nrp_karyawan)->update(['role' => $karyawan->jabatan]);

        return redirect()->route('master.karyawan')->with('success', 'Data karyawan berhasil diperbarui.');
    }

    /**
     * Hapus karyawan.
     */
    public function delete($id)
    {
        $karyawan = Karyawan::findOrFail($id);
        $karyawan->delete();

        return redirect()->route('master.karyawan')->with('success', 'Karyawan berhasil dihapus.');
    }
}
