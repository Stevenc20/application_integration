<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('production_plans', 'ppc_revision_id')) {
            Schema::table('production_plans', function (Blueprint $table) {
                $table->unsignedBigInteger('ppc_revision_id')->nullable()->after('source_type');
                $table->boolean('ppc_revision_active')->nullable()->after('ppc_revision_id');
            });
        }

        if (! Schema::hasColumn('production_plans', 'ppc_revision_active')) {
            Schema::table('production_plans', function (Blueprint $table) {
                $table->boolean('ppc_revision_active')->nullable()->after('ppc_revision_id');
            });
        }

        Schema::table('production_plans', function (Blueprint $table) {
            if (! Schema::hasIndex('production_plans', 'production_plans_ppc_revision_id_index')) {
                $table->index('ppc_revision_id', 'production_plans_ppc_revision_id_index');
            }
            if (! Schema::hasIndex('production_plans', 'production_plans_ppc_revision_active_index')) {
                $table->index('ppc_revision_active', 'production_plans_ppc_revision_active_index');
            }
            if (! Schema::hasIndex('production_plans', 'production_plans_revision_queue_index')) {
                $table->index(
                    ['plan_date', 'shift_name', 'press_name', 'ppc_revision_active'],
                    'production_plans_revision_queue_index'
                );
            }
        });

        if (! Schema::hasColumn('job_masters', 'ppc_revision_id')) {
            Schema::table('job_masters', function (Blueprint $table) {
                $table->unsignedBigInteger('ppc_revision_id')->nullable()->after('target_qty');
                $table->boolean('ppc_revision_active')->nullable()->after('ppc_revision_id');
            });
        }

        if (! Schema::hasColumn('job_masters', 'ppc_revision_active')) {
            Schema::table('job_masters', function (Blueprint $table) {
                $table->boolean('ppc_revision_active')->nullable()->after('ppc_revision_id');
            });
        }

        Schema::table('job_masters', function (Blueprint $table) {
            if (! Schema::hasIndex('job_masters', 'job_masters_ppc_revision_id_index')) {
                $table->index('ppc_revision_id', 'job_masters_ppc_revision_id_index');
            }
            if (! Schema::hasIndex('job_masters', 'job_masters_ppc_revision_active_index')) {
                $table->index('ppc_revision_active', 'job_masters_ppc_revision_active_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('production_plans', function (Blueprint $table) {
            if (Schema::hasIndex('production_plans', 'production_plans_ppc_revision_id_index')) {
                $table->dropIndex('production_plans_ppc_revision_id_index');
            }
            if (Schema::hasIndex('production_plans', 'production_plans_ppc_revision_active_index')) {
                $table->dropIndex('production_plans_ppc_revision_active_index');
            }
            if (Schema::hasIndex('production_plans', 'production_plans_revision_queue_index')) {
                $table->dropIndex('production_plans_revision_queue_index');
            }
        });

        Schema::table('production_plans', function (Blueprint $table) {
            $cols = array_values(array_filter(
                ['ppc_revision_id', 'ppc_revision_active'],
                fn (string $col) => Schema::hasColumn('production_plans', $col)
            ));
            if (! empty($cols)) {
                $table->dropColumn($cols);
            }
        });

        Schema::table('job_masters', function (Blueprint $table) {
            if (Schema::hasIndex('job_masters', 'job_masters_ppc_revision_id_index')) {
                $table->dropIndex('job_masters_ppc_revision_id_index');
            }
            if (Schema::hasIndex('job_masters', 'job_masters_ppc_revision_active_index')) {
                $table->dropIndex('job_masters_ppc_revision_active_index');
            }
        });

        Schema::table('job_masters', function (Blueprint $table) {
            $cols = array_values(array_filter(
                ['ppc_revision_id', 'ppc_revision_active'],
                fn (string $col) => Schema::hasColumn('job_masters', $col)
            ));
            if (! empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
