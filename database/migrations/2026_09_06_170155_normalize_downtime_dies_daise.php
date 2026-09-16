<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('downtimes')
            ->whereIn('jenis_downtime', ['Dies (Daise)', 'dies (daise)', 'DIES (DAISE)'])
            ->update(['jenis_downtime' => 'dies']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No down migration needed for data normalization
    }
};
