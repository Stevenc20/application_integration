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
        // The table already exists with only id+timestamps.
        // Add the missing columns via table modification.
        Schema::table('production_lines', function (Blueprint $table) {
            if (!Schema::hasColumn('production_lines', 'line_name')) {
                $table->string('line_name')->unique()->after('id');
            }
            if (!Schema::hasColumn('production_lines', 'capacity')) {
                $table->integer('capacity')->default(0)->after('line_name');
            }
            if (!Schema::hasColumn('production_lines', 'target_qty')) {
                $table->integer('target_qty')->nullable()->after('capacity');
            }
            if (!Schema::hasColumn('production_lines', 'plan_date')) {
                $table->date('plan_date')->nullable()->after('target_qty');
            }
            if (!Schema::hasColumn('production_lines', 'status')) {
                $table->enum('status', ['pending', 'approved', 'completed'])->default('pending')->after('plan_date');
            }
            if (!Schema::hasColumn('production_lines', 'notes')) {
                $table->text('notes')->nullable()->after('status');
            }
            if (!Schema::hasColumn('production_lines', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_lines', function (Blueprint $table) {
            $table->dropColumn(['line_name', 'capacity', 'target_qty', 'plan_date', 'status', 'notes', 'deleted_at']);
        });
    }
};
