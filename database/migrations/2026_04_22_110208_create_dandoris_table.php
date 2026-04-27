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
     Schema::create('dandoris', function (Blueprint $table) {
    $table->id();

    $table->unsignedBigInteger('previous_job_id')->nullable();
    $table->unsignedBigInteger('next_job_id')->nullable();

    $table->string('line')->nullable();
    $table->string('shift')->nullable();

    $table->string('activity')->default('Changeover');

    $table->timestamp('start_time')->nullable();
    $table->timestamp('finish_time')->nullable();

    $table->decimal('duration_minutes',10,2)->default(0);

    $table->date('work_date');

    $table->unsignedBigInteger('created_by')->nullable();

    $table->timestamps();
});
    }

};
