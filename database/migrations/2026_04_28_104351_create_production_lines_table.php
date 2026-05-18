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
        if (!Schema::hasTable('production_lines')) {
            Schema::create('production_lines', function (Blueprint $table) {
                $table->id();
                $table->string('line_name')->unique();
                $table->integer('capacity');
                $table->integer('target_qty')->nullable();
                $table->date('plan_date')->nullable();
                $table->enum('status', ['pending', 'approved', 'completed'])->default('pending');
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_lines');
    }
};
