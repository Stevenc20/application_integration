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
        Schema::create('qprs', function (Blueprint $table) {
        $table->id();

        $table->string('no_job')->nullable();
        $table->string('model')->nullable();
        $table->date('tanggal')->nullable();
        $table->string('nama_part')->nullable();
        $table->string('no_qpr')->nullable();
        $table->string('kontrol_part')->nullable();

        $table->integer('rework_qty')->default(0);
        $table->integer('reject_qty')->default(0);
        $table->integer('stock_ippi_qty')->default(0);

        $table->string('rencana_produksi')->nullable();
        $table->string('proses_repair')->nullable();

        $table->string('kategori_problem')->nullable();
        $table->string('defect')->nullable();
        $table->text('defect_keterangan')->nullable();

        $table->string('lokasi')->nullable();
        $table->string('shift')->nullable();
        $table->time('jam')->nullable();

        $table->boolean('analisa_man')->default(false);
        $table->boolean('analisa_method')->default(false);
        $table->boolean('analisa_machine')->default(false);
        $table->boolean('analisa_material')->default(false);
        $table->boolean('analisa_environment')->default(false);
        $table->text('analisa_keterangan')->nullable();

        $table->text('correction')->nullable();
        $table->date('target')->nullable();
        $table->string('pic')->nullable();
        $table->string('status')->default('OPEN');

        $table->text('pencegahan')->nullable();

        $table->string('sketch')->nullable();
        $table->foreignId('created_by')->nullable();

        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qprs');
    }
};
