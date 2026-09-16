<?php

namespace Database\Seeders;

use App\Models\MasterBreakTime;
use Illuminate\Database\Seeder;

class MasterBreakTimeSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            // Shift Pagi
            ['hari' => 'senin', 'waktu_mulai' => '12:00', 'waktu_selesai' => '12:45', 'type' => 'istirahat', 'label' => 'ISTIRAHAT SIANG', 'sort_order' => 10, 'shift' => 'Shift Pagi'],
            ['hari' => 'selasa', 'waktu_mulai' => '12:00', 'waktu_selesai' => '12:45', 'type' => 'istirahat', 'label' => 'ISTIRAHAT SIANG', 'sort_order' => 10, 'shift' => 'Shift Pagi'],
            ['hari' => 'rabu', 'waktu_mulai' => '12:00', 'waktu_selesai' => '12:45', 'type' => 'istirahat', 'label' => 'ISTIRAHAT SIANG', 'sort_order' => 10, 'shift' => 'Shift Pagi'],
            ['hari' => 'kamis', 'waktu_mulai' => '12:00', 'waktu_selesai' => '12:45', 'type' => 'istirahat', 'label' => 'ISTIRAHAT SIANG', 'sort_order' => 10, 'shift' => 'Shift Pagi'],
            ['hari' => 'jumat', 'waktu_mulai' => '11:45', 'waktu_selesai' => '12:45', 'type' => 'istirahat', 'label' => 'ISTIRAHAT JUMAT', 'sort_order' => 10, 'shift' => 'Shift Pagi'],
            ['hari' => 'semua', 'waktu_mulai' => '15:15', 'waktu_selesai' => '15:30', 'type' => 'cinkorak', 'label' => 'CINGKORAK', 'sort_order' => 20, 'shift' => 'Shift Pagi'],
            ['hari' => 'semua', 'waktu_mulai' => '16:30', 'waktu_selesai' => '16:45', 'type' => 'istirahat', 'label' => 'BREAKTIME', 'sort_order' => 30, 'shift' => 'Shift Pagi'],
            ['hari' => 'semua', 'waktu_mulai' => '18:00', 'waktu_selesai' => '18:30', 'type' => 'istirahat', 'label' => 'ISTIRAHAT SORE', 'sort_order' => 40, 'shift' => 'Shift Pagi'],
            // Shift Malam
            ['hari' => 'semua', 'waktu_mulai' => '00:00', 'waktu_selesai' => '00:45', 'type' => 'istirahat', 'label' => 'ISTIRAHAT MALAM', 'sort_order' => 10, 'shift' => 'Shift Malam'],
            ['hari' => 'semua', 'waktu_mulai' => '04:45', 'waktu_selesai' => '05:00', 'type' => 'istirahat', 'label' => 'BREAKTIME', 'sort_order' => 20, 'shift' => 'Shift Malam'],
        ];

        foreach ($rows as $row) {
            MasterBreakTime::updateOrCreate(
                [
                    'hari' => $row['hari'],
                    'waktu_mulai' => $row['waktu_mulai'],
                    'label' => $row['label'],
                    'shift' => $row['shift'],
                ],
                array_merge($row, ['is_active' => true])
            );
        }
    }
}
