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
        Schema::table('item_checks', function (Blueprint $table) {
            $table->dropForeign(['production_plan_id']);
            $table->renameColumn('production_plan_id', 'production_schedule_id');
            $table->foreign('production_schedule_id')
                  ->references('id')
                  ->on('production_schedules')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('item_checks', function (Blueprint $table) {
            $table->dropForeign(['production_schedule_id']);
            $table->renameColumn('production_schedule_id', 'production_plan_id');
            $table->foreign('production_plan_id')
                  ->references('id')
                  ->on('production_plans')
                  ->onDelete('cascade');
        });
    }
};
