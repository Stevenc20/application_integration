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
        Schema::table('production_processes', function (Blueprint $table) {
            // Drop the incorrect foreign key pointing to 'jobs' table
            // We need to know the exact constraint name. 
            // Based on previous error, it's 'production_processes_job_id_foreign'
            $table->dropForeign(['job_id']);
            
            // Re-add correctly pointing to 'job_masters'
            $table->foreign('job_id')
                  ->references('id')
                  ->on('job_masters')
                  ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_processes', function (Blueprint $table) {
            $table->dropForeign(['job_id']);
            $table->foreign('job_id')
                  ->references('id')
                  ->on('jobs')
                  ->nullOnDelete();
        });
    }
};
