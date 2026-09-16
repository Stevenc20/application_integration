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
        Schema::table('schedule_stampings', function (Blueprint $table) {
            $table->string('sect_head_ppc')->nullable()->after('revisi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedule_stampings', function (Blueprint $table) {
            $table->dropColumn('sect_head_ppc');
        });
    }
};
