<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recovery_items', function (Blueprint $table) {
            $table->enum('status', ['pending', 'approved', 'rejected', 'scheduled'])->default('pending')->after('total_mesin');
            $table->date('original_date')->nullable()->after('status');
            $table->string('original_shift_name')->nullable()->after('original_date');
        });
    }

    public function down(): void
    {
        Schema::table('recovery_items', function (Blueprint $table) {
            $table->dropColumn(['status', 'original_date', 'original_shift_name']);
        });
    }
};
