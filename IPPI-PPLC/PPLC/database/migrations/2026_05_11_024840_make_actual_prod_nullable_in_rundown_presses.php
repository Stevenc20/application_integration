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
        Schema::table('rundown_presses', function (Blueprint $table) {
            $table->decimal('actual_prod', 15, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rundown_presses', function (Blueprint $table) {
            $table->decimal('actual_prod', 15, 2)->nullable(false)->default(0)->change();
        });
    }
};
