<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hambatan_jalur', function (Blueprint $table) {
            $table->longText('leader_signature_image')->nullable()->after('signature_image');
            $table->dateTime('leader_signed_at')->nullable()->after('leader_signature_image');
            $table->foreignId('leader_signed_by')->nullable()->after('leader_signed_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('hambatan_jalur', function (Blueprint $table) {
            $table->dropConstrainedForeignId('leader_signed_by');
            $table->dropColumn(['leader_signature_image', 'leader_signed_at']);
        });
    }
};
