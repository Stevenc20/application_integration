<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('q_checks', function (Blueprint $table) {
            if (!Schema::hasColumn('q_checks', 'job_master_id')) {
                $table->foreignId('job_master_id')->nullable()->constrained()->nullOnDelete()->after('id');
            }
            if (!Schema::hasColumn('q_checks', 'jenis_qcheck')) {
                $table->string('jenis_qcheck', 50)->nullable()->after('job_master_id');
            }
            if (!Schema::hasColumn('q_checks', 'hasil_qcheck')) {
                $table->string('hasil_qcheck', 100)->nullable()->after('jenis_qcheck');
            }
            if (!Schema::hasColumn('q_checks', 'keterangan')) {
                $table->text('keterangan')->nullable()->after('hasil_qcheck');
            }
            if (!Schema::hasColumn('q_checks', 'start_time')) {
                $table->dateTime('start_time')->nullable()->after('keterangan');
            }
            if (!Schema::hasColumn('q_checks', 'finish_time')) {
                $table->dateTime('finish_time')->nullable()->after('start_time');
            }
        });
    }

    public function down(): void
    {
        Schema::table('q_checks', function (Blueprint $table) {
            $table->dropForeign(['job_master_id']);
            $table->dropColumn([
                'job_master_id', 'jenis_qcheck', 'hasil_qcheck',
                'keterangan', 'start_time', 'finish_time'
            ]);
        });
    }
};
