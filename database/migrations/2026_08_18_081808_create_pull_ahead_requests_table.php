<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pull_ahead_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('original_plan_id');
            $table->unsignedBigInteger('new_plan_id')->nullable();
            $table->unsignedBigInteger('requested_by');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->string('source_shift');
            $table->string('target_shift');
            $table->float('qty_requested');
            $table->float('qty_approved')->nullable();
            $table->string('proposed_sequence_after')->nullable();
            $table->string('final_sequence_after')->nullable();
            $table->enum('status', ['PENDING', 'REJECTED', 'APPROVED', 'APPLIED'])->default('PENDING');
            $table->text('remarks')->nullable();
            $table->boolean('is_read_by_leader')->default(false);
            $table->boolean('is_read_by_ppc')->default(false);
            $table->timestamps();

            $table->foreign('original_plan_id')->references('id')->on('production_plans')->onDelete('cascade');
            $table->foreign('new_plan_id')->references('id')->on('production_plans')->onDelete('set null');
            $table->foreign('requested_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pull_ahead_requests');
    }
};
