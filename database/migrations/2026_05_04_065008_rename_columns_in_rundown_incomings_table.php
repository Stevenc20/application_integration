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
        Schema::table('rundown_incomings', function (Blueprint $table) {
            $table->renameColumn('category', 'movement');
        });

        Schema::table('rundown_incomings', function (Blueprint $table) {
            $table->renameColumn('finish_part', 'category');
        });
    }

    public function down(): void
    {
        Schema::table('rundown_incomings', function (Blueprint $table) {
            $table->renameColumn('category', 'finish_part');
        });

        Schema::table('rundown_incomings', function (Blueprint $table) {
            $table->renameColumn('movement', 'category');
        });
    }
};
