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
            for ($i = 1; $i <= 7; $i++) {
                if (!Schema::hasColumn('li_templates', "dimensi{$i}_nominal")) {
                    $table->float("dimensi{$i}_nominal")->nullable()->after("dimensi{$i}_method");
                    $table->float("dimensi{$i}_plus")->nullable()->after("dimensi{$i}_nominal");
                    $table->float("dimensi{$i}_minus")->nullable()->after("dimensi{$i}_plus");
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('li_templates', function (Blueprint $table) {
            for ($i = 1; $i <= 7; $i++) {
                if (Schema::hasColumn('li_templates', "dimensi{$i}_nominal")) {
                    $table->dropColumn("dimensi{$i}_nominal");
                    $table->dropColumn("dimensi{$i}_plus");
                    $table->dropColumn("dimensi{$i}_minus");
                }
            }
        });
    }
};
