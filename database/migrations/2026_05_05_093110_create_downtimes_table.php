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
        Schema::create('downtimes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_master_id')->constrained('job_masters')->cascadeOnDelete();
            $table->string('jenis_downtime'); // Produksi, Mesin, Dies, Logistic, Material, Try out
            $table->text('problem')->nullable();
            $table->text('penyebab')->nullable();
            $table->text('action')->nullable();
            $table->string('pic')->nullable();
            $table->dateTime('start_time')->nullable();
            $table->dateTime('finish_time')->nullable();
            $table->integer('duration_seconds')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('downtimes');
    }
};
