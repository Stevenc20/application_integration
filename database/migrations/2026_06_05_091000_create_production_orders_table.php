<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_orders', function (Blueprint $table) {
            $table->id();
            $table->string('no_order')->unique();
            $table->string('material_kode');
            $table->string('material_nama');
            $table->integer('qty_plan');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status')->default('Released');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_orders');
    }
};
