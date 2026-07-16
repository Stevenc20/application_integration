<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedule_stampings', function (Blueprint $table) {
            $table->integer('sort_order')->nullable()->after('row_type');
        });

        // Isi sort_order dengan nilai id yang sudah ada agar urutan tetap sama
        DB::statement('UPDATE schedule_stampings SET sort_order = id');
    }

    public function down(): void
    {
        Schema::table('schedule_stampings', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
