<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('master_break_times')) {
            return;
        }

        Schema::create('master_break_times', function (Blueprint $table) {
            $table->id();
            $table->string('hari', 20)->default('semua'); // senin..minggu | semua
            $table->time('waktu_mulai');
            $table->time('waktu_selesai');
            $table->string('type', 30)->default('istirahat'); // istirahat | cinkorak
            $table->string('label')->nullable();
            $table->string('shift')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['hari', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_break_times');
    }
};
