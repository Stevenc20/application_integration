<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Department;
use App\Models\Section;
use App\Models\Position;
use App\Models\User;
use App\Models\Karyawan;
use App\Models\Feature;
use App\Models\DepartmentPositionFeature;
use Illuminate\Database\Seeder;

class UserStructureSeeder extends Seeder
{
    public function run(): void
    {
        $roles = Role::pluck('id', 'role_name');
        $departments = Department::pluck('id', 'department_name');
        $sections = Section::pluck('id', 'section_name');
        $positions = Position::pluck('id', 'position_name');

        // Mapping role lama ke role_id
        $roleMap = [
            'superadmin'  => 'SuperAdmin',
            'admin'       => 'Admin',
            'direktur'    => 'Director',
            'presdir'     => 'Director',
            'kadiv'       => 'User',
            'manager'     => 'User',
            'supervisor'  => 'User',
            'foreman'     => 'User',
            'leader'      => 'User',
            'leader a'    => 'User',
            'leader b'    => 'User',
            'leader c'    => 'User',
            'leader d'    => 'User',
            'shearing'    => 'User',
            'handwork'    => 'User',
            'operator'    => 'User',
            'ppc'         => 'User',
            'quality'     => 'User',
            'production'  => 'User',
            'dies_shop'   => 'User',
            'plant_service' => 'User',
            'irm'         => 'User',
            'logistik'    => 'User',
            'produksi'    => 'User',
            'hambatan'    => 'User',
        ];

        // Mapping role lama ke department
        $deptMap = [
            'kadiv'        => 'Corporate',
            'direktur'     => 'Corporate',
            'presdir'      => 'Corporate',
            'manager'      => 'Produksi',
            'supervisor'   => 'Produksi',
            'foreman'      => 'Produksi',
            'leader'       => 'Produksi',
            'leader a'     => 'Produksi',
            'leader b'     => 'Produksi',
            'leader c'     => 'Produksi',
            'leader d'     => 'Produksi',
            'shearing'     => 'Produksi',
            'handwork'     => 'Produksi',
            'operator'     => 'Produksi',
            'production'   => 'Produksi',
            'ppc'          => 'PPIC',
            'quality'      => 'Quality',
            'irm'          => 'Quality',
            'dies_shop'    => 'Maintenance',
            'plant_service' => 'Maintenance',
            'logistik'     => 'Produksi',
            'produksi'     => 'Produksi',
            'hambatan'     => 'Maintenance',
        ];

        // Mapping role lama ke section
        $sectionMap = [
            'kadiv'        => 'Direksi',
            'direktur'     => 'Direksi',
            'presdir'      => 'Direksi',
            'manager'      => 'Stamping',
            'supervisor'   => 'Stamping',
            'foreman'      => 'Stamping',
            'leader'       => 'Stamping',
            'leader a'     => 'Stamping',
            'leader b'     => 'Stamping',
            'leader c'     => 'Stamping',
            'leader d'     => 'Stamping',
            'shearing'     => 'Stamping',
            'handwork'     => 'Stamping',
            'operator'     => 'Stamping',
            'production'   => 'Stamping',
            'ppc'          => 'PPC & RM',
            'quality'      => 'Process Quality',
            'irm'          => 'Incoming Quality',
            'dies_shop'    => 'Dies Shop',
            'plant_service' => 'Plant Service',
            'logistik'     => null,
            'produksi'     => null,
            'hambatan'     => null,
        ];

        // Mapping karyawans.jabatan ke position name
        $jabatanToPosition = [
            'supervisor' => 'SPV',
            'foreman'    => 'Foreman',
            'leader'     => 'Leader',
            'operator'   => 'Member',
            'admin'      => null,
            'manager'    => 'Manager',
            'kadiv'      => 'Kepala Divisi',
            'direktur'   => 'Direktur',
            'presdir'    => 'Presdir',
        ];

        $karyawans = Karyawan::pluck('jabatan', 'nrp_karyawan');

        foreach (User::all() as $user) {
            $oldRole = strtolower($user->role);
            $nrp = $user->nrp;

            // role_id
            $newRoleName = $roleMap[$oldRole] ?? 'User';
            $user->role_id = $roles[$newRoleName] ?? null;

            // department_id
            $deptName = $deptMap[$oldRole] ?? null;
            $user->department_id = $deptName ? ($departments[$deptName] ?? null) : null;

            // section_id
            $sectionName = $sectionMap[$oldRole] ?? null;
            $user->section_id = $sectionName ? ($sections[$sectionName] ?? null) : null;

            // position_id: try from karyawans first, then from role mapping
            $positionName = null;
            $positionFromKaryawan = false;
            if ($nrp && isset($karyawans[$nrp])) {
                $jabatan = strtolower($karyawans[$nrp]);
                $pos = $jabatanToPosition[$jabatan] ?? '__NOT_SET__';
                if ($pos !== '__NOT_SET__') {
                    $positionName = $pos; // could be null (e.g., admin)
                    $positionFromKaryawan = true;
                }
            }

            if (!$positionFromKaryawan) {
                // Fallback: position by role (only if karyawan didn't provide one)
                $positionName = match($oldRole) {
                    'superadmin' => null,
                    'admin' => null,
                    'manager' => 'Manager',
                    'kadiv' => 'Kepala Divisi',
                    'supervisor' => 'SPV',
                    'foreman' => 'Foreman',
                    'leader', 'leader a', 'leader b', 'leader c', 'leader d', 'shearing', 'handwork' => 'Leader',
                    'operator' => 'Member',
                    'ppc' => 'SPV',
                    'quality' => 'SPV',
                    'production' => 'Member',
                    'dies_shop' => 'Leader',
                    'plant_service' => 'Leader',
                    'irm' => 'Leader',
                    'logistik' => 'Member',
                    'produksi' => 'Member',
                    'hambatan' => 'Member',
                    'direktur' => 'Direktur',
                    'presdir' => 'Presdir',
                    default => 'Member',
                };
            }
            $user->position_id = $positionName ? ($positions[$positionName] ?? null) : null;

            $user->save();
        }

        // Copy all role_feature entries to department_position_feature
        $allFeatures = Feature::pluck('id', 'feature_code');
        $deptPosCombos = [];

        foreach (User::all() as $user) {
            if ($user->department_id && $user->position_id) {
                $key = $user->department_id . '-' . $user->position_id;
                $deptPosCombos[$key] = [
                    'department_id' => $user->department_id,
                    'position_id' => $user->position_id,
                ];
            }
        }

        foreach ($deptPosCombos as $combo) {
            foreach ($allFeatures as $featureCode => $featureId) {
                DepartmentPositionFeature::firstOrCreate([
                    'department_id' => $combo['department_id'],
                    'position_id' => $combo['position_id'],
                    'feature_id' => $featureId,
                ], [
                    'enabled' => true,
                ]);
            }
        }
    }
}
