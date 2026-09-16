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
        Schema::table('shift_submissions', function (Blueprint $table) {
            $table->unique(['line_id', 'work_date', 'shift_master_id'], 'shift_submissions_unique_composite');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shift_submissions', function (Blueprint $table) {
            $table->dropUnique('shift_submissions_unique_composite');
        });
    }
};
