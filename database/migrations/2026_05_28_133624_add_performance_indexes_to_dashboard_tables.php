<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_masters', function (Blueprint $table) {
            $table->index('line', 'idx_job_masters_line');
        });

        Schema::table('daily_productions', function (Blueprint $table) {
            $table->index(['work_date', 'job_master_id'], 'idx_daily_prod_work_date_job');
        });

        Schema::table('downtimes', function (Blueprint $table) {
            $table->index('created_at', 'idx_downtimes_created_at');
        });
    }

    public function down(): void
    {
        Schema::table('job_masters', function (Blueprint $table) {
            $table->dropIndex('idx_job_masters_line');
        });

        Schema::table('daily_productions', function (Blueprint $table) {
            $table->dropIndex('idx_daily_prod_work_date_job');
        });

        Schema::table('downtimes', function (Blueprint $table) {
            $table->dropIndex('idx_downtimes_created_at');
        });
    }
};
