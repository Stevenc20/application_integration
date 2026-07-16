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
        Schema::table('production_plans', function (Blueprint $table) {
            if (!Schema::hasColumn('production_plans', 'parent_job_id')) {
                $table->bigInteger('parent_job_id')->unsigned()->nullable()->after('row_type');
            }
            if (!Schema::hasColumn('production_plans', 'split_group')) {
                $table->string('split_group')->nullable()->after('parent_job_id');
            }
            if (!Schema::hasColumn('production_plans', 'session_no')) {
                $table->integer('session_no')->nullable()->after('split_group');
            }
            if (!Schema::hasColumn('production_plans', 'original_plan')) {
                $table->float('original_plan')->nullable()->after('session_no');
            }
            if (!Schema::hasColumn('production_plans', 'remaining_plan')) {
                $table->float('remaining_plan')->nullable()->after('original_plan');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_plans', function (Blueprint $table) {
            $table->dropColumn(['parent_job_id', 'split_group', 'session_no', 'original_plan', 'remaining_plan']);
        });
    }
};
