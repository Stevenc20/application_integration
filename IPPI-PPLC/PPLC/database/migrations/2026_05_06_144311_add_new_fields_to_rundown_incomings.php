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
            $table->string('job_no_finish')->nullable()->after('job_no');
            $table->string('type_pallet')->nullable()->after('job_no_finish');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rundown_incomings', function (Blueprint $table) {
            $table->dropColumn(['job_no_finish', 'type_pallet']);
        });
    }
};
