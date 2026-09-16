<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('lembar_inspeksi', function (Blueprint $table) {
            // Waktu assign dan claim
            $table->timestamp('assigned_at')->nullable()->after('assigned_foreman_id');
            $table->timestamp('operator_claimed_at')->nullable()->after('assigned_at');

            // Field revision system — JSON berisi field mana yang direvisi Azriel
            // Format: {"partNo": {"catatan": "...", "by": "Azriel", "at": "...", "resolved": false}}
            $table->json('field_revisions')->nullable()->after('revision_records');
        });
    }

    public function down(): void {
        Schema::table('lembar_inspeksi', function (Blueprint $table) {
            $table->dropColumn(['assigned_at', 'operator_claimed_at', 'field_revisions']);
        });
    }
};
