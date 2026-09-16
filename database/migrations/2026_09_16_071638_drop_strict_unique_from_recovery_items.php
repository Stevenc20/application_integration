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
            // Drop the overly strict unique constraint
            $table->dropUnique('recovery_items_plan_id_unique');
            
            // Add a virtual generated column for partial uniqueness (MySQL 5.7+)
            // Only active statuses will have a non-null value, which is then enforced by UNIQUE
            $table->unsignedBigInteger('active_production_plan_id')
                  ->virtualAs("CASE WHEN status IN ('waiting_approval', 'continue', 'approved', 'scheduled', 'in_production') THEN production_plan_id ELSE NULL END")
                  ->nullable();
                  
            $table->unique('active_production_plan_id', 'recovery_items_active_plan_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recovery_items', function (Blueprint $table) {
            $table->dropUnique('recovery_items_active_plan_unique');
            $table->dropColumn('active_production_plan_id');
            $table->unique('production_plan_id', 'recovery_items_plan_id_unique');
        });
    }
};
