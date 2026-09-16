<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\Bom;
use App\Models\Material;
use Illuminate\Support\Facades\DB;

class BomSeeder extends Seeder
{
    public function run()
    {
        if (Bom::count() > 0) return;

        $today = date('Y-m-d');

        $bomDefs = [
            ['fp' => 'D52131 BZ170-00', 'base_qty' => 1, 'desc' => 'BOM S-1013', 'items' => [
                ['kode' => 'DG591.80X1180X0265', 'qty' => 1, 'unit' => 'SHT', 'note' => 'Coil'],
                ['kode' => 'VRM-940149',         'qty' => 0.5, 'unit' => 'KG', 'note' => 'Lubricant'],
            ]],
            ['fp' => 'D58169 BZ040', 'base_qty' => 1, 'desc' => 'BOM AES-009', 'items' => [
                ['kode' => 'DG5D1.20X1060X0215', 'qty' => 1, 'unit' => 'SHT', 'note' => 'Coil AES-011'],
                ['kode' => 'VRM-940273',         'qty' => 0.3, 'unit' => 'KG', 'note' => 'Lubricant'],
            ]],
            ['fp' => 'D58169 BZ050', 'base_qty' => 1, 'desc' => 'BOM AES-010', 'items' => [
                ['kode' => 'DG5D1.20X1060X0215', 'qty' => 1, 'unit' => 'SHT', 'note' => 'Coil AES-011'],
                ['kode' => 'VRM-940113',         'qty' => 0.4, 'unit' => 'KG', 'note' => 'Lubricant'],
            ]],
            ['fp' => 'D67147 BZ180-00', 'base_qty' => 1, 'desc' => 'BOM S-4030', 'items' => [
                ['kode' => 'DGAD1.20X0540X0710', 'qty' => 1, 'unit' => 'SHT', 'note' => 'Coil S-4030'],
                ['kode' => 'VRM-948408',         'qty' => 0.2, 'unit' => 'KG', 'note' => 'Lubricant'],
            ]],
            ['fp' => 'D67148 BZ180-00', 'base_qty' => 1, 'desc' => 'BOM S-4031', 'items' => [
                ['kode' => 'DGAD1.20X0540X0710', 'qty' => 1, 'unit' => 'SHT', 'note' => 'Coil S-4030'],
                ['kode' => 'VRM-898474',         'qty' => 0.3, 'unit' => 'KG', 'note' => 'Lubricant'],
            ]],
            ['fp' => 'DV61031 BZ240-00', 'base_qty' => 1, 'desc' => 'BOM BX-0022', 'items' => [
                ['kode' => 'DD270.65X0767X0510', 'qty' => 1, 'unit' => 'SHT', 'note' => 'Coil GBA-0114'],
                ['kode' => 'VRM-914701',         'qty' => 0.5, 'unit' => 'KG', 'note' => 'Lubricant'],
            ]],
            ['fp' => 'DV61607 BZ160-00', 'base_qty' => 1, 'desc' => 'BOM BX-0028', 'items' => [
                ['kode' => 'DD270.65X0800X1790', 'qty' => 1, 'unit' => 'SHT', 'note' => 'Coil GBA-0118'],
                ['kode' => 'VRM-940251',         'qty' => 0.4, 'unit' => 'KG', 'note' => 'Lubricant'],
            ]],
            ['fp' => 'G61621 BZ110-00', 'base_qty' => 1, 'desc' => 'BOM NA-053', 'items' => [
                ['kode' => 'DG272.00X0466X0504', 'qty' => 1, 'unit' => 'SHT', 'note' => 'Coil AES-016'],
                ['kode' => 'VRM-940073',         'qty' => 0.3, 'unit' => 'KG', 'note' => 'Lubricant'],
            ]],
            ['fp' => 'G61622 BZ140-00', 'base_qty' => 1, 'desc' => 'BOM NA-056', 'items' => [
                ['kode' => 'DG272.00X0466X0586', 'qty' => 1, 'unit' => 'SHT', 'note' => 'Coil AES-017'],
                ['kode' => 'VRM-910061',         'qty' => 0.3, 'unit' => 'KG', 'note' => 'Lubricant'],
            ]],
            ['fp' => 'T55741 BZ560-00-26', 'base_qty' => 1, 'desc' => 'BOM T073', 'items' => [
                ['kode' => 'DG591.80X1200X0387', 'qty' => 1, 'unit' => 'SHT', 'note' => 'Coil GBA-0109'],
                ['kode' => 'VRM-940149',         'qty' => 0.6, 'unit' => 'KG', 'note' => 'Lubricant'],
            ]],
            ['wip' => 'D55741 BZ560', 'base_qty' => 1, 'desc' => 'BOM WIP AES-023', 'items' => [
                ['kode' => 'DG591.80X1180X0265', 'qty' => 1, 'unit' => 'SHT', 'note' => 'Coil S-1013'],
            ]],
            ['wip' => 'D58114 BZ070', 'base_qty' => 1, 'desc' => 'BOM WIP AES-011', 'items' => [
                ['kode' => 'DC590.90X0605X0305', 'qty' => 1, 'unit' => 'SHT', 'note' => 'Coil AES-006'],
            ]],
            ['wip' => 'D57453 BZ100', 'base_qty' => 1, 'desc' => 'BOM WIP AES-048', 'items' => [
                ['kode' => 'DGAC1.40X1100X0386', 'qty' => 1, 'unit' => 'SHT', 'note' => 'Coil GBA-0110'],
                ['kode' => 'VRM-940273',         'qty' => 0.2, 'unit' => 'KG', 'note' => 'Lubricant'],
            ]],
        ];

        DB::transaction(function () use ($bomDefs, $today) {
            $seq = Bom::max('id') ?? 0;
            foreach ($bomDefs as $def) {
                $code = $def['fp'] ?? $def['wip'] ?? null;
                $mat = Material::where('kode', $code)->first();
                if (!$mat) continue;

                $seq++;
                $bom = Bom::create([
                    'bom_number'    => 'BOM-' . str_pad($seq, 5, '0', STR_PAD_LEFT),
                    'material_id'   => $mat->id,
                    'base_quantity' => $def['base_qty'],
                    'valid_from'    => $today,
                    'status'        => 'active',
                    'description'   => $def['desc'] ?? null,
                ]);

                foreach ($def['items'] as $item) {
                    $compMat = Material::where('kode', $item['kode'])->first();
                    if (!$compMat) continue;
                    $bom->items()->create([
                        'material_id' => $compMat->id,
                        'quantity'    => $item['qty'],
                        'unit'        => $item['unit'],
                        'notes'       => $item['note'] ?? null,
                    ]);
                }
            }
        });
    }
}
