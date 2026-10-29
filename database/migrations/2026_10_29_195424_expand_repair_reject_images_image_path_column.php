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
        // 1. Periksa dan ubah tipe kolom image_path menjadi TEXT / VARCHAR(1000)
        // Gunakan try-catch dan raw statement agar kompatibel dengan MySQL, MariaDB, dan SQLite
        try {
            $driver = DB::connection()->getDriverName();
            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                DB::statement("ALTER TABLE `repair_reject_images` MODIFY COLUMN `image_path` TEXT NOT NULL");
            } else {
                Schema::table('repair_reject_images', function (Blueprint $table) {
                    $table->text('image_path')->change();
                });
            }
        } catch (\Throwable $e) {
            // Fallback jika doctrine/dbal tidak terinstall atau sintaks engine berbeda
            try {
                DB::statement("ALTER TABLE `repair_reject_images` MODIFY COLUMN `image_path` VARCHAR(1000) NOT NULL");
            } catch (\Throwable $ex) {
                // Jangan gagalkan migration jika struktur sudah sesuai atau engine SQLite di test
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
                DB::statement("ALTER TABLE `repair_reject_images` MODIFY COLUMN `image_path` VARCHAR(255) NOT NULL");
            }
        } catch (\Throwable $e) {
            // Tidak merusak data lama jika rollback
        }
    }
};
