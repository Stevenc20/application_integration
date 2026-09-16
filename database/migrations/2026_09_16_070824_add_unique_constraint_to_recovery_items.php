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
        Schema::table('recovery_items', function (Blueprint $table) {
            // Drop old duplicates if they exist, keeping the newest one (id = max)
            $newestPerPlan = \Illuminate\Support\Facades\DB::table('recovery_items')
                ->select('production_plan_id', \Illuminate\Support\Facades\DB::raw('MAX(id) as keep_id'))
                ->whereNotNull('production_plan_id')
                ->groupBy('production_plan_id')
                ->havingRaw('COUNT(*) > 1')
                ->get();

            foreach ($newestPerPlan as $row) {
                \Illuminate\Support\Facades\DB::table('recovery_items')
                    ->where('production_plan_id', $row->production_plan_id)
                    ->where('id', '<', $row->keep_id)
                    ->delete();
            }

            $table->unique('production_plan_id', 'recovery_items_plan_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recovery_items', function (Blueprint $table) {
            $table->dropUnique('recovery_items_plan_id_unique');
        });
    }
};
