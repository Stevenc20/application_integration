<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_stampings', function (Blueprint $table) {
            $table->id();

            // Meta per file upload
            $table->string('upload_date');       // e.g. "05 MEI 2026"
            $table->string('press_name');        // e.g. "PRESS A"
            $table->string('shift_name');        // e.g. "Shift Pagi" / "Shift Malam"
            $table->string('hari')->nullable();  // e.g. "Selasa Pagi"
            $table->string('tgl')->nullable();   // e.g. "05-Mei-2026"
            $table->string('jam')->nullable();   // e.g. "07:30 - 16:15 WIB"
            $table->string('revisi')->nullable(); // e.g. "Revisi-01"

            // Row data
            $table->integer('row_no')->nullable();
            $table->string('row_type')->default('job'); // 'job' or 'break'
            $table->string('job_master')->nullable();
            $table->string('type_plt')->nullable();
            $table->decimal('qty_plt', 10, 2)->nullable();
            $table->decimal('keb_mtl', 10, 2)->nullable();
            $table->decimal('total_plt', 10, 2)->nullable();
            $table->string('job_no')->nullable();
            $table->string('each_part')->nullable();
            $table->decimal('plan', 10, 2)->nullable();
            $table->decimal('ok', 10, 2)->nullable();
            $table->decimal('repair', 10, 2)->nullable();
            $table->decimal('reject', 10, 2)->nullable();
            $table->integer('total_mesin')->nullable();
            $table->decimal('ct_detik', 10, 3)->nullable();   // CT (")
            $table->decimal('process_time', 10, 3)->nullable();
            $table->decimal('reg_active', 10, 3)->nullable();
            $table->decimal('dct', 10, 3)->nullable();
            $table->decimal('mct', 10, 3)->nullable();
            $table->decimal('plan_dct', 10, 3)->nullable();
            $table->decimal('tpt', 10, 2)->nullable();
            $table->decimal('gsph_item', 10, 4)->nullable();
            $table->string('start_time')->nullable();   // e.g. "07:40"
            $table->string('finish_time')->nullable();  // e.g. "08:15"
            $table->string('act_start')->nullable();
            $table->string('act_finish')->nullable();
            $table->string('keterangan')->nullable();
            $table->integer('a1')->nullable();
            $table->integer('a2')->nullable();
            $table->integer('a3')->nullable();
            $table->integer('a4')->nullable();
            $table->integer('dt_menit')->nullable();
            $table->integer('total_pcs')->nullable();  // col 37 / IN
            $table->decimal('tpt_total', 10, 2)->nullable(); // col 40

            $table->timestamps();

            $table->index(['upload_date', 'press_name', 'shift_name']);
            $table->index('job_no');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_stampings');
    }
};
