<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_scraps', function (Blueprint $table) {
            $table->id();
            $table->integer('no')->default(0);
            $table->integer('year')->nullable();
            $table->string('month')->nullable()->index();
            $table->string('ba_no')->nullable()->index();
            $table->string('job_no')->nullable()->index();
            $table->string('sourch_1')->nullable()->index();
            $table->string('part_number')->nullable()->index();
            $table->string('part_name')->nullable()->index();
            $table->string('sourch_2')->nullable()->index();
            $table->string('customer')->nullable()->index();
            $table->integer('qty')->default(0);
            $table->double('value')->default(0);
            $table->integer('total_production')->default(0);
            $table->double('reject_rate')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_scraps');
    }
};
