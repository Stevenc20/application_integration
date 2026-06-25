<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Add new enum values while keeping old ones
        DB::statement("ALTER TABLE recovery_items MODIFY COLUMN status ENUM('pending','waiting_approval','approved','rejected','scheduled','in_production','completed') DEFAULT 'pending'");

        // Step 2: Migrate data
        DB::table('recovery_items')->where('status', 'pending')->update(['status' => 'waiting_approval']);

        // Step 3: Remove old enum value and set new default
        DB::statement("ALTER TABLE recovery_items MODIFY COLUMN status ENUM('waiting_approval','approved','rejected','scheduled','in_production','completed') DEFAULT 'waiting_approval'");

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

        // Step 1: Add old enum value back
        DB::statement("ALTER TABLE recovery_items MODIFY COLUMN status ENUM('pending','waiting_approval','approved','rejected','scheduled','in_production','completed') DEFAULT 'pending'");

        // Step 2: Migrate data back
        DB::table('recovery_items')->where('status', 'waiting_approval')->update(['status' => 'pending']);
        DB::table('recovery_items')->whereIn('status', ['in_production', 'completed'])->update(['status' => 'scheduled']);

        // Step 3: Remove new enum values
        DB::statement("ALTER TABLE recovery_items MODIFY COLUMN status ENUM('pending','approved','rejected','scheduled','completed') DEFAULT 'pending'");
    }
};

