<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds dimensi6 and dimensi7 columns to both lembar_inspeksi and li_templates.
     *
     * All new columns use text() / json() instead of string() (varchar) because the
     * lembar_inspeksi table is near MySQL's 65535-byte non-BLOB row limit.
     * TEXT and JSON columns are exempt from that count (stored off-page via InnoDB).
     */
    public function up(): void
    {
        // Ensure DYNAMIC row format so InnoDB stores off-page data efficiently.
        DB::statement('ALTER TABLE `lembar_inspeksi` ROW_FORMAT=DYNAMIC');
        DB::statement('ALTER TABLE `li_templates`    ROW_FORMAT=DYNAMIC');

        // ── lembar_inspeksi ──────────────────────────────────────────────────
        Schema::table('lembar_inspeksi', function (Blueprint $table) {
            foreach ([6, 7] as $i) {
                // Label (standard text — e.g. "Ø15MM +0.5/-0.5")
                if (!Schema::hasColumn('lembar_inspeksi', "dimensi{$i}")) {
                    $table->text("dimensi{$i}")->nullable()->after('dimensi5');
                }
                // Numeric tolerance details
                if (!Schema::hasColumn('lembar_inspeksi', "dimensi{$i}_nominal")) {
                    $table->text("dimensi{$i}_nominal")->nullable()->after("dimensi{$i}");
                }
                if (!Schema::hasColumn('lembar_inspeksi', "dimensi{$i}_plus")) {
                    $table->text("dimensi{$i}_plus")->nullable()->after("dimensi{$i}_nominal");
                }
                if (!Schema::hasColumn('lembar_inspeksi', "dimensi{$i}_minus")) {
                    $table->text("dimensi{$i}_minus")->nullable()->after("dimensi{$i}_plus");
                }
                // Item name & measurement method
                if (!Schema::hasColumn('lembar_inspeksi', "dimensi{$i}_item")) {
                    $table->text("dimensi{$i}_item")->nullable()->after("dimensi{$i}_minus");
                }
                if (!Schema::hasColumn('lembar_inspeksi', "dimensi{$i}_method")) {
                    $table->text("dimensi{$i}_method")->nullable()->after("dimensi{$i}_item");
                }
                // Legacy 3-sample columns (kept for backward compat)
                foreach ([1, 2, 3] as $s) {
                    if (!Schema::hasColumn('lembar_inspeksi', "dimensi{$i}_sample_{$s}")) {
                        $table->text("dimensi{$i}_sample_{$s}")->nullable()->after("dimensi{$i}_method");
                    }
                }
                // Full JSON results for all pcs columns
                if (!Schema::hasColumn('lembar_inspeksi', "dimensi{$i}_results")) {
                    $table->json("dimensi{$i}_results")->nullable()->after("dimensi{$i}_sample_3");
                }
            }
        });

        // ── li_templates ─────────────────────────────────────────────────────
        Schema::table('li_templates', function (Blueprint $table) {
            foreach ([6, 7] as $i) {
                if (!Schema::hasColumn('li_templates', "dimensi{$i}")) {
                    $table->text("dimensi{$i}")->nullable()->after('dimensi5_method');
                }
                if (!Schema::hasColumn('li_templates', "dimensi{$i}_item")) {
                    $table->text("dimensi{$i}_item")->nullable()->after("dimensi{$i}");
                }
                if (!Schema::hasColumn('li_templates', "dimensi{$i}_method")) {
                    $table->text("dimensi{$i}_method")->nullable()->after("dimensi{$i}_item");
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('lembar_inspeksi', function (Blueprint $table) {
            $cols = [];
            foreach ([6, 7] as $i) {
                $cols[] = "dimensi{$i}";
                $cols[] = "dimensi{$i}_nominal";
                $cols[] = "dimensi{$i}_plus";
                $cols[] = "dimensi{$i}_minus";
                $cols[] = "dimensi{$i}_item";
                $cols[] = "dimensi{$i}_method";
                foreach ([1, 2, 3] as $s) {
                    $cols[] = "dimensi{$i}_sample_{$s}";
                }
                $cols[] = "dimensi{$i}_results";
            }
            $table->dropColumn($cols);
        });

        Schema::table('li_templates', function (Blueprint $table) {
            $cols = [];
            foreach ([6, 7] as $i) {
                $cols[] = "dimensi{$i}";
                $cols[] = "dimensi{$i}_item";
                $cols[] = "dimensi{$i}_method";
            }
            $table->dropColumn($cols);
        });
    }
};
