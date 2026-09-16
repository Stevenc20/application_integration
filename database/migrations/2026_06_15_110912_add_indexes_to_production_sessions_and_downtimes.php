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
        Schema::table('production_sessions', function (Blueprint $table) {
            $table->index(['job_master_id', 'status', 'finish_time'], 'idx_sessions_job_status_finish');
            $table->index('finish_time', 'idx_sessions_finish_time');
        });

        Schema::table('downtimes', function (Blueprint $table) {
            $table->index(['job_master_id', 'finish_time', 'jenis_downtime'], 'idx_downtimes_job_finish_jenis');
            $table->index('start_time', 'idx_downtimes_start_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_sessions', function (Blueprint $table) {
            $table->dropIndex('idx_sessions_job_status_finish');
            $table->dropIndex('idx_sessions_finish_time');
        });

        Schema::table('downtimes', function (Blueprint $table) {
            $table->dropIndex('idx_downtimes_job_finish_jenis');
            $table->dropIndex('idx_downtimes_start_time');
        });
    }
};
