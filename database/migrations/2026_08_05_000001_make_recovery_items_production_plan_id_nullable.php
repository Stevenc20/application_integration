<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recovery_items', function (Blueprint $table) {
            $table->dropForeign(['production_plan_id']);
        });

        Schema::table('recovery_items', function (Blueprint $table) {
            $table->unsignedBigInteger('production_plan_id')->nullable()->change();
        });

        Schema::table('recovery_items', function (Blueprint $table) {
            $table->foreign('production_plan_id')
                ->references('id')
                ->on('production_plans')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('recovery_items', function (Blueprint $table) {
            $table->dropForeign(['production_plan_id']);
        });

        Schema::table('recovery_items', function (Blueprint $table) {
            $table->unsignedBigInteger('production_plan_id')->nullable(false)->change();
        });

        Schema::table('recovery_items', function (Blueprint $table) {
            $table->foreign('production_plan_id')
                ->references('id')
                ->on('production_plans');
        });
    }
};
