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
            $table->foreign('shift_master_id')->references('id')->on('master_shifts')->onDelete('set null');
        });
        
        Schema::table('shift_submissions', function (Blueprint $table) {
            $table->foreign('shift_master_id')->references('id')->on('master_shifts')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('production_plans', function (Blueprint $table) {
            $table->dropForeign(['shift_master_id']);
        });
        
        Schema::table('shift_submissions', function (Blueprint $table) {
            $table->dropForeign(['shift_master_id']);
        });
    }
};
