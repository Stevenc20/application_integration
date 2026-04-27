<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dandori_details', function (Blueprint $table) {

            $table->id();

            $table->unsignedBigInteger('group_id');

            $table->string('activity_name');

            $table->integer('sequence_no')->default(1);

            $table->string('status')->default('waiting');

            $table->timestamp('start_time')->nullable();
            $table->timestamp('finish_time')->nullable();

            $table->decimal('duration_minutes',10,2)->default(0);

            $table->text('remarks')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dandori_details');
    }
};