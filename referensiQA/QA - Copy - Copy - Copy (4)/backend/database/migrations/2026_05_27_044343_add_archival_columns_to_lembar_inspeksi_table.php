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
            $table->timestamp('archived_at')->nullable()->after('status');
            $table->string('archive_reason')->nullable()->after('archived_at');
            $table->string('archived_pdf_path')->nullable()->after('archive_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lembar_inspeksi', function (Blueprint $table) {
            $table->dropColumn(['archived_at', 'archive_reason', 'archived_pdf_path']);
        });
    }
};
