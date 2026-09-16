<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_productions', function (Blueprint $table) {
            $table->index(['job_master_id', 'work_date'], 'idx_daily_prod_job_date');
        });

        Schema::table('production_sessions', function (Blueprint $table) {
            $table->index(['job_master_id', 'work_date'], 'idx_sessions_job_date');
        });

        Schema::table('production_plans', function (Blueprint $table) {
            $table->index(['plan_date', 'shift_name', 'press_name'], 'idx_plans_date_shift_press');
        });

        Schema::table('production_logs', function (Blueprint $table) {
            $table->index(['job_master_id', 'created_at'], 'idx_prod_logs_job_date');
        });
    }

    public function down(): void
    {
        Schema::table('daily_productions', function (Blueprint $table) {
            $table->dropIndex('idx_daily_prod_job_date');
        });

        Schema::table('production_sessions', function (Blueprint $table) {
            $table->dropIndex('idx_sessions_job_date');
        });

        Schema::table('production_plans', function (Blueprint $table) {
            $table->dropIndex('idx_plans_date_shift_press');
        });

        Schema::table('production_logs', function (Blueprint $table) {
            $table->dropIndex('idx_prod_logs_job_date');
        });
    }
};
