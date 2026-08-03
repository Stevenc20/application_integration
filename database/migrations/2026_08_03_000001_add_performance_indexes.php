<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_plans', function (Blueprint $table) {
            $table->index(['plan_date', 'shift_name', 'press_name', 'row_type'], 'idx_plan_date_shift_press');
        });

        Schema::table('job_masters', function (Blueprint $table) {
            $table->index(['line', 'status'], 'idx_job_line_status');
        });

        Schema::table('production_sessions', function (Blueprint $table) {
            $table->index(['job_master_id', 'work_date', 'status'], 'idx_session_job_date_status');
        });

        Schema::table('daily_productions', function (Blueprint $table) {
            $table->index(['job_master_id', 'work_date'], 'idx_daily_job_date');
        });

        Schema::table('downtimes', function (Blueprint $table) {
            $table->index(['job_master_id', 'start_time'], 'idx_dt_job_start');
        });
    }

    public function down(): void
    {
        Schema::table('production_plans', function (Blueprint $table) {
            $table->dropIndex('idx_plan_date_shift_press');
        });

        Schema::table('job_masters', function (Blueprint $table) {
            $table->dropIndex('idx_job_line_status');
        });

        Schema::table('production_sessions', function (Blueprint $table) {
            $table->dropIndex('idx_session_job_date_status');
        });

        Schema::table('daily_productions', function (Blueprint $table) {
            $table->dropIndex('idx_daily_job_date');
        });

        Schema::table('downtimes', function (Blueprint $table) {
            $table->dropIndex('idx_dt_job_start');
        });
    }
};
