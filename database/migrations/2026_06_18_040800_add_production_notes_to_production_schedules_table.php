<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_schedules', function (Blueprint $table) {
            // Kolom OK dari produksi (untuk komparasi dengan QA)
            $table->integer('ok_qty')->default(0)->after('repair_qty')->comment('Total barang OK dari produksi');
            // Mesin/Press yang dipakai
            $table->string('press_name', 50)->nullable()->after('ok_qty')->comment('Nama mesin/press (PRESS A, B, C, D)');
            // Shift produksi
            $table->string('shift_name', 50)->nullable()->after('press_name')->comment('Nama shift (Shift Pagi / Shift Malam)');
            // ID di PPC untuk referensi silang
            $table->unsignedBigInteger('ppc_plan_id')->nullable()->after('shift_name')->comment('ID production_plan di db_integration');
            // Keterangan repair dari produksi (JSON)
            $table->json('production_repair_notes')->nullable()->after('ppc_plan_id')->comment('Detail keterangan repair dari produksi');
            // Keterangan reject dari produksi (JSON)
            $table->json('production_reject_notes')->nullable()->after('production_repair_notes')->comment('Detail keterangan reject dari produksi');
        });
    }

    public function down(): void
    {
        Schema::table('production_schedules', function (Blueprint $table) {
            $table->dropColumn([
                'ok_qty', 'press_name', 'shift_name', 'ppc_plan_id',
                'production_repair_notes', 'production_reject_notes'
            ]);
        });
    }
};
