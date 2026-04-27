<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_processes', function (Blueprint $table) {

            $table->foreignId('user_id')
                  ->nullable()
                  ->after('id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->string('line')
                  ->nullable()
                  ->after('process_type');

            $table->enum('machine_status', ['running', 'stop', 'setup'])
                  ->default('running')
                  ->after('line');

            $table->foreign('job_id')
                  ->references('id')
                  ->on('jobs')
                  ->nullOnDelete();

            $table->index('created_at');
            $table->index('process_type');
            $table->index('shift');
            $table->index('line');
        });
    }

    public function down(): void
    {
        Schema::table('production_processes', function (Blueprint $table) {

            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');

            $table->dropColumn('line');

            $table->dropColumn('machine_status');

            $table->dropForeign(['job_id']);

            $table->dropIndex(['created_at']);
            $table->dropIndex(['process_type']);
            $table->dropIndex(['shift']);
            $table->dropIndex(['line']);
        });
    }
};