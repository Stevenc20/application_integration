<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_plans', function (Blueprint $table) {
            if (!Schema::hasColumn('production_plans', 'p1')) {
                $table->boolean('p1')->default(false)->after('total_mesin');
            }
            if (!Schema::hasColumn('production_plans', 'p2')) {
                $table->boolean('p2')->default(false)->after('p1');
            }
            if (!Schema::hasColumn('production_plans', 'p3')) {
                $table->boolean('p3')->default(false)->after('p2');
            }
            if (!Schema::hasColumn('production_plans', 'p4')) {
                $table->boolean('p4')->default(false)->after('p3');
            }
        });
    }

    public function down(): void
    {
        Schema::table('production_plans', function (Blueprint $table) {
            $table->dropColumn(['p1', 'p2', 'p3', 'p4']);
        });
    }
};
