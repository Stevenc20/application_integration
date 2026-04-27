<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dandori_groups', function (Blueprint $table) {

            $table->id();

            $table->unsignedBigInteger('session_id');

            $table->string('group_name');

            $table->integer('sequence_no')->default(1);

            $table->string('status')->default('waiting');

            $table->timestamp('start_time')->nullable();
            $table->timestamp('finish_time')->nullable();

            $table->decimal('total_minutes',10,2)->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dandori_groups');
    }
};