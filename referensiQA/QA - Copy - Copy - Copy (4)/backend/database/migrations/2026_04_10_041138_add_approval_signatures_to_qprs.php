<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qprs', function (Blueprint $table) {
            // Simpan TTD sebagai JSON: [{id, role, sub, nama, signature(base64)}]
            $table->json('approval_signatures')->nullable()->after('pencegahan');
        });
    }

    public function down(): void
    {
        Schema::table('qprs', function (Blueprint $table) {
            $table->dropColumn('approval_signatures');
        });
    }
};