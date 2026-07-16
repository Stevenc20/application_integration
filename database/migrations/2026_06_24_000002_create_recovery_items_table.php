<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recovery_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recovery_schedule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('production_plan_id')->constrained();
            $table->string('job_no');
            $table->string('job_master');
            $table->string('press_name');
            $table->float('plan_qty');
            $table->float('ok')->default(0);
            $table->float('repair')->default(0);
            $table->float('reject')->default(0);
            $table->float('ct_detik')->nullable();
            $table->float('dct')->nullable();
            $table->float('reg_active')->nullable();
            $table->integer('total_mesin')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recovery_items');
    }
};
