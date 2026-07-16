<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->string('nama');
            $table->string('tipe'); // WIP, FP, RM
            $table->string('uom'); // PCS, SHT
            $table->integer('qty_case')->default(0);
            $table->double('stok')->default(0.0);
            $table->double('min_stok')->default(0.0);
            $table->string('status')->default('Aktif');
            $table->timestamps();
        });

        $initialMaterials = [
            ['kode' => 'D52131 BZ170-00', 'nama' => 'S-1013', 'tipe' => 'FP', 'uom' => 'PCS', 'qty_case' => 0, 'stok' => 0.0, 'min_stok' => 300.0, 'status' => 'Aktif'],
            ['kode' => 'D55741 BZ560', 'nama' => 'AES-023', 'tipe' => 'WIP', 'uom' => 'PCS', 'qty_case' => 0, 'stok' => 300.0, 'min_stok' => 360.0, 'status' => 'Aktif'],
            ['kode' => 'D55741 BZ560-00', 'nama' => 'BX-0038', 'tipe' => 'FP', 'uom' => 'PCS', 'qty_case' => 0, 'stok' => 0.0, 'min_stok' => 195.0, 'status' => 'Aktif'],
            ['kode' => 'D55741 BZ570', 'nama' => 'AES-024', 'tipe' => 'WIP', 'uom' => 'PCS', 'qty_case' => 0, 'stok' => 0.0, 'min_stok' => 240.0, 'status' => 'Aktif'],
            ['kode' => 'D57453 BZ100', 'nama' => 'AES-048', 'tipe' => 'WIP', 'uom' => 'PCS', 'qty_case' => 0, 'stok' => 0.0, 'min_stok' => 600.0, 'status' => 'Aktif'],
            ['kode' => 'D57454 BZ150', 'nama' => 'AES-006', 'tipe' => 'WIP', 'uom' => 'PCS', 'qty_case' => 0, 'stok' => 0.0, 'min_stok' => 600.0, 'status' => 'Aktif'],
            ['kode' => 'D58114 BZ070', 'nama' => 'AES-011', 'tipe' => 'WIP', 'uom' => 'PCS', 'qty_case' => 0, 'stok' => 0.0, 'min_stok' => 500.0, 'status' => 'Aktif'],
            ['kode' => 'D58115 BZ100', 'nama' => 'AES-012', 'tipe' => 'WIP', 'uom' => 'PCS', 'qty_case' => 0, 'stok' => 0.0, 'min_stok' => 500.0, 'status' => 'Aktif'],
            ['kode' => 'D58169 BZ040', 'nama' => 'AES-009', 'tipe' => 'FP', 'uom' => 'PCS', 'qty_case' => 100, 'stok' => 300.0, 'min_stok' => 600.0, 'status' => 'Aktif'],
            ['kode' => 'D58169 BZ050', 'nama' => 'AES-010', 'tipe' => 'FP', 'uom' => 'PCS', 'qty_case' => 100, 'stok' => 900.0, 'min_stok' => 300.0, 'status' => 'Aktif'],
            ['kode' => 'D58181 BZ090', 'nama' => 'AES-005', 'tipe' => 'WIP', 'uom' => 'PCS', 'qty_case' => 0, 'stok' => 0.0, 'min_stok' => 1200.0, 'status' => 'Aktif'],
            ['kode' => 'D61623 BZ200', 'nama' => 'AES-027', 'tipe' => 'WIP', 'uom' => 'PCS', 'qty_case' => 0, 'stok' => 0.0, 'min_stok' => 600.0, 'status' => 'Aktif'],
            ['kode' => 'D61624 BZ210', 'nama' => 'AES-032', 'tipe' => 'WIP', 'uom' => 'PCS', 'qty_case' => 0, 'stok' => 0.0, 'min_stok' => 400.0, 'status' => 'Aktif'],
            ['kode' => 'D61627 BZ110', 'nama' => 'AES-028', 'tipe' => 'WIP', 'uom' => 'PCS', 'qty_case' => 100, 'stok' => 0.0, 'min_stok' => 600.0, 'status' => 'Aktif'],
            ['kode' => 'D61627 BZ120', 'nama' => 'AES-029', 'tipe' => 'WIP', 'uom' => 'PCS', 'qty_case' => 100, 'stok' => 0.0, 'min_stok' => 200.0, 'status' => 'Aktif'],
            ['kode' => 'D61627 BZ170', 'nama' => 'AES-049', 'tipe' => 'WIP', 'uom' => 'PCS', 'qty_case' => 240, 'stok' => 0.0, 'min_stok' => 200.0, 'status' => 'Aktif'],
            ['kode' => 'D61628 BZ160', 'nama' => 'AES-033', 'tipe' => 'WIP', 'uom' => 'PCS', 'qty_case' => 0, 'stok' => 0.0, 'min_stok' => 600.0, 'status' => 'Aktif'],
            ['kode' => 'D61628 BZ170', 'nama' => 'AES-034', 'tipe' => 'WIP', 'uom' => 'PCS', 'qty_case' => 0, 'stok' => 0.0, 'min_stok' => 200.0, 'status' => 'Aktif'],
            ['kode' => 'D61628 BZ220', 'nama' => 'AES-050', 'tipe' => 'WIP', 'uom' => 'PCS', 'qty_case' => 0, 'stok' => 0.0, 'min_stok' => 200.0, 'status' => 'Aktif'],
            ['kode' => 'D61657 BZ100', 'nama' => 'AES-016', 'tipe' => 'WIP', 'uom' => 'PCS', 'qty_case' => 0, 'stok' => 0.0, 'min_stok' => 600.0, 'status' => 'Aktif'],
            ['kode' => 'D61658 BZ100', 'nama' => 'AES-017', 'tipe' => 'WIP', 'uom' => 'PCS', 'qty_case' => 0, 'stok' => 0.0, 'min_stok' => 600.0, 'status' => 'Aktif'],
            ['kode' => 'D61733 BZ040', 'nama' => 'AES-039', 'tipe' => 'WIP', 'uom' => 'PCS', 'qty_case' => 0, 'stok' => 0.0, 'min_stok' => 750.0, 'status' => 'Aktif'],
            ['kode' => 'D61734 BZ040', 'nama' => 'AES-042', 'tipe' => 'WIP', 'uom' => 'PCS', 'qty_case' => 0, 'stok' => 0.0, 'min_stok' => 750.0, 'status' => 'Aktif'],
            ['kode' => 'D67147 BZ180-00', 'nama' => 'S-4030', 'tipe' => 'FP', 'uom' => 'PCS', 'qty_case' => 0, 'stok' => 0.0, 'min_stok' => 400.0, 'status' => 'Aktif'],
            ['kode' => 'D67148 BZ180-00', 'nama' => 'S-4031', 'tipe' => 'FP', 'uom' => 'PCS', 'qty_case' => 0, 'stok' => 0.0, 'min_stok' => 400.0, 'status' => 'Aktif'],
            ['kode' => 'DC590.90X0605X0305', 'nama' => 'GBA-0105', 'tipe' => 'RM', 'uom' => 'SHT', 'qty_case' => 0, 'stok' => 7000.0, 'min_stok' => 2500.0, 'status' => 'Aktif'],
            ['kode' => 'DD270.65X0767X0510', 'nama' => 'GBA-0114', 'tipe' => 'RM', 'uom' => 'SHT', 'qty_case' => 0, 'stok' => 2400.0, 'min_stok' => 1800.0, 'status' => 'Aktif'],
            ['kode' => 'DD270.65X0800X1790', 'nama' => 'GBA-0118', 'tipe' => 'RM', 'uom' => 'SHT', 'qty_case' => 0, 'stok' => 2400.0, 'min_stok' => 1600.0, 'status' => 'Aktif'],
            ['kode' => 'DE270.60X0437X0230', 'nama' => 'GBA-0116', 'tipe' => 'RM', 'uom' => 'SHT', 'qty_case' => 0, 'stok' => 2000.0, 'min_stok' => 1600.0, 'status' => 'Aktif'],
            ['kode' => 'DG272.00X0466X0504', 'nama' => 'GBA-0124', 'tipe' => 'RM', 'uom' => 'SHT', 'qty_case' => 0, 'stok' => 1800.0, 'min_stok' => 1800.0, 'status' => 'Aktif'],
            ['kode' => 'DG272.00X0466X0586', 'nama' => 'GBA-0125', 'tipe' => 'RM', 'uom' => 'SHT', 'qty_case' => 0, 'stok' => 900.0, 'min_stok' => 1800.0, 'status' => 'Aktif'],
            ['kode' => 'DG591.80X1180X0265', 'nama' => 'S-1013', 'tipe' => 'RM', 'uom' => 'SHT', 'qty_case' => 0, 'stok' => 300.0, 'min_stok' => 900.0, 'status' => 'Aktif'],
            ['kode' => 'DG591.80X1200X0387', 'nama' => 'GBA-0109', 'tipe' => 'RM', 'uom' => 'SHT', 'qty_case' => 0, 'stok' => 150.0, 'min_stok' => 300.0, 'status' => 'Aktif'],
            ['kode' => 'DG5D1.20X1060X0215', 'nama' => 'GBA-0107', 'tipe' => 'RM', 'uom' => 'SHT', 'qty_case' => 0, 'stok' => 1800.0, 'min_stok' => 3000.0, 'status' => 'Aktif'],
            ['kode' => 'DGAC1.40X1100X0386', 'nama' => 'GBA-0110', 'tipe' => 'RM', 'uom' => 'SHT', 'qty_case' => 0, 'stok' => 1000.0, 'min_stok' => 500.0, 'status' => 'Aktif'],
            ['kode' => 'DGAD0.60X1480X0423', 'nama' => 'GBA-0103', 'tipe' => 'RM', 'uom' => 'SHT', 'qty_case' => 0, 'stok' => 5700.0, 'min_stok' => 1800.0, 'status' => 'Aktif'],
            ['kode' => 'DGAD1.20X0540X0710', 'nama' => 'S-4030', 'tipe' => 'RM', 'uom' => 'SHT', 'qty_case' => 0, 'stok' => 0.0, 'min_stok' => 900.0, 'status' => 'Aktif'],
            ['kode' => 'DPC41.40X1125X0750', 'nama' => 'GBA-0127', 'tipe' => 'RM', 'uom' => 'SHT', 'qty_case' => 0, 'stok' => 750.0, 'min_stok' => 1500.0, 'status' => 'Aktif'],
            ['kode' => 'DPC41.40X1200X0172', 'nama' => 'GBA-0111', 'tipe' => 'RM', 'uom' => 'SHT', 'qty_case' => 0, 'stok' => 750.0, 'min_stok' => 500.0, 'status' => 'Aktif'],
            ['kode' => 'DV61031 BZ240-00', 'nama' => 'BX-0022', 'tipe' => 'FP', 'uom' => 'PCS', 'qty_case' => 0, 'stok' => 0.0, 'min_stok' => 750.0, 'status' => 'Aktif'],
            ['kode' => 'DV61032 BZ260-00', 'nama' => 'BX-0020', 'tipe' => 'FP', 'uom' => 'PCS', 'qty_case' => 0, 'stok' => 0.0, 'min_stok' => 750.0, 'status' => 'Aktif'],
            ['kode' => 'DV61607 BZ160-00', 'nama' => 'BX-0028', 'tipe' => 'FP', 'uom' => 'PCS', 'qty_case' => 0, 'stok' => 0.0, 'min_stok' => 400.0, 'status' => 'Aktif'],
            ['kode' => 'DV61608 BZ160-00', 'nama' => 'BX-0027', 'tipe' => 'FP', 'uom' => 'PCS', 'qty_case' => 0, 'stok' => 0.0, 'min_stok' => 400.0, 'status' => 'Aktif'],
            ['kode' => 'G61621 BZ110-00', 'nama' => 'NA-053', 'tipe' => 'FP', 'uom' => 'PCS', 'qty_case' => 0, 'stok' => 0.0, 'min_stok' => 200.0, 'status' => 'Aktif'],
            ['kode' => 'G61622 BZ140-00', 'nama' => 'NA-056', 'tipe' => 'FP', 'uom' => 'PCS', 'qty_case' => 0, 'stok' => 0.0, 'min_stok' => 200.0, 'status' => 'Aktif'],
            ['kode' => 'GV61705 BZ130-00', 'nama' => 'NA-054', 'tipe' => 'FP', 'uom' => 'PCS', 'qty_case' => 0, 'stok' => 0.0, 'min_stok' => 360.0, 'status' => 'Aktif'],
            ['kode' => 'GV61706 BZ080-00', 'nama' => 'NA-057', 'tipe' => 'FP', 'uom' => 'PCS', 'qty_case' => 0, 'stok' => 0.0, 'min_stok' => 200.0, 'status' => 'Aktif'],
            ['kode' => 'T55741 BZ560-00-26', 'nama' => 'T073', 'tipe' => 'FP', 'uom' => 'PCS', 'qty_case' => 0, 'stok' => 0.0, 'min_stok' => 150.0, 'status' => 'Aktif'],
            ['kode' => 'T55741 BZ570-00-26', 'nama' => 'T074', 'tipe' => 'FP', 'uom' => 'PCS', 'qty_case' => 0, 'stok' => 0.0, 'min_stok' => 210.0, 'status' => 'Aktif'],
            ['kode' => 'T57051 BZ340-00-26', 'nama' => 'NA-112', 'tipe' => 'FP', 'uom' => 'PCS', 'qty_case' => 0, 'stok' => 0.0, 'min_stok' => 900.0, 'status' => 'Aktif'],
            ['kode' => 'T57052 BZ230-00-28', 'nama' => 'T089', 'tipe' => 'FP', 'uom' => 'PCS', 'qty_case' => 0, 'stok' => 0.0, 'min_stok' => 900.0, 'status' => 'Aktif'],
            ['kode' => 'T57055 BZ030-00-28', 'nama' => 'T090', 'tipe' => 'FP', 'uom' => 'PCS', 'qty_case' => 0, 'stok' => 0.0, 'min_stok' => 450.0, 'status' => 'Aktif'],
            ['kode' => 'T57055 BZ040-00-28', 'nama' => 'T091', 'tipe' => 'FP', 'uom' => 'PCS', 'qty_case' => 0, 'stok' => 0.0, 'min_stok' => 120.0, 'status' => 'Aktif'],
            ['kode' => 'T58114 BZ070-00-28', 'nama' => 'T154', 'tipe' => 'FP', 'uom' => 'PCS', 'qty_case' => 0, 'stok' => 0.0, 'min_stok' => 300.0, 'status' => 'Aktif'],
            ['kode' => 'T58115 BZ100-00-28', 'nama' => 'T155', 'tipe' => 'FP', 'uom' => 'PCS', 'qty_case' => 0, 'stok' => 0.0, 'min_stok' => 300.0, 'status' => 'Aktif'],
            ['kode' => 'T61031 BZ240-00-26', 'nama' => 'T182', 'tipe' => 'FP', 'uom' => 'PCS', 'qty_case' => 0, 'stok' => 0.0, 'min_stok' => 1110.0, 'status' => 'Aktif'],
            ['kode' => 'T61031 BZ260-00-26', 'nama' => 'T183', 'tipe' => 'FP', 'uom' => 'PCS', 'qty_case' => 0, 'stok' => 0.0, 'min_stok' => 570.0, 'status' => 'Aktif'],
            ['kode' => 'T61032 BZ260-00-26', 'nama' => 'T184', 'tipe' => 'FP', 'uom' => 'PCS', 'qty_case' => 0, 'stok' => 0.0, 'min_stok' => 840.0, 'status' => 'Aktif'],
            ['kode' => 'T61032 BZ280-00-26', 'nama' => 'T185', 'tipe' => 'FP', 'uom' => 'PCS', 'qty_case' => 0, 'stok' => 0.0, 'min_stok' => 570.0, 'status' => 'Aktif'],
            ['kode' => 'T61607 BZ160-00-28', 'nama' => 'T207', 'tipe' => 'FP', 'uom' => 'PCS', 'qty_case' => 0, 'stok' => 0.0, 'min_stok' => 840.0, 'status' => 'Aktif'],
            ['kode' => 'T61608 BZ160-00-28', 'nama' => 'T208', 'tipe' => 'FP', 'uom' => 'PCS', 'qty_case' => 0, 'stok' => 0.0, 'min_stok' => 840.0, 'status' => 'Aktif'],
            ['kode' => 'T61621 BZ110-00-28', 'nama' => 'T213', 'tipe' => 'FP', 'uom' => 'PCS', 'qty_case' => 0, 'stok' => 0.0, 'min_stok' => 400.0, 'status' => 'Aktif'],
            ['kode' => 'T61622 BZ140-00-28', 'nama' => 'T214', 'tipe' => 'FP', 'uom' => 'PCS', 'qty_case' => 0, 'stok' => 0.0, 'min_stok' => 300.0, 'status' => 'Aktif'],
            ['kode' => 'T61705 BZ130-00-28', 'nama' => 'T227', 'tipe' => 'FP', 'uom' => 'PCS', 'qty_case' => 0, 'stok' => 0.0, 'min_stok' => 720.0, 'status' => 'Aktif'],
            ['kode' => 'T61706 BZ080-00-28', 'nama' => 'T228', 'tipe' => 'FP', 'uom' => 'PCS', 'qty_case' => 0, 'stok' => 0.0, 'min_stok' => 360.0, 'status' => 'Aktif'],
            ['kode' => 'VV-D57051-BZ340', 'nama' => 'NA-112', 'tipe' => 'FP', 'uom' => 'PCS', 'qty_case' => 0, 'stok' => 0.0, 'min_stok' => 570.0, 'status' => 'Aktif'],
            ['kode' => 'VV-D57052-BZ230', 'nama' => 'NA-107', 'tipe' => 'FP', 'uom' => 'PCS', 'qty_case' => 0, 'stok' => 0.0, 'min_stok' => 570.0, 'status' => 'Aktif'],
            ['kode' => 'VV-D57055-BZ040', 'nama' => 'NA-048', 'tipe' => 'FP', 'uom' => 'PCS', 'qty_case' => 0, 'stok' => 0.0, 'min_stok' => 210.0, 'status' => 'Aktif'],
            ['kode' => 'VV-D58114-BZ070', 'nama' => 'NA-051', 'tipe' => 'FP', 'uom' => 'PCS', 'qty_case' => 0, 'stok' => 0.0, 'min_stok' => 200.0, 'status' => 'Aktif'],
            ['kode' => 'VV-D58115-BZ100', 'nama' => 'NA-052', 'tipe' => 'FP', 'uom' => 'PCS', 'qty_case' => 0, 'stok' => 0.0, 'min_stok' => 200.0, 'status' => 'Aktif'],
            ['kode' => 'VV-D61705-BZ080', 'nama' => 'NA-058', 'tipe' => 'FP', 'uom' => 'PCS', 'qty_case' => 0, 'stok' => 0.0, 'min_stok' => 40.0, 'status' => 'Aktif'],
        ];

        foreach ($initialMaterials as $m) {
            $m['created_at'] = now();
            $m['updated_at'] = now();
            DB::table('materials')->insert($m);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};
