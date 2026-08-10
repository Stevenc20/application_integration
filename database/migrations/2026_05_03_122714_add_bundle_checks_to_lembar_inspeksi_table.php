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
            $table->json('bundle_checks')->nullable()->after('catatan');
            $table->text('bundle_tindakan')->nullable()->after('bundle_checks');
        });
    }

    public function down(): void
    {
        Schema::table('lembar_inspeksi', function (Blueprint $table) {
            $table->dropColumn(['bundle_checks', 'bundle_tindakan']);
        });
    }
};
