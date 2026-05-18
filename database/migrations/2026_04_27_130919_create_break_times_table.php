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
        if (!Schema::hasTable('break_times')) {
            Schema::create('break_times', function (Blueprint $table) {
                $table->id();
                $table->string('nama_istirahat');
                $table->string('shift')->nullable();
                $table->string('hari')->nullable();
                $table->time('waktu_mulai');
                $table->time('waktu_selesai');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('break_times');
    }
};
