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
        Schema::table('qpr_actions', function (Blueprint $table) {
            $table->string('verif_1_status')->nullable()->after('tgl_verif_1');
            $table->string('verif_2_status')->nullable()->after('tgl_verif_2');
            $table->string('verif_3_status')->nullable()->after('tgl_verif_3');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('qpr_actions', function (Blueprint $table) {
            $table->dropColumn(['verif_1_status', 'verif_2_status', 'verif_3_status']);
        });
    }
};
