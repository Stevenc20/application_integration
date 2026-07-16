<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = ['SuperAdmin', 'Admin', 'Director', 'User'];
        foreach ($roles as $name) {
            Role::firstOrCreate(['role_name' => $name]);
        }
    }
}
