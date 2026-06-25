<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_plans', function (Blueprint $table) {
            $table->string('source_type', 20)->default('ppc')->after('recovery_id');
        });

        DB::table('production_plans')->whereNotNull('recovery_id')->update(['source_type' => 'recovery']);
    }

    public function down(): void
    {
        Schema::table('production_plans', function (Blueprint $table) {
            $table->dropColumn('source_type');
        });
    }
};
