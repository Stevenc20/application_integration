<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_plan_duplicate_audits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('production_plan_id')->index();
            $table->unsignedBigInteger('duplicate_of_id')->nullable()->index();
            $table->string('action', 32)->default('deleted');
            $table->string('job_no')->nullable();
            $table->string('job_master')->nullable();
            $table->date('plan_date')->nullable();
            $table->string('shift_name')->nullable();
            $table->string('press_name')->nullable();
            $table->string('start_time')->nullable();
            $table->string('finish_time')->nullable();
            $table->float('plan_qty')->nullable();
            $table->text('reason')->nullable();
            $table->json('snapshot')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable()->index();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_plan_duplicate_audits');
    }
};
