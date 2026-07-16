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
        Schema::create('line_assignments', function (Blueprint $table) {
            $table->id();
            $table->string('line_name');
            $table->string('shift_name');
            $table->unsignedBigInteger('leader_user_id')->nullable();
            $table->unsignedBigInteger('foreman_user_id')->nullable();
            $table->unsignedBigInteger('supervisor_user_id')->nullable();
            $table->timestamps();

            $table->foreign('leader_user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('foreman_user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('supervisor_user_id')->references('id')->on('users')->onDelete('set null');

            // Unique constraint to avoid duplicate assignment for same line and shift
            $table->unique(['line_name', 'shift_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('line_assignments');
    }
};
