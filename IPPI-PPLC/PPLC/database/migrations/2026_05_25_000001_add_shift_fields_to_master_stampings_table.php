<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_stampings', function (Blueprint $table) {
            $table->boolean('is_shift_pagi')->default(false)->after('remarks');
            $table->boolean('is_shift_malam')->default(false)->after('is_shift_pagi');
            
            $table->index('is_shift_pagi');
            $table->index('is_shift_malam');
        });
    }

    public function down(): void
    {
        Schema::table('master_stampings', function (Blueprint $table) {
            $table->dropIndex(['is_shift_pagi']);
            $table->dropIndex(['is_shift_malam']);
            $table->dropColumn(['is_shift_pagi', 'is_shift_malam']);
        });
    }
};
