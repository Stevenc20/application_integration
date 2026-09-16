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
        Schema::table('qpr_actions', function (Blueprint $table) {
            $table->text('evidence_file')->nullable()->after('pic');
            $table->text('evidence_remarks')->nullable()->after('evidence_file');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('qpr_actions', function (Blueprint $table) {
            $table->dropColumn(['evidence_file', 'evidence_remarks']);
        });
    }
};
