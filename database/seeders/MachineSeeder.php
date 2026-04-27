<?php

namespace Database\Seeders;
use App\Models\Machine;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MachineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
    Machine::create(['name' => 'Machine 1','line' => 'A']);
    Machine::create(['name' => 'Machine 2','line' => 'A']);
    Machine::create(['name' => 'Machine 3','line' => 'B']);
    }
}
