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
        Schema::create('material_stocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('material_id');
            $table->unsignedBigInteger('storage_location_id');
            $table->double('qty')->default(0.0);
            $table->double('qty_vendor')->default(0.0);
            $table->timestamps();

            $table->foreign('material_id')->references('id')->on('materials')->onDelete('cascade');
            $table->foreign('storage_location_id')->references('id')->on('storage_locations')->onDelete('cascade');
            $table->unique(['material_id', 'storage_location_id']);
        });

        // Materials to insert/update based on the screenshot
        $targetMaterials = [
            ['kode' => 'VRM-940149', 'nama' => 'AA-3426', 'tipe' => 'RM', 'uom' => 'SHEET', 'qty_case' => 0, 'stok' => 1341.0, 'min_stok' => 800.0, 'status' => 'Aktif'],
            ['kode' => 'VRM-940273', 'nama' => 'AA-3446', 'tipe' => 'RM', 'uom' => 'SHEET', 'qty_case' => 0, 'stok' => 750.0, 'min_stok' => 600.0, 'status' => 'Aktif'],
            ['kode' => 'VRM-940113', 'nama' => 'AAS-006', 'tipe' => 'RM', 'uom' => 'SHEET', 'qty_case' => 0, 'stok' => 600.0, 'min_stok' => 400.0, 'status' => 'Aktif'],
            ['kode' => 'VRM-948408', 'nama' => 'AAS-050', 'tipe' => 'RM', 'uom' => 'SHEET', 'qty_case' => 0, 'stok' => 500.0, 'min_stok' => 480.0, 'status' => 'Aktif'],
            ['kode' => 'VRM-898474', 'nama' => 'AAS-054', 'tipe' => 'RM', 'uom' => 'SHEET', 'qty_case' => 0, 'stok' => 800.0, 'min_stok' => 480.0, 'status' => 'Aktif'],
            ['kode' => 'VRM-914701', 'nama' => 'AAS-057', 'tipe' => 'RM', 'uom' => 'SHEET', 'qty_case' => 0, 'stok' => 750.0, 'min_stok' => 720.0, 'status' => 'Aktif'],
            ['kode' => 'VRM-940251', 'nama' => 'AAS-058', 'tipe' => 'RM', 'uom' => 'SHEET', 'qty_case' => 0, 'stok' => 720.0, 'min_stok' => 1440.0, 'status' => 'Aktif'],
            ['kode' => 'VRM-940073', 'nama' => 'AE-0090', 'tipe' => 'RM', 'uom' => 'SHEET', 'qty_case' => 0, 'stok' => 6250.0, 'min_stok' => 500.0, 'status' => 'Aktif'],
            ['kode' => 'DC590.90X0605X0305', 'nama' => 'AES-006', 'tipe' => 'RM', 'uom' => 'SHEET', 'qty_case' => 0, 'stok' => 0.0, 'min_stok' => 500.0, 'status' => 'Aktif'],
            ['kode' => 'DG5D1.20X1060X0215', 'nama' => 'AES-011', 'tipe' => 'RM', 'uom' => 'SHEET', 'qty_case' => 0, 'stok' => 0.0, 'min_stok' => 600.0, 'status' => 'Aktif'],
            ['kode' => 'VRM-910061', 'nama' => 'AES-011V', 'tipe' => 'RM', 'uom' => 'SHEET', 'qty_case' => 0, 'stok' => 240.0, 'min_stok' => 480.0, 'status' => 'Aktif'],
            ['kode' => 'DG272.00X0466X0504', 'nama' => 'AES-016', 'tipe' => 'RM', 'uom' => 'SHEET', 'qty_case' => 0, 'stok' => 1200.0, 'min_stok' => 600.0, 'status' => 'Aktif'],
            ['kode' => 'DG272.00X0466X0586', 'nama' => 'AES-017', 'tipe' => 'RM', 'uom' => 'SHEET', 'qty_case' => 0, 'stok' => 1500.0, 'min_stok' => 600.0, 'status' => 'Aktif'],
        ];

        foreach ($targetMaterials as $m) {
            // Check if material with code exists
            $existing = DB::table('materials')->where('kode', $m['kode'])->first();
            if ($existing) {
                // Update
                DB::table('materials')->where('id', $existing->id)->update([
                    'nama' => $m['nama'],
                    'tipe' => $m['tipe'],
                    'uom' => $m['uom'],
                    'stok' => $m['stok'],
                    'min_stok' => $m['min_stok'],
                ]);
                $matId = $existing->id;
            } else {
                // Insert
                $m['created_at'] = now();
                $m['updated_at'] = now();
                $matId = DB::table('materials')->insertGetId($m);
            }

            // Seed stock in Gudang IRM (storage_location_id = 3)
            DB::table('material_stocks')->insert([
                'material_id' => $matId,
                'storage_location_id' => 3,
                'qty' => $m['stok'],
                'qty_vendor' => 0.0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_stocks');
    }
};
