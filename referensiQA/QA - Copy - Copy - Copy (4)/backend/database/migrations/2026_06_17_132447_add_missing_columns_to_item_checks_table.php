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
        Schema::table('item_checks', function (Blueprint $table) {
            $table->integer('repair')->default(0)->after('status');
            $table->integer('reject')->default(0)->after('repair');
            $table->unsignedBigInteger('assigned_gl_id')->nullable()->after('reject');
            $table->unsignedBigInteger('assigned_foreman_id')->nullable()->after('assigned_gl_id');
            $table->json('ng_details')->nullable()->after('hasil_visual');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('item_checks', function (Blueprint $table) {
            $table->dropColumn(['repair', 'reject', 'assigned_gl_id', 'assigned_foreman_id', 'ng_details']);
        });
    }
};
