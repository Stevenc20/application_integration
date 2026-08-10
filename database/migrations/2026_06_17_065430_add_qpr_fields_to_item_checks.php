<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('item_checks', function (Blueprint $table) {
            $table->boolean('qpr_generated')->default(false)->after('status');
            $table->unsignedBigInteger('qpr_id')->nullable()->after('qpr_generated');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('item_checks', function (Blueprint $table) {
            $table->dropColumn(['qpr_generated', 'qpr_id']);
        });
    }
};
