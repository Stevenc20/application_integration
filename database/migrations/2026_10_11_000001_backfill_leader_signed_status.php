<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('hambatan_jalur')
            ->where('status', 'signed')
            ->whereNull('foreman_signature_image')
            ->whereNotNull('leader_signature_image')
            ->update(['status' => 'leader_signed']);
    }

    public function down(): void
    {
        DB::table('hambatan_jalur')
            ->where('status', 'leader_signed')
            ->whereNull('foreman_signature_image')
            ->whereNotNull('leader_signature_image')
            ->update(['status' => 'signed']);
    }
};