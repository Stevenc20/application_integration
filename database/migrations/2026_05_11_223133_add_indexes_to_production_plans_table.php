<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_plans', function (Blueprint $table) {
            $table->index('plan_date');
            $table->index('shift_name');
            $table->index('press_name');
            $table->index('row_type');
        });
    }

    public function down(): void
    {
        Schema::table('production_plans', function (Blueprint $table) {
            $table->dropIndex(['plan_date']);
            $table->dropIndex(['shift_name']);
            $table->dropIndex(['press_name']);
            $table->dropIndex(['row_type']);
        });
    }
};
