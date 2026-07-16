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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->string('nama');
            $table->string('kontak')->nullable();
            $table->string('email')->nullable();
            $table->string('telepon')->nullable();
            $table->string('status')->default('Aktif');
            $table->timestamps();
        });

        $initialCustomers = [
            ['kode' => 'C-ADM-KAP', 'nama' => 'Astra Daihatsu Motor (KAP)', 'kontak' => 'Budi Santoso', 'email' => 'budi@daihatsu.co.id', 'telepon' => '021-6510300', 'status' => 'Aktif'],
            ['kode' => 'C-ADM-SAP', 'nama' => 'Astra Daihatsu Motor (SAP)', 'kontak' => 'Ahmad Syarif', 'email' => 'ahmad@daihatsu.co.id', 'telepon' => '021-6510300', 'status' => 'Aktif'],
            ['kode' => 'C-TMMIN', 'nama' => 'Toyota Motor Manufacturing Indonesia', 'kontak' => 'Dedi Kurniawan', 'email' => 'dedi@toyota.co.id', 'telepon' => '021-6515555', 'status' => 'Aktif'],
            ['kode' => 'C-FTI', 'nama' => 'Fuji Technica Indonesia', 'kontak' => 'Heri Cahyono', 'email' => 'heri@fuji.co.id', 'telepon' => '021-8980123', 'status' => 'Aktif'],
            ['kode' => 'C-GKD', 'nama' => 'Gema Kencana Dinamika', 'kontak' => 'Rian Hidayat', 'email' => 'rian@gkd.co.id', 'telepon' => '021-8981245', 'status' => 'Aktif'],
            ['kode' => 'C-IAMI', 'nama' => 'Isuzu Astra Motor Indonesia', 'kontak' => 'Wawan Setiawan', 'email' => 'wawan@isuzu.co.id', 'telepon' => '021-6501111', 'status' => 'Aktif'],
            ['kode' => 'C-HPM', 'nama' => 'Honda Prospect Motor', 'kontak' => 'Rudi Hermawan', 'email' => 'rudi@honda-indonesia.com', 'telepon' => '021-6517777', 'status' => 'Aktif'],
            ['kode' => 'C-GAYAMOTOR', 'nama' => 'Gaya Motor', 'kontak' => 'Dodi Wijaya', 'email' => 'dodi@gayamotor.co.id', 'telepon' => '021-6512345', 'status' => 'Aktif'],
            ['kode' => 'C-IKAR', 'nama' => 'Ikar', 'kontak' => 'Roni Siregar', 'email' => 'roni@ikar.co.id', 'telepon' => '021-8976543', 'status' => 'Aktif'],
            ['kode' => 'C-PAMA', 'nama' => 'Pama Persada', 'kontak' => 'Toni Suwandi', 'email' => 'toni@pamapersada.com', 'telepon' => '021-4602015', 'status' => 'Aktif'],
        ];

        foreach ($initialCustomers as $c) {
            $c['created_at'] = now();
            $c['updated_at'] = now();
            DB::table('customers')->insert($c);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
