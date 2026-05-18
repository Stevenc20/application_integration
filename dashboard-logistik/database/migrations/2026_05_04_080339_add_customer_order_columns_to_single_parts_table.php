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
        Schema::table('single_parts', function (Blueprint $table) {
            $table->decimal('iami', 15, 2)->nullable()->default(0)->after('assy');
            $table->decimal('gkd', 15, 2)->nullable()->default(0)->after('iami');
            $table->decimal('sap', 15, 2)->nullable()->default(0)->after('gkd');
            $table->decimal('kap', 15, 2)->nullable()->default(0)->after('sap');
            $table->decimal('gmo', 15, 2)->nullable()->default(0)->after('kap');
        });
    }

    public function down(): void
    {
        Schema::table('single_parts', function (Blueprint $table) {
            $table->dropColumn(['iami', 'gkd', 'sap', 'kap', 'gmo']);
        });
    }
};
