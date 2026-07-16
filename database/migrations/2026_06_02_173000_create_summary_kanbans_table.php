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
        Schema::create('summary_kanbans', function (Blueprint $table) {
            $table->id();
            $table->string('no_skm')->unique();
            $table->date('tanggal');
            $table->enum('status', ['Draft', 'Dikirim', 'Sebagian', 'Selesai'])->default('Draft');
            $table->string('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('summary_kanban_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('summary_kanban_id');
            $table->unsignedBigInteger('material_id');
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->double('stok_saat_ini')->default(0);
            $table->integer('total_kanban')->default(0);
            $table->integer('stok_kanban')->default(0);
            $table->integer('outstanding')->default(0);
            $table->double('qty_kartu')->default(0);
            $table->integer('saran_kartu')->default(0);
            $table->double('total_order')->default(0);
            $table->timestamps();

            $table->foreign('summary_kanban_id')->references('id')->on('summary_kanbans')->onDelete('cascade');
            $table->foreign('material_id')->references('id')->on('materials')->onDelete('cascade');
            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('summary_kanban_items');
        Schema::dropIfExists('summary_kanbans');
    }
};
