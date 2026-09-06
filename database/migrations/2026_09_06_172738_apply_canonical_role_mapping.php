<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Fix Positions Hierarchy
        $positions = [
            ['name' => 'Tim Member', 'level' => 1],
            ['name' => 'Leader', 'level' => 2],
            ['name' => 'Foreman', 'level' => 3],
            ['name' => 'SPV', 'level' => 4],
            ['name' => 'Manager', 'level' => 5],
            ['name' => 'Kadiv', 'level' => 6],
            ['name' => 'Direktur', 'level' => 7],
            ['name' => 'Presdir', 'level' => 8],
        ];

        foreach ($positions as $pos) {
            $existing = DB::table('positions')->where('position_name', $pos['name'])->first();
            if ($existing) {
                DB::table('positions')->where('id', $existing->id)->update(['level' => $pos['level']]);
            } else {
                DB::table('positions')->insert([
                    'position_name' => $pos['name'],
                    'level' => $pos['level'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

        // 2. Fix Sections
        $deptId = DB::table('departments')->where('department_name', 'General')->value('id');
        if (!$deptId) {
            $deptId = DB::table('departments')->insertGetId([
                'department_name' => 'General',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        $canonicalSections = ['Produksi', 'Logistik', 'Delivery', 'PPC', 'IRM', 'Dies', 'Mesin', 'Quality'];
        foreach ($canonicalSections as $sec) {
            $existing = DB::table('sections')->where('section_name', $sec)->first();
            if (!$existing) {
                DB::table('sections')->insert([
                    'department_id' => $deptId,
                    'section_name' => $sec,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

        // 3. Safe Legacy Mapping
        $users = DB::table('users')->get();
        foreach ($users as $user) {
            $oldRole = strtolower(trim($user->role));
            
            $updateData = [];
            
            // --- SYSTEM ROLE ---
            if ($oldRole === 'admin') {
                $updateData['system_role'] = 'admin';
            } else {
                $updateData['system_role'] = 'user';
            }

            // --- POSITION ---
            if (!$user->position_id) { // Don't overwrite if manually set
                $posName = null;
                switch ($oldRole) {
                    case 'presdir': $posName = 'Presdir'; break;
                    case 'direktur': $posName = 'Direktur'; break;
                    case 'kadiv': $posName = 'Kadiv'; break;
                    case 'manager': $posName = 'Manager'; break;
                    case 'supervisor': $posName = 'SPV'; break;
                    case 'foreman': $posName = 'Foreman'; break;
                    case 'leader a':
                    case 'leader b': $posName = 'Leader'; break;
                    case 'operator': $posName = 'Tim Member'; break;
                }
                if ($posName) {
                    $pid = DB::table('positions')->where('position_name', $posName)->value('id');
                    if ($pid) $updateData['position_id'] = $pid;
                }
            }

            // --- SECTION ---
            if (!$user->section_id) { // Don't overwrite
                $secName = null;
                switch ($oldRole) {
                    case 'ppc': $secName = 'PPC'; break;
                    case 'irm': $secName = 'IRM'; break;
                    case 'logistik': $secName = 'Logistik'; break;
                    case 'dies_shop': $secName = 'Dies'; break;
                    case 'production':
                    case 'produksi': $secName = 'Produksi'; break;
                    case 'quality': $secName = 'Quality'; break;
                }
                if ($secName) {
                    $sid = DB::table('sections')->where('section_name', $secName)->value('id');
                    if ($sid) $updateData['section_id'] = $sid;
                }
            }

            if (!empty($updateData)) {
                DB::table('users')->where('id', $user->id)->update($updateData);
            }
        }
    }

    public function down(): void
    {
        // No down needed as we don't drop anything.
    }
};
