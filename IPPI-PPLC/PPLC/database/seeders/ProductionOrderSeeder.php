<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProductionOrder;

class ProductionOrderSeeder extends Seeder
{
    public function run()
    {
        $orders = [
            ['no_order' => 'PRO-2026-00010', 'material_kode' => 'IRM-09322803', 'material_nama' => 'PH-027N', 'qty_plan' => 495, 'start_date' => '2026-06-05', 'end_date' => '2026-06-05', 'status' => 'Completed'],
            ['no_order' => 'PRO-2026-00011', 'material_kode' => 'VRM-94025301', 'material_nama' => 'AAS-028', 'qty_plan' => 800, 'start_date' => '2026-06-05', 'end_date' => '2026-06-05', 'status' => 'Completed'],
            ['no_order' => 'PRO-2026-00012', 'material_kode' => 'IRM-09326701', 'material_nama' => 'PH-039GU', 'qty_plan' => 120, 'start_date' => '2026-06-05', 'end_date' => '2026-06-05', 'status' => 'Completed'],
            ['no_order' => 'PRO-2026-00013', 'material_kode' => 'IRM-09326101', 'material_nama' => 'IF-017', 'qty_plan' => 120, 'start_date' => '2026-06-05', 'end_date' => '2026-06-05', 'status' => 'Completed'],
            ['no_order' => 'PRO-2026-00014', 'material_kode' => 'IRM-95004001', 'material_nama' => 'PH-032N', 'qty_plan' => 100, 'start_date' => '2026-06-05', 'end_date' => '2026-06-05', 'status' => 'Completed'],
            ['no_order' => 'PRO-2026-00015', 'material_kode' => 'IRM-95004601', 'material_nama' => 'PH-039U', 'qty_plan' => 140, 'start_date' => '2026-06-05', 'end_date' => '2026-06-05', 'status' => 'Completed'],
            ['no_order' => 'PRO-2026-00016', 'material_kode' => 'IRM-09322102', 'material_nama' => 'PH-077GU', 'qty_plan' => 180, 'start_date' => '2026-06-05', 'end_date' => '2026-06-05', 'status' => 'Completed'],
            ['no_order' => 'PRO-2026-00017', 'material_kode' => 'VRM-94025301', 'material_nama' => 'AAS-028', 'qty_plan' => 1600, 'start_date' => '2026-06-05', 'end_date' => '2026-06-05', 'status' => 'Completed'],
            ['no_order' => 'PRO-2026-00018', 'material_kode' => 'IRM-05910302', 'material_nama' => 'IF-026', 'qty_plan' => 80, 'start_date' => '2026-06-05', 'end_date' => '2026-06-05', 'status' => 'Released'],
            ['no_order' => 'PRO-2026-00019', 'material_kode' => 'VRM-95001101', 'material_nama' => 'AAS-023', 'qty_plan' => 500, 'start_date' => '2026-06-05', 'end_date' => '2026-06-06', 'status' => 'Released'],
            ['no_order' => 'PRO-2026-00020', 'material_kode' => 'IRM-95005301', 'material_nama' => 'PB-027', 'qty_plan' => 1500, 'start_date' => '2026-06-06', 'end_date' => '2026-06-07', 'status' => 'Released'],
            ['no_order' => 'PRO-2026-00021', 'material_kode' => 'IRM-95002201', 'material_nama' => 'PB-071', 'qty_plan' => 250, 'start_date' => '2026-06-06', 'end_date' => '2026-06-08', 'status' => 'Created'],
        ];

        foreach ($orders as $o) {
            ProductionOrder::firstOrCreate(['no_order' => $o['no_order']], $o);
        }
    }
}
