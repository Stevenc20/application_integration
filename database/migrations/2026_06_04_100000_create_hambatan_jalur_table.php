<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hambatan_jalur', function (Blueprint $table) {
            $table->id();
            $table->foreignId('downtime_id')->constrained('downtimes')->cascadeOnDelete();
            $table->string('line_name')->nullable();
            $table->string('mesin')->nullable();
            $table->string('job_no')->nullable();
            $table->string('nama_part')->nullable();
            $table->string('jenis_hambatan', 20)->nullable();
            $table->string('sub_jenis', 20)->nullable();
            $table->text('problem')->nullable();
            $table->text('penyebab')->nullable();
            $table->text('penanggulangan')->nullable();
            $table->string('pic_hambatan')->nullable();
            $table->dateTime('waktu')->nullable();
            $table->string('status', 20)->default('open');
            $table->dateTime('signed_at')->nullable();
            $table->foreignId('signed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hambatan_jalur');
    }
};
