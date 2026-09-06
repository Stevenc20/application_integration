<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'system_role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('system_role')->default('user')->after('password');
            });
        }

        // Seed Positions
        $positions = [
            ['position_name' => 'Tim Member', 'level' => 1],
            ['position_name' => 'Leader', 'level' => 2],
            ['position_name' => 'Foreman', 'level' => 3],
            ['position_name' => 'SPV', 'level' => 4],
            ['position_name' => 'Manager', 'level' => 5],
            ['position_name' => 'Kadiv', 'level' => 6],
            ['position_name' => 'Direktur', 'level' => 7],
            ['position_name' => 'Presdir', 'level' => 8],
        ];
        
        foreach ($positions as $pos) {
            DB::table('positions')->updateOrInsert(
                ['position_name' => $pos['position_name']],
                ['level' => $pos['level'], 'created_at' => now(), 'updated_at' => now()]
            );
        }

        // Seed Department to hold sections
        $deptId = DB::table('departments')->where('department_name', 'General')->value('id');
        if (!$deptId) {
            $deptId = DB::table('departments')->insertGetId([
                'department_name' => 'General',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        // Seed Sections
        $sections = ['Produksi', 'Logistik', 'Delivery', 'PPC', 'IRM', 'Dies', 'Mesin', 'Quality'];
        foreach ($sections as $sec) {
            DB::table('sections')->updateOrInsert(
                ['department_id' => $deptId, 'section_name' => $sec],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }

        // Mapping Logic
        $users = DB::table('users')->get();
        foreach ($users as $u) {
            $oldRole = strtolower(trim($u->role));
            $systemRole = 'user';
            $positionId = null;
            $sectionId = null;

            if (in_array($oldRole, ['superadmin', 'super admin', 'super_admin'])) {
                $systemRole = 'superadmin';
            } elseif (in_array($oldRole, ['admin', 'administrator'])) {
                $systemRole = 'admin';
            } else {
                $systemRole = 'user';
                
                // Guess position
                if (str_contains($oldRole, 'foreman')) {
                    $positionId = DB::table('positions')->where('position_name', 'Foreman')->value('id');
                } elseif (str_contains($oldRole, 'leader')) {
                    $positionId = DB::table('positions')->where('position_name', 'Leader')->value('id');
                } elseif (str_contains($oldRole, 'supervisor') || str_contains($oldRole, 'spv')) {
                    $positionId = DB::table('positions')->where('position_name', 'SPV')->value('id');
                } else {
                    // Default to Tim Member for operator, shearing, handwork, etc.
                    $positionId = DB::table('positions')->where('position_name', 'Tim Member')->value('id');
                }

                // Guess section
                if (str_contains($oldRole, 'ppc')) {
                    $sectionId = DB::table('sections')->where('section_name', 'PPC')->value('id');
                } elseif (str_contains($oldRole, 'logistik')) {
                    $sectionId = DB::table('sections')->where('section_name', 'Logistik')->value('id');
                } elseif (str_contains($oldRole, 'delivery')) {
                    $sectionId = DB::table('sections')->where('section_name', 'Delivery')->value('id');
                } elseif (str_contains($oldRole, 'irm')) {
                    $sectionId = DB::table('sections')->where('section_name', 'IRM')->value('id');
                } elseif (str_contains($oldRole, 'dies')) {
                    $sectionId = DB::table('sections')->where('section_name', 'Dies')->value('id');
                } elseif (str_contains($oldRole, 'mesin') || str_contains($oldRole, 'plant_service')) {
                    $sectionId = DB::table('sections')->where('section_name', 'Mesin')->value('id');
                } elseif (str_contains($oldRole, 'quality') || str_contains($oldRole, 'qc')) {
                    $sectionId = DB::table('sections')->where('section_name', 'Quality')->value('id');
                } else {
                    $sectionId = DB::table('sections')->where('section_name', 'Produksi')->value('id');
                }
            }

            DB::table('users')->where('id', $u->id)->update([
                'system_role' => $systemRole,
                'position_id' => $positionId,
                'section_id' => $sectionId,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('system_role');
        });
    }
};
