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
        Schema::create('daily_productions', function (Blueprint $table) {
        $table->id();

        $table->foreignId('job_master_id')->constrained()->cascadeOnDelete();

        $table->date('work_date');

        $table->integer('actual_qty')->default(0);
        $table->integer('reject_qty')->default(0);
        $table->integer('repair_qty')->default(0);

        $table->text('remarks')->nullable();

        $table->timestamps();
     });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_production');
    }
};
