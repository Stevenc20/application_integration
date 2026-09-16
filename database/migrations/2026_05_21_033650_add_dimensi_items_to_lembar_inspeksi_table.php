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
        Schema::table('lembar_inspeksi', function (Blueprint $table) {
            $table->string('dimensi1_item')->nullable()->after('dimensi5');
            $table->string('dimensi2_item')->nullable()->after('dimensi1_item');
            $table->string('dimensi3_item')->nullable()->after('dimensi2_item');
            $table->string('dimensi4_item')->nullable()->after('dimensi3_item');
            $table->string('dimensi5_item')->nullable()->after('dimensi4_item');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lembar_inspeksi', function (Blueprint $table) {
            $table->dropColumn([
                'dimensi1_item',
                'dimensi2_item',
                'dimensi3_item',
                'dimensi4_item',
                'dimensi5_item',
            ]);
        });
    }
};
