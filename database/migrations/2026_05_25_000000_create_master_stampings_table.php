<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_stampings', function (Blueprint $table) {
            $table->id();
            $table->string('proses_line')->nullable();
            $table->string('mach')->nullable();
            $table->string('job_no')->nullable();
            $table->string('job_master')->nullable();
            $table->string('part_no')->nullable();
            $table->string('irm_number')->nullable();
            $table->string('part_name')->nullable();
            $table->decimal('qty_unit', 10, 2)->nullable();
            $table->decimal('total', 10, 2)->nullable();
            $table->string('type_pallet')->nullable();
            $table->decimal('qty_pallet', 10, 2)->nullable();
            $table->decimal('ct_detik', 10, 3)->nullable();
            $table->decimal('dct', 10, 3)->nullable();
            $table->decimal('reg_active', 10, 3)->nullable();
            $table->decimal('mct', 10, 3)->nullable();
            $table->decimal('tpt', 10, 2)->nullable();
            $table->string('customer')->nullable();
            $table->string('remarks')->nullable();
            $table->timestamps();

            $table->index('job_no');
            $table->index('job_master');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_stampings');
    }
};
