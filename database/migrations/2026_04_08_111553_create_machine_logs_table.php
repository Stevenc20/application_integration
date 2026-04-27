<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   
    public function up(): void
    {
        Schema::create('machine_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['running','downtime','maintenance']);
            $table->timestamp('downtime_start')->nullable();
            $table->timestamp('downtime_end')->nullable();
            $table->timestamps();
        });
    }


};
