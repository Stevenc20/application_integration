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
        Schema::table('rundown_presses', function (Blueprint $table) {
            $table->decimal('plan_day', 15, 2)->default(0)->after('spare_part');
            $table->decimal('plan_night', 15, 2)->default(0)->after('plan_day');
            $table->decimal('actual_prod', 15, 2)->default(0)->after('plan_night');
        });
    }

    public function down(): void
    {
        Schema::table('rundown_presses', function (Blueprint $table) {
            $table->dropColumn(['plan_day', 'plan_night', 'actual_prod']);
        });
    }
};
