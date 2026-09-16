<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('network_port_scans', function (Blueprint $table) {
            $table->id();
            $table->string('target');
            $table->integer('port');
            $table->string('protocol', 10)->default('tcp');
            $table->string('service_name')->nullable();
            $table->string('status', 20)->default('closed');
            $table->float('response_time_ms')->nullable();
            $table->timestamp('scanned_at')->nullable();
            $table->timestamps();

            $table->index('target');
            $table->index('status');
            $table->index('scanned_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('network_port_scans');
    }
};
