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
        Schema::create('production_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('sap_order_no')->nullable()->unique()->comment('Nomor Order dari SAP');
            $table->string('job_no')->comment('Relasi logis ke Master Template QA');
            $table->string('part_no')->nullable();
            $table->string('part_name')->nullable();
            $table->date('tanggal_produksi');
            $table->string('line')->nullable();
            $table->integer('target_qty')->default(0)->comment('Target produksi harian dari PPC');
            $table->string('status')->default('scheduled')->comment('scheduled, running, completed');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_schedules');
    }
};
