<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qprs', function (Blueprint $table) {
            $table->unsignedBigInteger('inspeksi_id')->nullable()->after('id');
            $table->string('source')->nullable()->after('status'); // 'inspeksi' atau null
            
            $table->foreign('inspeksi_id')
                ->references('id')
                ->on('lembar_inspeksi')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('qprs', function (Blueprint $table) {
            $table->dropForeign(['inspeksi_id']);
            $table->dropColumn(['inspeksi_id', 'source']);
        });
    }
};
