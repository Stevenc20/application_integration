<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repair_reject_logs', function (Blueprint $table) {
            $table->string('pcs_number', 255)->nullable()->after('qty_b');
        });
    }

    public function down(): void
    {
        Schema::table('repair_reject_logs', function (Blueprint $table) {
            $table->dropColumn('pcs_number');
        });
    }
};
