<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {


        // Daftar jenis defect per part (standar inspeksi)
        Schema::create('defect_masters', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();               // Cth: DEF-001
            $table->string('name');                         // Cth: Scratch
            $table->string('category')->nullable();         // Visual / Dimensi / Fungsi
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Relasi many-to-many: part bisa punya banyak standar defect
        Schema::create('part_defect_standards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('part_id')->constrained('parts')->cascadeOnDelete();
            $table->foreignId('defect_master_id')->constrained('defect_masters')->cascadeOnDelete();
            $table->string('severity')->default('major');   // critical / major / minor
            $table->timestamps();

            $table->unique(['part_id', 'defect_master_id']);
        });


    }

    public function down(): void
    {
        Schema::dropIfExists('part_defect_standards');
        Schema::dropIfExists('defect_masters');
    }
};