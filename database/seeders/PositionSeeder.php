<?php

namespace Database\Seeders;

use App\Models\Position;
use Illuminate\Database\Seeder;

class PositionSeeder extends Seeder
{
    public function run(): void
    {
        $positions = [
            ['position_name' => 'Presdir',        'level' => 0],
            ['position_name' => 'Direktur',       'level' => 1],
            ['position_name' => 'Kepala Divisi',  'level' => 2],
            ['position_name' => 'Manager',        'level' => 3],
            ['position_name' => 'SPV',            'level' => 4],
            ['position_name' => 'Foreman',        'level' => 5],
            ['position_name' => 'Leader',         'level' => 6],
            ['position_name' => 'Member',         'level' => 7],
        ];

        foreach ($positions as $p) {
            Position::firstOrCreate(
                ['position_name' => $p['position_name']],
                $p
            );
        }
    }
}
