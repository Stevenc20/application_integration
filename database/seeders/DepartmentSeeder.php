<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = ['Corporate', 'PPIC', 'Produksi', 'Maintenance', 'Quality'];
        foreach ($departments as $name) {
            Department::firstOrCreate(['department_name' => $name]);
        }
    }
}
