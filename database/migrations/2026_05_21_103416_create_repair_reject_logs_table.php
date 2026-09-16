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
        Schema::create('repair_reject_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_master_id')->constrained('job_masters')->onDelete('cascade');
            $table->enum('type', ['repair', 'reject']);
            $table->string('sketch_no')->nullable();
            $table->string('repair_category')->nullable();
            $table->string('defect_name');
            $table->decimal('qty_a', 10, 2)->default(0);
            $table->decimal('qty_b', 10, 2)->nullable();
            $table->string('area_problem')->nullable();
            $table->text('root_cause')->nullable();
            $table->text('countermeasure')->nullable();
            $table->unsignedBigInteger('created_by')->nullable(); // Can't easily use constrained('users') if it doesn't match type exactly, but we will
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repair_reject_logs');
    }
};
