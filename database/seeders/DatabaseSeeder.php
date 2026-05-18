<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\ProductionProcessSeeder;
use App\Models\Karyawan;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

use App\Models\LineMaster;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Seed Line Masters
        $lines = [
            ['line_code' => 'PA', 'line_name' => 'PRESS A', 'status' => 'active'],
            ['line_code' => 'PB', 'line_name' => 'PRESS B', 'status' => 'active'],
            ['line_code' => 'PC', 'line_name' => 'PRESS C', 'status' => 'active'],
            ['line_code' => 'PD', 'line_name' => 'PRESS D', 'status' => 'active'],
        ];

        foreach ($lines as $l) {
            LineMaster::create($l);
        }

        $this->call(ProductionProcessSeeder::class);

        // Seed Karyawans
        $karyawans = [
            ['nama_karyawan' => 'Admin Utama', 'nrp_karyawan' => '12345', 'jabatan' => 'admin'],
            ['nama_karyawan' => 'Budi Operator', 'nrp_karyawan' => '23456', 'jabatan' => 'operator'],
            ['nama_karyawan' => 'Candra Leader', 'nrp_karyawan' => '34567', 'jabatan' => 'leader'],
            ['nama_karyawan' => 'Dedi Foreman', 'nrp_karyawan' => '45678', 'jabatan' => 'foreman'],
            ['nama_karyawan' => 'Eko Supervisor', 'nrp_karyawan' => '56789', 'jabatan' => 'supervisor'],
            ['nama_karyawan' => 'Fajar Leader A', 'nrp_karyawan' => '67890', 'jabatan' => 'leader'],
            ['nama_karyawan' => 'Guntur Leader B', 'nrp_karyawan' => '78901', 'jabatan' => 'leader'],
            ['nama_karyawan' => 'Hadi Shearing', 'nrp_karyawan' => '89012', 'jabatan' => 'operator'],
            ['nama_karyawan' => 'Indra Handwork', 'nrp_karyawan' => '90123', 'jabatan' => 'operator'],
            ['nama_karyawan' => 'Joko PPC', 'nrp_karyawan' => '99999', 'jabatan' => 'foreman'],
        ];

        foreach ($karyawans as $k) {
            Karyawan::create($k);
        }

        // Seed corresponding Users
        $users = [
            ['name' => 'Admin Utama', 'nrp' => '12345', 'role' => 'admin', 'password' => Hash::make('password123')],
            ['name' => 'Budi Operator', 'nrp' => '23456', 'role' => 'operator', 'password' => Hash::make('password123')],
            ['name' => 'Candra Leader', 'nrp' => '34567', 'role' => 'leader a', 'password' => Hash::make('password123')],
            ['name' => 'Dedi Foreman', 'nrp' => '45678', 'role' => 'foreman', 'password' => Hash::make('password123')],
            ['name' => 'Eko Supervisor', 'nrp' => '56789', 'role' => 'supervisor', 'password' => Hash::make('password123')],
            ['name' => 'Fajar Leader A', 'nrp' => '67890', 'role' => 'leader a', 'password' => Hash::make('password123')],
            ['name' => 'Guntur Leader B', 'nrp' => '78901', 'role' => 'leader b', 'password' => Hash::make('password123')],
            ['name' => 'Hadi Shearing', 'nrp' => '89012', 'role' => 'shearing', 'password' => Hash::make('password123')],
            ['name' => 'Indra Handwork', 'nrp' => '90123', 'role' => 'handwork', 'password' => Hash::make('password123')],
            ['name' => 'Joko PPC', 'nrp' => '99999', 'role' => 'ppc', 'password' => Hash::make('password123')],
        ];

        foreach ($users as $u) {
            User::create($u);
        }
    }
}