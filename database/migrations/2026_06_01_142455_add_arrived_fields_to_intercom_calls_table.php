<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('intercom_calls', function (Blueprint $table) {
            // Waktu GL/Foreman tiba secara fisik di tablet operator
            $table->timestamp('arrived_at')->nullable()->after('called_at');
            // Nama GL/Foreman yang melakukan check-in fisik
            $table->string('arrived_name')->nullable()->after('arrived_at');
        });
    }

    public function down(): void
    {
        Schema::table('intercom_calls', function (Blueprint $table) {
            $table->dropColumn(['arrived_at', 'arrived_name']);
        });
    }
};
