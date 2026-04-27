<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dandori_sessions', function (Blueprint $table) {

            $table->id();

            $table->unsignedBigInteger('job_master_id');
            $table->string('job_number');
            $table->string('job_name');

            $table->string('line')->nullable();
            $table->string('shift')->nullable();

            $table->string('status')->default('waiting');

            $table->timestamp('start_time')->nullable();
            $table->timestamp('finish_time')->nullable();

            $table->decimal('total_minutes',10,2)->default(0);

            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dandori_sessions');
    }
};