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

        $nameE = $isPressStyle ? 'PRESS E' : 'Line E';
        $nameF = $isPressStyle ? 'PRESS F' : 'Line F';

        $existsE = DB::table('line_masters')
            ->where(function ($q) {
                $q->where('line_name', 'Line E')
                  ->orWhere('line_name', 'PRESS E')
                  ->orWhere('line_code', 'PE')
                  ->orWhere('line_code', 'L-E')
                  ->orWhere('line_code', 'L-05');
            })
            ->exists();

        if (!$existsE) {
            DB::table('line_masters')->insert([
                'line_code'   => 'PE',
                'line_name'   => $nameE,
                'capacity'    => 0,
                'shift'       => 'Pagi',
                'status'      => 'active',
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        $existsF = DB::table('line_masters')
            ->where(function ($q) {
                $q->where('line_name', 'Line F')
                  ->orWhere('line_name', 'PRESS F')
                  ->orWhere('line_code', 'PF')
                  ->orWhere('line_code', 'L-F')
                  ->orWhere('line_code', 'L-06');
            })
            ->exists();

        if (!$existsF) {
            DB::table('line_masters')->insert([
                'line_code'   => 'PF',
                'line_name'   => $nameF,
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
                ->whereIn('line_code', ['PE', 'PF'])
                ->delete();
        }
    }
};
