<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('line_masters')) {
            Schema::create('line_masters', function (Blueprint $table) {
                $table->id();
                $table->string('line_code')->unique()->comment('Kode unik line, misal: L-01');
                $table->string('line_name');
                $table->integer('capacity')->default(0)->comment('Kapasitas unit/hari');
                $table->integer('machine_count')->default(0)->comment('Jumlah mesin di line ini');
                $table->string('shift')->default('Pagi')->comment('Shift operasional: Pagi / Siang / Malam / Semua');
                $table->text('description')->nullable();
                $table->enum('status', ['active', 'inactive', 'maintenance'])->default('active');
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('line_masters');
    }
};
