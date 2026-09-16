<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Part;
use App\Models\DefectMaster;
use App\Models\ProductionLine;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            ProductionLineSeeder::class,
            DefectMasterSeeder::class,
        ]);
    }
}

// // ============================================================
// // UserSeeder.php
// // ============================================================

// class UserSeeder extends Seeder
// {
//     public function run(): void
//     {
//         // Admin pertama — Ka Sie QA
//         User::create([
//             'name'        => 'Ka Sie QA',
//             'email'       => 'admin.qa@perusahaan.com',
//             'password'    => Hash::make('ChangeMe123!'),    // WAJIB ganti setelah login pertama
//             'role'        => 'admin',
//             'employee_id' => 'EMP-001',
//             'department'  => 'Quality Assurance',
//             'is_active'   => true,
//         ]);

//         // Contoh user awal per role (hapus/ganti sesuai data asli)
//         $users = [
//             ['name' => 'Staff QA 1',       'email' => 'qa1@perusahaan.com',        'role' => 'qa',         'employee_id' => 'EMP-002', 'department' => 'Quality Assurance'],
//             ['name' => 'Inspector 1',       'email' => 'inspector1@perusahaan.com', 'role' => 'inspector',  'employee_id' => 'EMP-003', 'assigned_line' => 'LINE-01'],
//             ['name' => 'Inspector 2',       'email' => 'inspector2@perusahaan.com', 'role' => 'inspector',  'employee_id' => 'EMP-004', 'assigned_line' => 'LINE-02'],
//             ['name' => 'Foreman Line 1',    'email' => 'foreman1@perusahaan.com',   'role' => 'foreman',    'employee_id' => 'EMP-005', 'assigned_line' => 'LINE-01'],
//             ['name' => 'Operator Produksi', 'email' => 'produksi1@perusahaan.com',  'role' => 'production', 'employee_id' => 'EMP-006', 'assigned_line' => 'LINE-01'],
//         ];

//         foreach ($users as $user) {
//             User::create(array_merge($user, [
//                 'password'  => Hash::make('ChangeMe123!'),
//                 'is_active' => true,
//             ]));
//         }
//     }
// }

// // ============================================================
// // ProductionLineSeeder.php
// // ============================================================

// class ProductionLineSeeder extends Seeder
// {
//     public function run(): void
//     {
//         $lines = [
//             ['code' => 'LINE-01', 'name' => 'Line Assembly A', 'department' => 'Produksi'],
//             ['code' => 'LINE-02', 'name' => 'Line Assembly B', 'department' => 'Produksi'],
//             ['code' => 'LINE-03', 'name' => 'Line Machining',  'department' => 'Produksi'],
//             ['code' => 'LINE-04', 'name' => 'Line Painting',   'department' => 'Finishing'],
//         ];

//         foreach ($lines as $line) {
//             ProductionLine::create(array_merge($line, ['is_active' => true, 'is_stopped' => false]));
//         }
//     }
// }

// // ============================================================
// // DefectMasterSeeder.php
// // ============================================================

// class DefectMasterSeeder extends Seeder
// {
//     public function run(): void
//     {
//         $defects = [
//             // Visual
//             ['code' => 'DEF-V01', 'name' => 'Scratch',         'category' => 'Visual'],
//             ['code' => 'DEF-V02', 'name' => 'Dent',            'category' => 'Visual'],
//             ['code' => 'DEF-V03', 'name' => 'Warna tidak sesuai', 'category' => 'Visual'],
//             ['code' => 'DEF-V04', 'name' => 'Kontaminasi',     'category' => 'Visual'],
//             ['code' => 'DEF-V05', 'name' => 'Retak / Crack',   'category' => 'Visual'],
//             ['code' => 'DEF-V06', 'name' => 'Burr',            'category' => 'Visual'],
//             // Dimensi
//             ['code' => 'DEF-D01', 'name' => 'Dimensi OOS',     'category' => 'Dimensi'],
//             ['code' => 'DEF-D02', 'name' => 'Posisi salah',    'category' => 'Dimensi'],
//             ['code' => 'DEF-D03', 'name' => 'Toleransi tidak terpenuhi', 'category' => 'Dimensi'],
//             // Fungsi
//             ['code' => 'DEF-F01', 'name' => 'Tidak bisa dirakit', 'category' => 'Fungsi'],
//             ['code' => 'DEF-F02', 'name' => 'Missing part',    'category' => 'Fungsi'],
//             ['code' => 'DEF-F03', 'name' => 'Deformasi',       'category' => 'Fungsi'],
//         ];

//         foreach ($defects as $defect) {
//             DefectMaster::create(array_merge($defect, ['is_active' => true]));
//         }
//     }
// }