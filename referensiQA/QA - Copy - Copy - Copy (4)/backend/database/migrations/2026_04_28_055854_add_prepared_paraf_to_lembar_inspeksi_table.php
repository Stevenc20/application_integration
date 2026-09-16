<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lembar_inspeksi', function (Blueprint $table) {
            $table->string('prepared_paraf')->nullable()->after('catatan');
            $table->timestamp('prepared_at')->nullable()->after('prepared_paraf');
        });
    }

    public function down(): void
    {
        Schema::table('lembar_inspeksi', function (Blueprint $table) {
            $table->dropColumn(['prepared_paraf', 'prepared_at']);
        });
    }
};
