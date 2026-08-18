<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qpr_actions', function (Blueprint $table) {
            $table->date('tgl_verif_1')->nullable()->after('schedule');
            $table->date('tgl_verif_2')->nullable()->after('tgl_verif_1');
            $table->date('tgl_verif_3')->nullable()->after('tgl_verif_2');
            $table->string('ok_result')->nullable()->after('tgl_verif_3');
            $table->string('ok_cd')->nullable()->after('ok_result');
        });
    }

    public function down(): void
    {
        Schema::table('qpr_actions', function (Blueprint $table) {
            $table->dropColumn(['tgl_verif_1','tgl_verif_2','tgl_verif_3','ok_result','ok_cd']);
        });
    }
};
