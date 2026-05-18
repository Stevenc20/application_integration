<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_productions', function (Blueprint $table) {
            if (!Schema::hasColumn('daily_productions', 'actual_ok')) {
                $table->integer('actual_ok')->default(0)->after('actual_qty');
            }
            if (!Schema::hasColumn('daily_productions', 'actual_repair')) {
                $table->integer('actual_repair')->default(0)->after('actual_ok');
            }
            if (!Schema::hasColumn('daily_productions', 'actual_reject')) {
                $table->integer('actual_reject')->default(0)->after('actual_repair');
            }
        });
    }

    public function down(): void
    {
        Schema::table('daily_productions', function (Blueprint $table) {
            $table->dropColumn(['actual_ok', 'actual_repair', 'actual_reject']);
        });
    }
};
