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
        Schema::table('lembar_inspeksi', function (Blueprint $table) {
            $table->string('appearance13')->nullable()->after('appearance12');
            $table->string('appearance14')->nullable()->after('appearance13');
            $table->json('appearance13_results')->nullable()->after('appearance12_results');
            $table->json('appearance14_results')->nullable()->after('appearance13_results');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lembar_inspeksi', function (Blueprint $table) {
            $table->dropColumn(['appearance13', 'appearance14', 'appearance13_results', 'appearance14_results']);
        });
    }
};
