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
        Schema::table('repair_reject_images', function (Blueprint $table) {
            $table->string('image_type', 10)->nullable()->after('image_path')->comment('before/after');
        });

        DB::table('repair_reject_images')->whereNull('image_type')->update(['image_type' => 'before']);
    }

    public function down(): void
    {
        Schema::table('repair_reject_images', function (Blueprint $table) {
            $table->dropColumn('image_type');
        });
    }
};
