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
            // Drop old duplicates if they exist, keeping the newest one
            \Illuminate\Support\Facades\DB::statement("
                DELETE t1 FROM recovery_items t1
                INNER JOIN recovery_items t2 
                WHERE t1.id < t2.id AND t1.production_plan_id = t2.production_plan_id AND t1.production_plan_id IS NOT NULL;
            ");
            
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
