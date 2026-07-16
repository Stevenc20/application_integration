<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProductionOrder;
use App\Models\Material;
use App\Models\Bom;
use App\Models\User;

class ProductionOrderSeeder extends Seeder
{
    public function run()
    {
        $creator = User::first();
        if (!$creator) return;

        $orders = [
            ['order_number' => 'PRO-2026-00010', 'material_kode' => 'IRM-09322803', 'quantity_planned' => 495, 'planned_start_date' => '2026-06-05', 'planned_end_date' => '2026-06-05', 'status' => 'completed'],
            ['order_number' => 'PRO-2026-00011', 'material_kode' => 'VRM-94025301', 'quantity_planned' => 800, 'planned_start_date' => '2026-06-05', 'planned_end_date' => '2026-06-05', 'status' => 'completed'],
            ['order_number' => 'PRO-2026-00012', 'material_kode' => 'IRM-09326701', 'quantity_planned' => 120, 'planned_start_date' => '2026-06-05', 'planned_end_date' => '2026-06-05', 'status' => 'completed'],
            ['order_number' => 'PRO-2026-00013', 'material_kode' => 'IRM-09326101', 'quantity_planned' => 120, 'planned_start_date' => '2026-06-05', 'planned_end_date' => '2026-06-05', 'status' => 'completed'],
            ['order_number' => 'PRO-2026-00014', 'material_kode' => 'IRM-95004001', 'quantity_planned' => 100, 'planned_start_date' => '2026-06-05', 'planned_end_date' => '2026-06-05', 'status' => 'completed'],
            ['order_number' => 'PRO-2026-00015', 'material_kode' => 'IRM-95004601', 'quantity_planned' => 140, 'planned_start_date' => '2026-06-05', 'planned_end_date' => '2026-06-05', 'status' => 'completed'],
            ['order_number' => 'PRO-2026-00016', 'material_kode' => 'IRM-09322102', 'quantity_planned' => 180, 'planned_start_date' => '2026-06-05', 'planned_end_date' => '2026-06-05', 'status' => 'completed'],
            ['order_number' => 'PRO-2026-00017', 'material_kode' => 'VRM-94025301', 'quantity_planned' => 1600, 'planned_start_date' => '2026-06-05', 'planned_end_date' => '2026-06-05', 'status' => 'completed'],
            ['order_number' => 'PRO-2026-00018', 'material_kode' => 'IRM-05910302', 'quantity_planned' => 80, 'planned_start_date' => '2026-06-05', 'planned_end_date' => '2026-06-05', 'status' => 'released'],
            ['order_number' => 'PRO-2026-00019', 'material_kode' => 'VRM-95001101', 'quantity_planned' => 500, 'planned_start_date' => '2026-06-05', 'planned_end_date' => '2026-06-06', 'status' => 'released'],
            ['order_number' => 'PRO-2026-00020', 'material_kode' => 'IRM-95005301', 'quantity_planned' => 1500, 'planned_start_date' => '2026-06-06', 'planned_end_date' => '2026-06-07', 'status' => 'released'],
            ['order_number' => 'PRO-2026-00021', 'material_kode' => 'IRM-95002201', 'quantity_planned' => 250, 'planned_start_date' => '2026-06-06', 'planned_end_date' => '2026-06-08', 'status' => 'created'],
        ];

        foreach ($orders as $o) {
            $material = Material::where('kode', $o['material_kode'])->first();
            if (!$material) continue;

            $bom = Bom::where('material_id', $material->id)->where('status', 'active')->first();

            ProductionOrder::firstOrCreate(
                ['order_number' => $o['order_number']],
                [
                    'material_id'        => $material->id,
                    'bom_id'             => $bom?->id,
                    'quantity_planned'   => $o['quantity_planned'],
                    'planned_start_date' => $o['planned_start_date'],
                    'planned_end_date'   => $o['planned_end_date'],
                    'status'             => $o['status'],
                    'created_by'         => $creator->id,
                ]
            );
        }
    }
}
