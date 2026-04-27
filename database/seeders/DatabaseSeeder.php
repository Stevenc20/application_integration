<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\ProductionProcessSeeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ProductionProcessSeeder::class);
    }
}