<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boms', function (Blueprint $table) {
            $table->id();
            $table->string('no_bom')->unique();
            $table->string('material_kode');
            $table->string('material_nama');
            $table->decimal('qty_base', 10, 3)->default(1);
            $table->integer('jml_komponen')->default(1);
            $table->string('status')->default('Aktif');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boms');
    }
};
