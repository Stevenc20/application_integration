<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_masters', function (Blueprint $table) {
            $table->string('status')->default('pending')->after('capacity');
            $table->integer('sequence_no')->default(0)->after('status');
            $table->timestamp('started_at')->nullable()->after('sequence_no');
            $table->timestamp('finished_at')->nullable()->after('started_at');
        });
    }

    public function down(): void
    {
        Schema::table('job_masters', function (Blueprint $table) {
            $table->dropColumn([
                'status',
                'sequence_no',
                'started_at',
                'finished_at'
            ]);
        });
    }
};