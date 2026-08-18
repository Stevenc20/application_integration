<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('item_checks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lembar_inspeksi_id')->comment('Referensi ke Master Template');
            $table->unsignedBigInteger('production_schedule_id')->nullable()->comment('Relasi ke Jadwal Produksi SAP');
            $table->unsignedBigInteger('operator_id')->comment('Operator yang mengerjakan');
            $table->date('tanggal')->nullable();
            $table->string('shift')->nullable();
            $table->timestamp('waktu_mulai')->nullable();
            $table->timestamp('waktu_selesai')->nullable();
            $table->string('status')->default('in_progress'); 
            $table->json('hasil_dimensi')->nullable()->comment('Penyimpanan array data pengukuran');
            $table->json('hasil_visual')->nullable()->comment('Penyimpanan hasil visual appearance dll');
            $table->string('judgement')->nullable()->comment('OK/NG/REPAIR');
            $table->text('catatan')->nullable();
            
            $table->longText('paraf_operator')->nullable();
            $table->longText('paraf_foreman')->nullable();
            $table->longText('paraf_leader')->nullable();

            $table->timestamps();

            // Setup foreign keys
            $table->foreign('lembar_inspeksi_id')->references('id')->on('lembar_inspeksi')->onDelete('cascade');
            $table->foreign('operator_id')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('production_schedule_id')->references('id')->on('production_schedules')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_checks');
    }
};
