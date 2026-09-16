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
        Schema::table('lembar_inspeksi', function (Blueprint $table) {
            for ($i = 1; $i <= 5; $i++) {
                $table->json('dimensi' . $i . '_results')->nullable()->after('dimensi' . $i . '_sample_3');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lembar_inspeksi', function (Blueprint $table) {
            for ($i = 1; $i <= 5; $i++) {
                $table->dropColumn('dimensi' . $i . '_results');
            }
        });
    }
};
