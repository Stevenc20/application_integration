<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\MasterBreakTime;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Update existing breaks to belong to Shift Pagi
        MasterBreakTime::whereNull('shift')
            ->orWhere('shift', '')
            ->update(['shift' => 'Shift Pagi']);

        // 2. Add Shift Malam breaks
        $malamBreaks = [
            ['hari' => 'semua', 'waktu_mulai' => '00:00', 'waktu_selesai' => '00:45', 'type' => 'istirahat', 'label' => 'ISTIRAHAT MALAM', 'sort_order' => 10, 'shift' => 'Shift Malam'],
            ['hari' => 'semua', 'waktu_mulai' => '04:45', 'waktu_selesai' => '05:00', 'type' => 'istirahat', 'label' => 'BREAKTIME',       'sort_order' => 30, 'shift' => 'Shift Malam'],
        ];

        foreach ($malamBreaks as $row) {
            MasterBreakTime::updateOrCreate(
                ['hari' => $row['hari'], 'waktu_mulai' => $row['waktu_mulai'], 'label' => $row['label'], 'shift' => $row['shift']],
                array_merge($row, ['is_active' => true])
            );
        }
    }

    public function down(): void
    {
        // Remove Shift Malam breaks
        MasterBreakTime::where('shift', 'Shift Malam')->delete();

        // Reset Pagi breaks back to null shift
        MasterBreakTime::where('shift', 'Shift Pagi')->update(['shift' => null]);
    }
};
