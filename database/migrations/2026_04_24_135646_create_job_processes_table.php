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
       Schema::create('job_processes', function (Blueprint $table) {
        $table->id();
        $table->foreignId('job_master_id')->constrained('job_masters')->onDelete('cascade');
        $table->integer('sequence_no')->default(1);
        $table->string('process_name');
        $table->integer('standard_minutes')->default(0);
        $table->string('status')->default('pending');
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_processes');
    }
};
