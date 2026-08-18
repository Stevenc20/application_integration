<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DefectMasterSeeder extends Seeder
{
    public function run(): void
    {
        $defects = [
            // Visual
            ['code' => 'DEF-V01', 'name' => 'Scratch',                  'category' => 'Visual'],
            ['code' => 'DEF-V02', 'name' => 'Dent',                     'category' => 'Visual'],
            ['code' => 'DEF-V03', 'name' => 'Warna tidak sesuai',       'category' => 'Visual'],
            ['code' => 'DEF-V04', 'name' => 'Kontaminasi',              'category' => 'Visual'],
            ['code' => 'DEF-V05', 'name' => 'Retak / Crack',            'category' => 'Visual'],
            ['code' => 'DEF-V06', 'name' => 'Burr',                     'category' => 'Visual'],
            // Dimensi
            ['code' => 'DEF-D01', 'name' => 'Dimensi OOS',              'category' => 'Dimensi'],
            ['code' => 'DEF-D02', 'name' => 'Posisi salah',             'category' => 'Dimensi'],
            ['code' => 'DEF-D03', 'name' => 'Toleransi tidak terpenuhi','category' => 'Dimensi'],
            // Fungsi
            ['code' => 'DEF-F01', 'name' => 'Tidak bisa dirakit',       'category' => 'Fungsi'],
            ['code' => 'DEF-F02', 'name' => 'Missing part',             'category' => 'Fungsi'],
            ['code' => 'DEF-F03', 'name' => 'Deformasi',                'category' => 'Fungsi'],
        ];

        foreach ($defects as $defect) {
            DB::table('defect_masters')->insertOrIgnore(array_merge($defect, [
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}