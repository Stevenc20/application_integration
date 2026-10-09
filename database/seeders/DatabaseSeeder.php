<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Karyawan;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

use App\Models\LineMaster;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            FeaturePermissionSeeder::class,
            RoleSeeder::class,
            DepartmentSeeder::class,
            SectionSeeder::class,
            PositionSeeder::class,
            MasterBreakTimeSeeder::class,
            BomSeeder::class,
        ]);

        // Seed Line Masters
        $lines = [
            ['line_code' => 'PA', 'line_name' => 'PRESS A', 'status' => 'active'],
            ['line_code' => 'PB', 'line_name' => 'PRESS B', 'status' => 'active'],
            ['line_code' => 'PC', 'line_name' => 'PRESS C', 'status' => 'active', 'production_end' => '22:00'],
            ['line_code' => 'PD', 'line_name' => 'PRESS D', 'status' => 'active', 'production_start' => '12:45'],
        ];

        foreach ($lines as $l) {
            LineMaster::updateOrCreate(
                ['line_code' => $l['line_code']],
                $l
            );
        }

        // Seed Karyawans
        $karyawans = [
            ['nama_karyawan' => 'Admin Utama', 'nrp_karyawan' => '1234', 'jabatan' => 'admin'],
            ['nama_karyawan' => 'Budi Operator', 'nrp_karyawan' => '2345', 'jabatan' => 'operator'],
            ['nama_karyawan' => 'Candra Leader', 'nrp_karyawan' => '3456', 'jabatan' => 'leader'],
            ['nama_karyawan' => 'Dedi Foreman', 'nrp_karyawan' => '4567', 'jabatan' => 'foreman'],
            ['nama_karyawan' => 'Eko Supervisor', 'nrp_karyawan' => '5678', 'jabatan' => 'supervisor'],
            ['nama_karyawan' => 'Fajar Leader A', 'nrp_karyawan' => '6789', 'jabatan' => 'leader'],
            ['nama_karyawan' => 'Guntur Leader B', 'nrp_karyawan' => '7890', 'jabatan' => 'leader'],
            ['nama_karyawan' => 'Hadi Shearing', 'nrp_karyawan' => '8901', 'jabatan' => 'operator'],
            ['nama_karyawan' => 'Indra Handwork', 'nrp_karyawan' => '9012', 'jabatan' => 'operator'],
            ['nama_karyawan' => 'Joko PPC', 'nrp_karyawan' => '9999', 'jabatan' => 'foreman'],
            ['nama_karyawan' => 'Manager Produksi', 'nrp_karyawan' => '1111', 'jabatan' => 'manager'],
            ['nama_karyawan' => 'Kepala Divisi', 'nrp_karyawan' => '2222', 'jabatan' => 'kadiv'],
            ['nama_karyawan' => 'Direktur', 'nrp_karyawan' => '3333', 'jabatan' => 'direktur'],
            ['nama_karyawan' => 'Presdir', 'nrp_karyawan' => '4444', 'jabatan' => 'presdir'],
            ['nama_karyawan' => 'Quality Control', 'nrp_karyawan' => '5555', 'jabatan' => 'operator'],
            ['nama_karyawan' => 'Production Staff', 'nrp_karyawan' => '6666', 'jabatan' => 'operator'],
        ];

        foreach ($karyawans as $k) {
            Karyawan::create($k);
        }

        // Seed corresponding Users
        $users = [
            ['name' => 'Super Admin', 'nrp' => '0000', 'role' => 'superadmin', 'password' => Hash::make('superadmin123')],
            ['name' => 'Admin Utama', 'nrp' => '1234', 'role' => 'admin', 'password' => Hash::make('password123')],
            ['name' => 'Budi Operator', 'nrp' => '2345', 'role' => 'operator', 'password' => Hash::make('password123')],
            ['name' => 'Candra Leader', 'nrp' => '3456', 'role' => 'leader a', 'password' => Hash::make('password123')],
            ['name' => 'Dedi Foreman', 'nrp' => '4567', 'role' => 'foreman', 'password' => Hash::make('password123')],
            ['name' => 'Eko Supervisor', 'nrp' => '5678', 'role' => 'supervisor', 'password' => Hash::make('password123')],
            ['name' => 'Fajar Leader A', 'nrp' => '6789', 'role' => 'leader a', 'password' => Hash::make('password123')],
            ['name' => 'Guntur Leader B', 'nrp' => '7890', 'role' => 'leader b', 'password' => Hash::make('password123')],
            ['name' => 'Hadi Shearing', 'nrp' => '8901', 'role' => 'shearing', 'password' => Hash::make('password123')],
            ['name' => 'Indra Handwork', 'nrp' => '9012', 'role' => 'handwork', 'password' => Hash::make('password123')],
            ['name' => 'Joko PPC', 'nrp' => '9999', 'role' => 'ppc', 'password' => Hash::make('password123')],
            ['name' => 'Manager Produksi', 'nrp' => '1111', 'role' => 'manager', 'password' => Hash::make('password123')],
            ['name' => 'Kepala Divisi', 'nrp' => '2222', 'role' => 'kadiv', 'password' => Hash::make('password123')],
            ['name' => 'Direktur', 'nrp' => '3333', 'role' => 'direktur', 'password' => Hash::make('password123')],
            ['name' => 'Presdir', 'nrp' => '4444', 'role' => 'presdir', 'password' => Hash::make('password123')],
            ['name' => 'Quality Control', 'nrp' => '5555', 'role' => 'quality', 'password' => Hash::make('password123')],
            ['name' => 'Production Staff', 'nrp' => '6666', 'role' => 'production', 'password' => Hash::make('password123')],

            // Hambatan Jalur Roles
            ['name' => 'Dies Shop', 'nrp' => 'DS00', 'role' => 'dies_shop', 'password' => Hash::make('password123')],
            ['name' => 'Plant Service', 'nrp' => 'PS00', 'role' => 'plant_service', 'password' => Hash::make('password123')],
            ['name' => 'IRM', 'nrp' => 'IRM0', 'role' => 'irm', 'password' => Hash::make('password123')],
            ['name' => 'Logistik', 'nrp' => 'LOG0', 'role' => 'logistik', 'password' => Hash::make('password123')],
            ['name' => 'Produksi', 'nrp' => 'PRD0', 'role' => 'produksi', 'password' => Hash::make('password123')],
        ];

        foreach ($users as $u) {
            User::create($u);
        }

        $this->call([
            UserStructureSeeder::class,
        ]);
    }
}
