<?php
 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Trim users.nrp to remove the last character if it is longer than 4 characters
        $users = DB::table('users')->get();
        foreach ($users as $user) {
            if ($user->nrp && strlen($user->nrp) > 4) {
                $newNrp = substr($user->nrp, 0, -1);
                DB::table('users')->where('id', $user->id)->update(['nrp' => $newNrp]);
            }
        }

        // Trim karyawans.nrp_karyawan to remove the last character if it is longer than 4 characters
        $karyawans = DB::table('karyawans')->get();
        foreach ($karyawans as $karyawan) {
            if ($karyawan->nrp_karyawan && strlen($karyawan->nrp_karyawan) > 4) {
                $newNrp = substr($karyawan->nrp_karyawan, 0, -1);
                DB::table('karyawans')->where('id_karyawan', $karyawan->id_karyawan)->update(['nrp_karyawan' => $newNrp]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Truncation cannot be automatically undone
    }
};
