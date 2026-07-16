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
        Schema::create('storage_locations', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->string('nama');
            $table->string('deskripsi')->nullable();
            $table->string('tipe_material'); // RM, WIP, FP
            $table->boolean('is_scrap')->default(false);
            $table->timestamps();
        });

        $initialLocations = [
            ['kode' => '1101-S-ADM', 'nama' => 'SCRAP RM ADM', 'deskripsi' => 'GUDANG SCRAP RM ADM', 'tipe_material' => 'RM', 'is_scrap' => true],
            ['kode' => '1101-S', 'nama' => 'SCRAP RM IPPI', 'deskripsi' => 'GUDANG SCRAP RM IPPI', 'tipe_material' => 'RM', 'is_scrap' => true],
            ['kode' => '1101', 'nama' => 'Gudang IRM', 'deskripsi' => 'Penyimpanan material RM', 'tipe_material' => 'RM', 'is_scrap' => false],
            ['kode' => '1100', 'nama' => 'Gudang WIP', 'deskripsi' => 'Work-in-Process', 'tipe_material' => 'WIP', 'is_scrap' => false],
            ['kode' => '1107', 'nama' => 'Gudang Logistik', 'deskripsi' => 'Penyimpanan FP', 'tipe_material' => 'FP', 'is_scrap' => false],
        ];

        foreach ($initialLocations as $loc) {
            $loc['created_at'] = now();
            $loc['updated_at'] = now();
            DB::table('storage_locations')->insert($loc);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('storage_locations');
    }
};
