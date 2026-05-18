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
        Schema::table('job_masters', function (Blueprint $table) {
            $table->timestamp('plan_start')->nullable()->after('finished_at');
            $table->timestamp('plan_end')->nullable()->after('plan_start');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_masters', function (Blueprint $table) {
            $table->dropColumn(['plan_start', 'plan_end']);
        });
    }
};
