<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lembar_inspeksi', function (Blueprint $table) {
            $table->string('paraf_gl_bottom_name')->nullable()->after('paraf_gl_bottom');
            $table->string('paraf_fm_bottom_name')->nullable()->after('paraf_foreman_bottom');
        });
    }

    public function down(): void
    {
        Schema::table('lembar_inspeksi', function (Blueprint $table) {
            $table->dropColumn(['paraf_gl_bottom_name', 'paraf_fm_bottom_name']);
        });
    }
};
