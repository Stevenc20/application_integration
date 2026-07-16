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
        Schema::table('line_masters', function (Blueprint $table) {
            $table->time('production_start')->nullable()->after('line_name')
                ->comment('Override start time (null = pakai shift start)');
            $table->time('production_end')->nullable()->after('production_start')
                ->comment('Override end time (null = pakai shift end)');
        });

        DB::table('line_masters')->where('line_code', 'PC')->update(['production_end' => '22:00']);
        DB::table('line_masters')->where('line_code', 'PD')->update(['production_start' => '12:45']);
    }

    public function down(): void
    {
        Schema::table('line_masters', function (Blueprint $table) {
            $table->dropColumn(['production_start', 'production_end']);
        });
    }
};
