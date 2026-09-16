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
            $table->string('dimensi1_method')->nullable()->after('dimensi1_item');
            $table->string('dimensi2_method')->nullable()->after('dimensi2_item');
            $table->string('dimensi3_method')->nullable()->after('dimensi3_item');
            $table->string('dimensi4_method')->nullable()->after('dimensi4_item');
            $table->string('dimensi5_method')->nullable()->after('dimensi5_item');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lembar_inspeksi', function (Blueprint $table) {
            $table->dropColumn([
                'dimensi1_method',
                'dimensi2_method',
                'dimensi3_method',
                'dimensi4_method',
                'dimensi5_method',
            ]);
        });
    }
};
