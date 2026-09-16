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
        Schema::table('li_templates', function (Blueprint $table) {
            $table->dropUnique('li_templates_part_no_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('li_templates', function (Blueprint $table) {
            $table->unique('part_no', 'li_templates_part_no_unique');
        });
    }
};
