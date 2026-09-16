<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fri istirahat: 11:40 - 12:40 → 11:45 - 12:45
        DB::table('master_break_times')
            ->where('label', 'ISTIRAHAT JUMAT')
            ->where('waktu_mulai', '11:40')
            ->where('waktu_selesai', '12:40')
            ->where('shift', 'Shift Pagi')
            ->update(['waktu_mulai' => '11:45', 'waktu_selesai' => '12:45']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to 11:40
        DB::table('master_break_times')
            ->where('label', 'ISTIRAHAT JUMAT')
            ->where('waktu_mulai', '11:45')
            ->where('waktu_selesai', '12:45')
            ->where('shift', 'Shift Pagi')
            ->update(['waktu_mulai' => '11:40', 'waktu_selesai' => '12:40']);
    }
};
