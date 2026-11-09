<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_plans', function (Blueprint $table) {
            if (! Schema::hasIndex('production_plans', 'idx_prod_plans_date_shift_press')) {
                $table->index(
                    ['plan_date', 'shift_name', 'press_name'],
                    'idx_prod_plans_date_shift_press'
                );
            }

            if (! Schema::hasIndex('production_plans', 'idx_prod_plans_parent_date')) {
                $table->index(
                    ['parent_job_id', 'plan_date'],
                    'idx_prod_plans_parent_date'
                );
            }
        });
    }

    public function down(): void
    {
        Schema::table('production_plans', function (Blueprint $table) {
            if (Schema::hasIndex('production_plans', 'idx_prod_plans_date_shift_press')) {
                $table->dropIndex('idx_prod_plans_date_shift_press');
            }

            if (Schema::hasIndex('production_plans', 'idx_prod_plans_parent_date')) {
                $table->dropIndex('idx_prod_plans_parent_date');
            }
        });
    }
};
