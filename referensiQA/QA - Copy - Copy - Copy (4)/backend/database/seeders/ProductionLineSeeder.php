<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductionLineSeeder extends Seeder
{
    public function run(): void
    {
        $lines = [
            ['code' => 'LINE-01', 'name' => 'Line Assembly A', 'department' => 'Produksi'],
            ['code' => 'LINE-02', 'name' => 'Line Assembly B', 'department' => 'Produksi'],
            ['code' => 'LINE-03', 'name' => 'Line Machining',  'department' => 'Produksi'],
            ['code' => 'LINE-04', 'name' => 'Line Painting',   'department' => 'Finishing'],
        ];

        foreach ($lines as $line) {
            DB::table('production_lines')->insertOrIgnore(array_merge($line, [
                'is_active'  => true,
                'is_stopped' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}