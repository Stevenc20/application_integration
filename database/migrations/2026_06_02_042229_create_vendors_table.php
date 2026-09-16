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
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->string('nama');
            $table->string('tipe');
            $table->string('kontak')->nullable();
            $table->string('email')->nullable();
            $table->string('telepon')->nullable();
            $table->string('status')->default('Aktif');
            $table->timestamps();
        });

        // Insert initial data from PDF
        $initialVendors = [
            ['kode' => '1000060', 'nama' => 'PT. FUJI TECHNICA INDONESIA', 'tipe' => 'Process / Makloon', 'kontak' => null, 'email' => null, 'telepon' => null, 'status' => 'Aktif'],
            ['kode' => '1200249', 'nama' => 'PT. GEMALA KEMPA DAYA', 'tipe' => 'Process / Makloon', 'kontak' => null, 'email' => null, 'telepon' => null, 'status' => 'Aktif'],
            ['kode' => '1200290', 'nama' => 'PT. STEEL CENTER INDONESIA', 'tipe' => 'Coil Center (Supplier Bahan Baku)', 'kontak' => null, 'email' => null, 'telepon' => null, 'status' => 'Aktif'],
            ['kode' => '1200305', 'nama' => 'PT. UNITED STEEL CENTER INDONESIA', 'tipe' => 'Coil Center (Supplier Bahan Baku)', 'kontak' => null, 'email' => null, 'telepon' => null, 'status' => 'Aktif'],
            ['kode' => '1200306', 'nama' => 'PT. WAJAKAMAJAYA SENTOSA', 'tipe' => 'Process / Makloon', 'kontak' => null, 'email' => null, 'telepon' => null, 'status' => 'Aktif'],
            ['kode' => '1200308', 'nama' => 'PT. YOSKA PRIMA INTI', 'tipe' => 'Process / Makloon', 'kontak' => null, 'email' => null, 'telepon' => null, 'status' => 'Aktif'],
            ['kode' => '1201330', 'nama' => 'PT. POSCO IJPC', 'tipe' => 'Coil Center (Supplier Bahan Baku)', 'kontak' => null, 'email' => null, 'telepon' => null, 'status' => 'Aktif'],
            ['kode' => '1201379', 'nama' => 'PT. TT METAL INDONESIA', 'tipe' => 'Coil Center (Supplier Bahan Baku)', 'kontak' => null, 'email' => null, 'telepon' => null, 'status' => 'Aktif'],
            ['kode' => '1201945', 'nama' => 'PT. GEMALA SARANA UPAYA', 'tipe' => 'Coil Center (Supplier Bahan Baku)', 'kontak' => null, 'email' => null, 'telepon' => null, 'status' => 'Aktif'],
            ['kode' => '1202016', 'nama' => 'PT. OHKUMA INDUSTRIES', 'tipe' => 'Process / Makloon', 'kontak' => null, 'email' => null, 'telepon' => null, 'status' => 'Aktif'],
            ['kode' => '1202035', 'nama' => 'PT. SUPER STEEL KARAWANG', 'tipe' => 'Coil Center (Supplier Bahan Baku)', 'kontak' => null, 'email' => null, 'telepon' => null, 'status' => 'Aktif'],
            ['kode' => '1202130', 'nama' => 'PT. ADIPERKASA ANUGRAH PRATAMA', 'tipe' => 'Process / Makloon', 'kontak' => null, 'email' => null, 'telepon' => null, 'status' => 'Aktif'],
            ['kode' => '1202208', 'nama' => 'PT. TRI CENTRUM FORTUNA', 'tipe' => 'Process / Makloon', 'kontak' => null, 'email' => null, 'telepon' => null, 'status' => 'Aktif'],
            ['kode' => '1202261', 'nama' => 'PT. ISRA PRESISI INDONESIA', 'tipe' => 'Process / Makloon', 'kontak' => null, 'email' => null, 'telepon' => null, 'status' => 'Aktif'],
            ['kode' => '1202264', 'nama' => 'PT. CGS INDONESIA', 'tipe' => 'Process / Makloon', 'kontak' => null, 'email' => null, 'telepon' => null, 'status' => 'Aktif'],
            ['kode' => '1202323', 'nama' => 'PT. MENARA PRESS INDONESIA', 'tipe' => 'Process / Makloon', 'kontak' => null, 'email' => null, 'telepon' => null, 'status' => 'Aktif'],
            ['kode' => '1202325', 'nama' => 'PT. CIPTA METAL MANDIRI', 'tipe' => 'Process / Makloon', 'kontak' => null, 'email' => null, 'telepon' => null, 'status' => 'Aktif'],
            ['kode' => 'VND-001', 'nama' => 'PT. IPPI KARAWANG', 'tipe' => 'Process / Makloon', 'kontak' => null, 'email' => null, 'telepon' => null, 'status' => 'Aktif'],
            ['kode' => 'VND-002', 'nama' => 'PT. GARUDA METAL UTAMA', 'tipe' => 'Coil Center (Supplier Bahan Baku)', 'kontak' => null, 'email' => null, 'telepon' => null, 'status' => 'Aktif'],
        ];

        foreach ($initialVendors as $vendor) {
            $vendor['created_at'] = now();
            $vendor['updated_at'] = now();
            DB::table('vendors')->insert($vendor);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
