<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lembar_inspeksi', function (Blueprint $table) {
            for ($i = 1; $i <= 5; $i++) {
                if (!Schema::hasColumn('lembar_inspeksi', "dimensi{$i}_nominal")) {
                    $table->text("dimensi{$i}_nominal")->nullable()->after("dimensi{$i}");
                }
                if (!Schema::hasColumn('lembar_inspeksi', "dimensi{$i}_plus")) {
                    $table->text("dimensi{$i}_plus")->nullable()->after("dimensi{$i}_nominal");
                }
                if (!Schema::hasColumn('lembar_inspeksi', "dimensi{$i}_minus")) {
                    $table->text("dimensi{$i}_minus")->nullable()->after("dimensi{$i}_plus");
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('lembar_inspeksi', function (Blueprint $table) {
            for ($i = 1; $i <= 5; $i++) {
                $table->dropColumn([
                    "dimensi{$i}_nominal",
                    "dimensi{$i}_plus",
                    "dimensi{$i}_minus"
                ]);
            }
        });
    }
};
