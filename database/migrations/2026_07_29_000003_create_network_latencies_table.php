<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('network_latencies', function (Blueprint $table) {
            $table->id();
            $table->string('source');
            $table->string('target');
            $table->string('target_type')->default('service');
            $table->float('latency_ms')->nullable();
            $table->string('status')->default('unknown');
            $table->timestamp('measured_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('network_latencies');
    }
};
