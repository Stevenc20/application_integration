<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Rename old ENUM values — MySQL only; SQLite uses VARCHAR + CHECK
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE recovery_items MODIFY COLUMN status ENUM('pending','waiting_approval','approved','rejected','scheduled','in_production','completed') DEFAULT 'pending'");
        }

        // Step 2: Migrate data
        DB::table('recovery_items')->where('status', 'pending')->update(['status' => 'waiting_approval']);

        // Step 3: Drop old ENUM values — MySQL only; SQLite uses VARCHAR + CHECK
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE recovery_items MODIFY COLUMN status ENUM('waiting_approval','approved','rejected','scheduled','in_production','completed') DEFAULT 'waiting_approval'");
        }

        // Step 4: Add original_row_no for FIFO ordering
        Schema::table('recovery_items', function (Blueprint $table) {
            $table->unsignedInteger('original_row_no')->nullable()->after('original_shift_name');
        });
    }

    public function down(): void
    {
        Schema::table('recovery_items', function (Blueprint $table) {
            $table->dropColumn('original_row_no');
        });

        // Step 1: Migrate data back
        DB::table('recovery_items')->where('status', 'waiting_approval')->update(['status' => 'pending']);
        DB::table('recovery_items')->whereIn('status', ['in_production', 'completed'])->update(['status' => 'scheduled']);

        // Step 2: Restore old ENUM values — MySQL only; SQLite uses VARCHAR + CHECK
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE recovery_items MODIFY COLUMN status ENUM('pending','waiting_approval','approved','rejected','scheduled','in_production','completed') DEFAULT 'pending'");
            DB::statement("ALTER TABLE recovery_items MODIFY COLUMN status ENUM('pending','approved','rejected','scheduled','completed') DEFAULT 'pending'");
        }
    }
};

