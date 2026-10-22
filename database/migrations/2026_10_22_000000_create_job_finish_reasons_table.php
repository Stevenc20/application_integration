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
        Schema::create('job_finish_reasons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_master_id')->index();
            $table->unsignedBigInteger('production_plan_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('line_id')->nullable()->index();
            $table->string('line_name', 50)->nullable();
            $table->date('work_date')->nullable()->index();
            $table->string('shift', 20)->nullable()->index();
            $table->integer('target_qty')->default(0);
            $table->integer('actual_ok')->default(0);
            $table->integer('actual_repair')->default(0);
            $table->integer('actual_reject')->default(0);
            $table->integer('actual_total')->default(0);
            $table->text('reason');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_finish_reasons');
    }
};
