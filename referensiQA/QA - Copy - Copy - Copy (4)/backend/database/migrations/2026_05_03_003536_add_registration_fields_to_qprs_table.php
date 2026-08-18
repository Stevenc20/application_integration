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
        Schema::table('qprs', function (Blueprint $table) {
            $table->date('target_selesai')->nullable()->after('target');
            $table->string('verif_1')->nullable()->after('target_selesai');
            $table->string('verif_2')->nullable()->after('verif_1');
            $table->string('verif_3')->nullable()->after('verif_2');
            $table->string('hasil')->nullable()->after('verif_3');
            $table->string('remark')->nullable()->after('hasil');
        });
    }

    public function down(): void
    {
        Schema::table('qprs', function (Blueprint $table) {
            $table->dropColumn(['target_selesai', 'verif_1', 'verif_2', 'verif_3', 'hasil', 'remark']);
        });
    }
};
