<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qprs', function (Blueprint $table) {
            $table->unsignedBigInteger('assigned_foreman_id')->nullable()->after('status');
            $table->foreign('assigned_foreman_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('qprs', function (Blueprint $table) {
            $table->dropForeign(['assigned_foreman_id']);
            $table->dropColumn('assigned_foreman_id');
        });
    }
};