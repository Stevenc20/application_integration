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
        Schema::table('shift_submissions', function (Blueprint $table) {
            $table->text('comment')->nullable()->after('submitted_by');
            $table->timestamp('cancelled_at')->nullable()->after('comment');
            $table->unsignedBigInteger('cancelled_by')->nullable()->after('cancelled_at');
            $table->unsignedTinyInteger('cancel_count')->default(0)->after('cancelled_by');

            $table->foreign('cancelled_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shift_submissions', function (Blueprint $table) {
            $table->dropForeign(['cancelled_by']);
            $table->dropColumn(['comment', 'cancelled_at', 'cancelled_by', 'cancel_count']);
        });
    }
};