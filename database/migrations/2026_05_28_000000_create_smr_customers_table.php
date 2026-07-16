<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('smr_customers', function (Blueprint $table) {
            $table->id();
            $table->integer('no')->default(0);
            $table->integer('year')->nullable();
            $table->date('date')->nullable()->index();
            $table->string('month')->nullable()->index();
            $table->string('quarterly')->nullable()->index();
            $table->string('no_smr')->nullable()->index();
            $table->string('job_no')->nullable()->index();
            $table->string('part_number')->nullable()->index();
            $table->string('part_name')->nullable()->index();
            $table->integer('qty_smr')->default(0);
            $table->integer('total_production')->default(0);
            $table->double('cost_rijection')->nullable();
            $table->double('rijection_rate')->nullable();
            $table->string('customer')->nullable()->index();
            $table->text('problem')->nullable();
            $table->text('countermeasures')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('smr_customers');
    }
};
