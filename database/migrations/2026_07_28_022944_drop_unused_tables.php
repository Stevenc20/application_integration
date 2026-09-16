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
        // Disable foreign key checks just in case there are loose dependencies
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('integration_logs');
        Schema::dropIfExists('complaints');
        Schema::dropIfExists('complaint_actions');
        Schema::dropIfExists('stop_line_logs');
        Schema::dropIfExists('sorting_sessions');
        Schema::dropIfExists('inspection_photos');
        Schema::dropIfExists('parts');
        Schema::dropIfExists('part_defect_standards');

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration removes unused tables. Recreating them would require
        // redefining their original schemas here.
    }
};
