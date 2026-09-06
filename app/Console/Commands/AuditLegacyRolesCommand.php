<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AuditLegacyRolesCommand extends Command
{
    protected $signature = 'role:audit';
    protected $description = 'Audit existing roles and generate a mapping report';

    public function handle()
    {
        $this->info("=== AUDIT LEGACY ROLES ===");
        
        $roles = DB::table('users')
            ->select('role', DB::raw('count(*) as total'))
            ->groupBy('role')
            ->orderBy('role')
            ->get();
            
        $this->table(['Legacy Role', 'User Count'], $roles->map(function($r) {
            return [$r->role ?? 'NULL', $r->total];
        }));

        $this->info("\n=== EXISTING POSITIONS ===");
        $positions = DB::table('positions')->get();
        if ($positions->isEmpty()) {
            $this->warn("No positions found in DB.");
        } else {
            $this->table(['ID', 'Name', 'Level'], $positions->map(function($p) {
                return [$p->id, $p->position_name, $p->level];
            }));
        }

        $this->info("\n=== EXISTING SECTIONS ===");
        $sections = DB::table('sections')->get();
        if ($sections->isEmpty()) {
            $this->warn("No sections found in DB.");
        } else {
            $this->table(['ID', 'Name', 'Dept ID'], $sections->map(function($s) {
                return [$s->id, $s->section_name, $s->department_id];
            }));
        }
    }
}
