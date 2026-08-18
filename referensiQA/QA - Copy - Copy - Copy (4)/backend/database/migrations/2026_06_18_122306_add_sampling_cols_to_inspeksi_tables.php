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
        Schema::table('li_templates', function (Blueprint $table) {
            $table->json('sampling_cols')->nullable()->after('ct_tanpa_dimensi');
        });
        Schema::table('lembar_inspeksi', function (Blueprint $table) {
            $table->json('sampling_cols')->nullable()->after('ct_tanpa_dimensi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('li_templates', function (Blueprint $table) {
            $table->dropColumn('sampling_cols');
        });
        Schema::table('lembar_inspeksi', function (Blueprint $table) {
            $table->dropColumn('sampling_cols');
        });
    }
};
