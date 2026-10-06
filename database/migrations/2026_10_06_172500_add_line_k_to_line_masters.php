<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('line_masters')) {
            return;
        }

        // Check whether existing lines use "PRESS" or "Line" naming style
        $sample = DB::table('line_masters')->where('status', 'active')->first();
        $isPressStyle = $sample && str_starts_with(strtoupper($sample->line_name), 'PRESS');

        $nameK = $isPressStyle ? 'PRESS K' : 'Line K';

        $existsK = DB::table('line_masters')
            ->where(function ($q) {
                $q->where('line_name', 'Line K')
                  ->orWhere('line_name', 'PRESS K')
                  ->orWhere('line_code', 'PK')
                  ->orWhere('line_code', 'L-K')
                  ->orWhere('line_code', 'L-11');
            })
            ->exists();

        if (!$existsK) {
            DB::table('line_masters')->insert([
                'line_code'   => 'PK',
                'line_name'   => $nameK,
                'capacity'    => 0,
                'shift'       => 'Pagi',
                'status'      => 'active',
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('line_masters')) {
            DB::table('line_masters')
                ->where('line_code', 'PK')
                ->orWhere('line_name', 'PRESS K')
                ->orWhere('line_name', 'Line K')
                ->delete();
        }
    }
};
