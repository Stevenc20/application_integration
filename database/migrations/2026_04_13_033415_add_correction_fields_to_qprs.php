<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qprs', function (Blueprint $table) {
            $table->json('correction_items')->nullable()->after('correction');
            $table->json('dampak_items')->nullable()->after('pencegahan');
            $table->string('pic_seksi')->nullable()->after('pic');
        });
    }

    public function down(): void
    {
        Schema::table('qprs', function (Blueprint $table) {
            $table->dropColumn(['correction_items', 'dampak_items', 'pic_seksi']);
        });
    }
};
