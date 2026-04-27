<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProductionProcess;
use Carbon\Carbon;

class ProductionProcessSeeder extends Seeder
{
    public function run(): void
    {
        $processes = ['Stamping', 'Sub Assy', 'Shearing', 'Metal Finish'];
        $shifts = ['Shift 1', 'Shift 2', 'Shift 3'];
        $statuses = ['approved', 'pending'];

        for ($i = 0; $i < 30; $i++) {

            ProductionProcess::create([
                'production_order_number' => 'PO-' . rand(1000, 9999),
                'process_type' => $processes[array_rand($processes)],
                'shift' => $shifts[array_rand($shifts)],
                'qty_ok' => rand(100, 300),
                'qty_repair' => rand(0, 20),
                'qty_reject' => rand(0, 10),
                'status' => $statuses[array_rand($statuses)],

                // Random date (last 3 days)
                'created_at' => Carbon::now()->subDays(rand(0, 2)),
                'updated_at' => now(),
            ]);
        }
    }
}