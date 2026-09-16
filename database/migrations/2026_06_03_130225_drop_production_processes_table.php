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
        Schema::dropIfExists('production_processes');
    }

    public function down(): void
    {
        Schema::create('production_processes', function (Blueprint $table) {
            $table->id();
            $table->string('production_order_number');
            $table->foreignId('job_id')->nullable()->constrained('job_masters')->nullOnDelete();
            $table->string('process_type');
            $table->string('shift');
            $table->integer('qty_ok');
            $table->integer('qty_repair')->default(0);
            $table->integer('qty_reject')->default(0);
            $table->string('status')->default('pending');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('line')->nullable();
            $table->timestamps();
        });
    }
};
