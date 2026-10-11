<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hambatan_jalur', function (Blueprint $table) {
            $table->longText('foreman_signature_image')->nullable()->after('leader_signed_by');
            $table->dateTime('foreman_signed_at')->nullable()->after('foreman_signature_image');
            $table->foreignId('foreman_signed_by')->nullable()->after('foreman_signed_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('hambatan_jalur', function (Blueprint $table) {
            $table->dropConstrainedForeignId('foreman_signed_by');
            $table->dropColumn(['foreman_signature_image', 'foreman_signed_at']);
        });
    }
};