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
            $table->unsignedBigInteger('shift_master_id')->nullable()->after('shift_name');
        });
        
        Schema::table('shift_submissions', function (Blueprint $table) {
            $table->unsignedBigInteger('shift_master_id')->nullable()->after('shift');
        });
        
        // Backfill data
        \Illuminate\Support\Facades\DB::statement("
            UPDATE production_plans 
            SET shift_master_id = (SELECT id FROM master_shifts WHERE name LIKE '%Malam%' LIMIT 1)
            WHERE UPPER(shift_name) LIKE '%MALAM%'
        ");
        
        \Illuminate\Support\Facades\DB::statement("
            UPDATE production_plans 
            SET shift_master_id = (SELECT id FROM master_shifts WHERE name LIKE '%Pagi%' LIMIT 1)
            WHERE UPPER(shift_name) LIKE '%PAGI%' OR UPPER(shift_name) NOT LIKE '%MALAM%'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_plans', function (Blueprint $table) {
            $table->dropColumn('shift_master_id');
        });
        Schema::table('shift_submissions', function (Blueprint $table) {
            $table->dropColumn('shift_master_id');
        });
    }
};
