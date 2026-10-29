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
        // 1. Pastikan kolom image_path, image_path_part, image_path_tooling ada dan bersifat NULLABLE dengan DEFAULT NULL
        Schema::table('downtimes', function (Blueprint $table) {
            if (!Schema::hasColumn('downtimes', 'image_path')) {
                $table->string('image_path')->nullable()->default(null)->after('pic');
            }
            if (!Schema::hasColumn('downtimes', 'image_path_part')) {
                $table->string('image_path_part')->nullable()->default(null)->after('image_path');
            }
            if (!Schema::hasColumn('downtimes', 'image_path_tooling')) {
                $table->string('image_path_tooling')->nullable()->default(null)->after('image_path_part');
            }
        });

        // 2. Untuk database MySQL / MariaDB, pastikan spesifikasi kolom eksplisit NULL DEFAULT NULL jika sebelumnya terbuat NOT NULL
        try {
            $driver = DB::connection()->getDriverName();
            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                DB::statement("ALTER TABLE `downtimes` MODIFY COLUMN `image_path` VARCHAR(255) NULL DEFAULT NULL");
                DB::statement("ALTER TABLE `downtimes` MODIFY COLUMN `image_path_part` VARCHAR(255) NULL DEFAULT NULL");
                DB::statement("ALTER TABLE `downtimes` MODIFY COLUMN `image_path_tooling` VARCHAR(255) NULL DEFAULT NULL");
            }
        } catch (\Throwable $e) {
            // Abaikan jika database engine tidak mendukung ALTER MODIFY atau sudah sesuai
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Tetap pertahankan kompatibilitas data operasional (tidak melakukan drop kolom agar foto lama tidak hilang)
    }
};
