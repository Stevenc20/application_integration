<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [

            // ── Quality Assurance ─────────────────────────────────────────
            [
                'name'         => 'Rayyan Ardiano',
                'email'        => 'admin.qa@perusahaan.com',
                'password'     => Hash::make('ChangeMe123!'),
                'role'         => 'Admin',
                'employee_id'  => 'EMP-000',
                'department'   => 'Quality Assurance',
                'is_active'    => true,
            ],

            // ── Quality Control – Group Leader ────────────────────────────
            [
                'name'         => 'Susang Raharjo',
                'email'        => 'susang.raharjo@perusahaan.com',
                'password'     => Hash::make('ChangeMe123!'),
                'role'         => 'Group Leader',
                'employee_id'  => 'EMP-001',
                'department'   => 'Quality Control',
                'is_active'    => true,
            ],
            [
                'name'         => 'Murdianto',
                'email'        => 'murdianto@perusahaan.com',
                'password'     => Hash::make('ChangeMe123!'),
                'role'         => 'Group Leader',
                'employee_id'  => 'EMP-002',
                'department'   => 'Quality Control',
                'is_active'    => true,
            ],

            // ── Quality Control – Foreman ─────────────────────────────────
            [
                'name'         => 'Deddy Purwanto',
                'email'        => 'deddy.purwanto@perusahaan.com',
                'password'     => Hash::make('ChangeMe123!'),
                'role'         => 'Foreman',
                'employee_id'  => 'EMP-003',
                'department'   => 'Quality Control',
                'is_active'    => true,
            ],
            [
                'name'         => 'Azriel',
                'email'        => 'azriel@perusahaan.com',
                'password'     => Hash::make('ChangeMe123!'),
                'role'         => 'Foreman',
                'employee_id'  => 'EMP-004',
                'department'   => 'Quality Control',
                'is_active'    => true,
            ],
            [
                'name'         => 'Yulistiawan',
                'email'        => 'yulistiawan@perusahaan.com',
                'password'     => Hash::make('ChangeMe123!'),
                'role'         => 'Foreman',
                'employee_id'  => 'EMP-005',
                'department'   => 'Quality Control',
                'is_active'    => true,
            ],

            // ── Quality Control – Supervisor ──────────────────────────────
            [
                'name'         => 'Novina',
                'email'        => 'novina@perusahaan.com',
                'password'     => Hash::make('ChangeMe123!'),
                'role'         => 'Supervisor',
                'employee_id'  => 'EMP-014',
                'department'   => 'Quality Control',
                'is_active'    => true,
            ],

            // ── IRM (Inventory Raw Material) ──────────────────────────────
            [
                'name'         => 'Ikhsan',
                'email'        => 'ikhsan@perusahaan.com',
                'password'     => Hash::make('ChangeMe123!'),
                'role'         => 'Foreman',
                'employee_id'  => 'EMP-020',
                'department'   => 'IRM',
                'is_active'    => true,
            ],
            [
                'name'         => 'Alberta',
                'email'        => 'alberta@perusahaan.com',
                'password'     => Hash::make('ChangeMe123!'),
                'role'         => 'Supervisor',
                'employee_id'  => 'EMP-021',
                'department'   => 'IRM',
                'is_active'    => true,
            ],

            // ── Produksi SA ───────────────────────────────────────────────
            [
                'name'         => 'Hafid',
                'email'        => 'hafid@perusahaan.com',
                'password'     => Hash::make('ChangeMe123!'),
                'role'         => 'Foreman',
                'employee_id'  => 'EMP-030',
                'department'   => 'Produksi SA',
                'is_active'    => true,
            ],
            [
                'name'         => 'Burhan',
                'email'        => 'burhan@perusahaan.com',
                'password'     => Hash::make('ChangeMe123!'),
                'role'         => 'Foreman',
                'employee_id'  => 'EMP-031',
                'department'   => 'Produksi SA',
                'is_active'    => true,
            ],
            [
                'name'         => 'Sapriadi',
                'email'        => 'sapriadi@perusahaan.com',
                'password'     => Hash::make('ChangeMe123!'),
                'role'         => 'Supervisor',
                'employee_id'  => 'EMP-032',
                'department'   => 'Produksi SA',
                'is_active'    => true,
            ],
            [
                'name'         => 'Bayu BR',
                'email'        => 'bayu.br@perusahaan.com',
                'password'     => Hash::make('ChangeMe123!'),
                'role'         => 'Supervisor',
                'employee_id'  => 'EMP-033',
                'department'   => 'Produksi SA',
                'is_active'    => true,
            ],

            // ── Produksi Stamping ─────────────────────────────────────────
            // Supervisor sama dengan SA (Sapriadi EMP-032, Bayu EMP-033)
            [
                'name'         => 'Harjito',
                'email'        => 'harjito@perusahaan.com',
                'password'     => Hash::make('ChangeMe123!'),
                'role'         => 'Foreman',
                'employee_id'  => 'EMP-040',
                'department'   => 'Produksi Stamping',
                'is_active'    => true,
            ],
            [
                'name'         => 'Sukoyo',
                'email'        => 'sukoyo@perusahaan.com',
                'password'     => Hash::make('ChangeMe123!'),
                'role'         => 'Foreman',
                'employee_id'  => 'EMP-041',
                'department'   => 'Produksi Stamping',
                'is_active'    => true,
            ],
            [
                'name'         => 'Sapto',
                'email'        => 'sapto@perusahaan.com',
                'password'     => Hash::make('ChangeMe123!'),
                'role'         => 'Foreman',
                'employee_id'  => 'EMP-042',
                'department'   => 'Produksi Stamping',
                'is_active'    => true,
            ],

            // ── Produksi Metal Finish ─────────────────────────────────────
            // Foreman: Harjito (EMP-040) & Sukoyo (EMP-041) — sudah ada
            // Supervisor: Sapriadi (EMP-032) & Bayu BR (EMP-033) — sudah ada

            // ── Logistic ──────────────────────────────────────────────────
            [
                'name'         => 'Rindhon',
                'email'        => 'rindhon@perusahaan.com',
                'password'     => Hash::make('ChangeMe123!'),
                'role'         => 'Foreman',
                'employee_id'  => 'EMP-050',
                'department'   => 'Logistic',
                'is_active'    => true,
            ],
            [
                'name'         => 'Imam',
                'email'        => 'imam@perusahaan.com',
                'password'     => Hash::make('ChangeMe123!'),
                'role'         => 'Foreman',
                'employee_id'  => 'EMP-051',
                'department'   => 'Logistic',
                'is_active'    => true,
            ],
            [
                'name'         => 'Alvyn',
                'email'        => 'alvyn@perusahaan.com',
                'password'     => Hash::make('ChangeMe123!'),
                'role'         => 'Supervisor',
                'employee_id'  => 'EMP-052',
                'department'   => 'Logistic',
                'is_active'    => true,
            ],

            // ── PPC ───────────────────────────────────────────────────────
            // Supervisor PPC = Alberta (EMP-021) — sudah ada
            [
                'name'         => 'Abdul',
                'email'        => 'abdul@perusahaan.com',
                'password'     => Hash::make('ChangeMe123!'),
                'role'         => 'staff',
                'employee_id'  => 'EMP-060',
                'department'   => 'PPC',
                'is_active'    => true,
            ],

            // ── Delivery ──────────────────────────────────────────────────
            // Supervisor Delivery = Alvyn (EMP-052) — sudah ada
            [
                'name'         => 'Aldy',
                'email'        => 'aldy@perusahaan.com',
                'password'     => Hash::make('ChangeMe123!'),
                'role'         => 'staff',
                'employee_id'  => 'EMP-070',
                'department'   => 'Delivery',
                'is_active'    => true,
            ],
            [
                'name'         => 'Muhadi',
                'email'        => 'muhadi@perusahaan.com',
                'password'     => Hash::make('ChangeMe123!'),
                'role'         => 'staff',
                'employee_id'  => 'EMP-071',
                'department'   => 'Delivery',
                'is_active'    => true,
            ],

            // ── Procurement ───────────────────────────────────────────────
            [
                'name'         => 'Supranjono',
                'email'        => 'supranjono@perusahaan.com',
                'password'     => Hash::make('ChangeMe123!'),
                'role'         => 'staff',
                'employee_id'  => 'EMP-080',
                'department'   => 'Procurement',
                'is_active'    => true,
            ],
            [
                'name'         => 'Fauzan',
                'email'        => 'fauzan@perusahaan.com',
                'password'     => Hash::make('ChangeMe123!'),
                'role'         => 'Supervisor',
                'employee_id'  => 'EMP-081',
                'department'   => 'Procurement',
                'is_active'    => true,
            ],

            // ── Dies Shop ─────────────────────────────────────────────────
            [
                'name'         => 'Akbar',
                'email'        => 'akbar@perusahaan.com',
                'password'     => Hash::make('ChangeMe123!'),
                'role'         => 'staff',
                'employee_id'  => 'EMP-090',
                'department'   => 'Dies Shop',
                'is_active'    => true,
            ],
            [
                'name'         => 'Widayat',
                'email'        => 'widayat@perusahaan.com',
                'password'     => Hash::make('ChangeMe123!'),
                'role'         => 'staff',
                'employee_id'  => 'EMP-091',
                'department'   => 'Dies Shop',
                'is_active'    => true,
            ],
            [
                'name'         => 'Omida',
                'email'        => 'omida@perusahaan.com',
                'password'     => Hash::make('ChangeMe123!'),
                'role'         => 'Supervisor',
                'employee_id'  => 'EMP-092',
                'department'   => 'Dies Shop',
                'is_active'    => true,
            ],

            // ── Plant Service ─────────────────────────────────────────────
            [
                'name'         => 'Anhar',
                'email'        => 'anhar@perusahaan.com',
                'password'     => Hash::make('ChangeMe123!'),
                'role'         => 'Foreman',
                'employee_id'  => 'EMP-100',
                'department'   => 'Plant Service',
                'is_active'    => true,
            ],
            [
                'name'         => 'Dedd Purwanto',
                'email'        => 'dedd.purwanto@perusahaan.com',
                'password'     => Hash::make('ChangeMe123!'),
                'role'         => 'Supervisor',
                'employee_id'  => 'EMP-101',
                'department'   => 'Plant Service',
                'is_active'    => true,
            ],
            // ── Operator ─────────────────────────────────────────────
            [
                'name'        => 'Didik Hariyanto',
                'email'       => 'didik.hariyanto@perusahaan.com',
                'password'    => Hash::make('ChangeMe123!'),
                'role'        => 'Operator',
                'employee_id' => 'EMP-102',
                'department'  => 'Quality Control',
                'is_active'   => true,
            ],
            [
                'name'        => 'Donny Aditya',
                'email'       => 'donny.aditya@perusahaan.com',
                'password'    => Hash::make('ChangeMe123!'),
                'role'        => 'Operator',
                'employee_id' => 'EMP-103',
                'department'  => 'Quality Control',
                'is_active'   => true,
            ],
            [
                'name'        => 'Ronna Maryanto',
                'email'       => 'ronna.maryanto@perusahaan.com',
                'password'    => Hash::make('ChangeMe123!'),
                'role'        => 'Operator',
                'employee_id' => 'EMP-104',
                'department'  => 'Quality Control',
                'is_active'   => true,
            ],
            [
                'name'        => 'Angga Prasetiyantoro',
                'email'       => 'angga.prasetiyantoro@perusahaan.com',
                'password'    => Hash::make('ChangeMe123!'),
                'role'        => 'Operator',
                'employee_id' => 'EMP-105',
                'department'  => 'Quality Control',
                'is_active'   => true,
            ],
            [
                'name'        => 'Mahmud Yusup',
                'email'       => 'mahmud.yusup@perusahaan.com',
                'password'    => Hash::make('ChangeMe123!'),
                'role'        => 'Operator',
                'employee_id' => 'EMP-106',
                'department'  => 'Quality Control',
                'is_active'   => true,
            ],
            [
                'name'        => 'Kristi Handoko',
                'email'       => 'kristi.handoko@perusahaan.com',
                'password'    => Hash::make('ChangeMe123!'),
                'role'        => 'Operator',
                'employee_id' => 'EMP-107',
                'department'  => 'Quality Control',
                'is_active'   => true,
            ],
            [
                'name'        => 'Chandra',
                'email'       => 'chandra@perusahaan.com',
                'password'    => Hash::make('ChangeMe123!'),
                'role'        => 'Operator',
                'employee_id' => 'EMP-108',
                'department'  => 'Quality Control',
                'is_active'   => true,
            ],
        ];

        foreach ($users as $user) {
            DB::table('users')->insertOrIgnore(array_merge($user, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        $this->command->info('✅ UserSeeder: ' . count($users) . ' user berhasil di-seed.');
        $this->command->warn('⚠  Default password: ChangeMe123! — minta setiap user ganti password setelah login pertama.');
    }
}