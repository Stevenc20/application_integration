<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('network_containers', function (Blueprint $table) {
            $table->id();
            $table->string('container_name');
            $table->string('image')->nullable();
            $table->string('status')->default('unknown');
            $table->string('ports')->nullable();
            $table->float('cpu_percent')->nullable();
            $table->float('memory_mb')->nullable();
            $table->integer('uptime_seconds')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();

            $table->unique('container_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('network_containers');
    }
};
