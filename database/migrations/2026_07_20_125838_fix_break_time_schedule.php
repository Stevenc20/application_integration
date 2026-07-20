<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Mon-Thu istirahat: 12:00 - 12:45 → 12:00 - 12:40
        DB::table('master_break_times')
            ->where('label', 'ISTIRAHAT SIANG')
            ->where('waktu_mulai', '12:00')
            ->where('waktu_selesai', '12:45')
            ->where('shift', 'Shift Pagi')
            ->update(['waktu_selesai' => '12:40']);

        // Fri istirahat: 11:45 - 12:45 → 11:40 - 12:40
        DB::table('master_break_times')
            ->where('label', 'ISTIRAHAT JUMAT')
            ->where('waktu_mulai', '11:45')
            ->where('waktu_selesai', '12:45')
            ->where('shift', 'Shift Pagi')
            ->update(['waktu_mulai' => '11:40', 'waktu_selesai' => '12:40']);
    }

    public function down(): void
    {
        DB::table('master_break_times')
            ->where('label', 'ISTIRAHAT SIANG')
            ->where('waktu_mulai', '12:00')
            ->where('waktu_selesai', '12:40')
            ->where('shift', 'Shift Pagi')
            ->update(['waktu_selesai' => '12:45']);

        DB::table('master_break_times')
            ->where('label', 'ISTIRAHAT JUMAT')
            ->where('waktu_mulai', '11:40')
            ->where('waktu_selesai', '12:40')
            ->where('shift', 'Shift Pagi')
            ->update(['waktu_mulai' => '11:45', 'waktu_selesai' => '12:45']);
    }
};
