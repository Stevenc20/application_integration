<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lembar_inspeksi', function (Blueprint $table) {
            $table->decimal('tact_time', 8, 3)->nullable()->after('max_sample')
                  ->comment('Tact Time per pcs dalam detik');
            $table->decimal('ct_dimensi', 8, 3)->nullable()->after('tact_time')
                  ->comment('Cycle Time check dengan dimensi dalam detik');
            $table->decimal('ct_tanpa_dimensi', 8, 3)->nullable()->after('ct_dimensi')
                  ->comment('Cycle Time check tanpa dimensi dalam detik');
        });
    }

    public function down(): void
    {
        Schema::table('lembar_inspeksi', function (Blueprint $table) {
            $table->dropColumn(['tact_time', 'ct_dimensi', 'ct_tanpa_dimensi']);
        });
    }
};

