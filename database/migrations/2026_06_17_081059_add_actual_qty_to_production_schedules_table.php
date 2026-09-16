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
        Schema::table('production_schedules', function (Blueprint $table) {
            $table->integer('actual_qty')->default(0)->after('target_qty')->comment('Total aktual produksi');
            $table->integer('ng_qty')->default(0)->after('actual_qty')->comment('Total barang NG/Reject');
            $table->integer('repair_qty')->default(0)->after('ng_qty')->comment('Total barang Repair');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_schedules', function (Blueprint $table) {
            $table->dropColumn(['actual_qty', 'ng_qty', 'repair_qty']);
        });
    }
};
