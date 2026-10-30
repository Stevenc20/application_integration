<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ubah decimal(5,2) menjadi decimal(8,2) agar efficiency hingga 999,999.99% dapat disimpan tanpa SQLSTATE[22003]
        try {
            $driver = DB::connection()->getDriverName();
            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                DB::statement("ALTER TABLE `daily_productions` MODIFY COLUMN `efficiency` DECIMAL(8,2) NOT NULL DEFAULT 0.00");
            } else {
                Schema::table('daily_productions', function (Blueprint $table) {
                    $table->decimal('efficiency', 8, 2)->default(0)->change();
                });
            }
        } catch (\Throwable $e) {
            // Fallback safe handling
            try {
                DB::statement("ALTER TABLE `daily_productions` MODIFY COLUMN `efficiency` DECIMAL(8,2) DEFAULT 0.00");
            } catch (\Throwable $ex) {
                // Ignore if already changed or unsupported in test engine
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            $driver = DB::connection()->getDriverName();
            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                DB::statement("ALTER TABLE `daily_productions` MODIFY COLUMN `efficiency` DECIMAL(5,2) NOT NULL DEFAULT 0.00");
            }
        } catch (\Throwable $e) {
            // Ignore on down
        }
    }
};
