<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('signatures', function (Blueprint $table) {
            $table->string('line_name')->nullable()->after('work_date');
            $table->string('shift_name')->nullable()->after('line_name');
        });

        Schema::table('signatures', function (Blueprint $table) {
            // Drop old unique constraint
            $table->dropUnique(['role', 'work_date']);
            // Add new composite unique constraint
            $table->unique(['role', 'work_date', 'line_name', 'shift_name'], 'signatures_composite_unique');
        });
    }

    public function down(): void
    {
        Schema::table('signatures', function (Blueprint $table) {
            $table->dropUnique('signatures_composite_unique');
            $table->unique(['role', 'work_date']);
            $table->dropColumn(['line_name', 'shift_name']);
        });
    }
};
