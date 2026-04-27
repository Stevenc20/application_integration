<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_targets', function (Blueprint $table) {

            $table->id();

            $table->date('target_date');

            $table->foreignId('job_id')
                ->nullable()
                ->constrained('jobs')
                ->cascadeOnDelete();

            $table->string('process_type');
            $table->string('shift');

            $table->integer('target_qty');

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_targets');
    }
};