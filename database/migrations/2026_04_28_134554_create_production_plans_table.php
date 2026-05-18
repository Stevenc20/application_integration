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
        Schema::create('production_plans', function (Blueprint $table) {
        $table->id();
        $table->foreignId('line_master_id')->constrained('line_masters');
        $table->integer('target_qty')->default(0);
        $table->date('plan_date');
        $table->enum('status',['pending','approved','completed'])->default('pending');
        $table->text('notes')->nullable();
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_plans');
    }
};
