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
        Schema::table('qprs', function (Blueprint $table) {
            $table->foreignId('parent_qpr_id')->nullable()->after('id')->constrained('qprs')->nullOnDelete();
            $table->boolean('is_a3_required')->default(false)->after('status');
            $table->date('a3_due_date')->nullable()->after('is_a3_required');
            $table->string('a3_document')->nullable()->after('a3_due_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('qprs', function (Blueprint $table) {
            $table->dropForeign(['parent_qpr_id']);
            $table->dropColumn(['parent_qpr_id', 'is_a3_required', 'a3_due_date', 'a3_document']);
        });
    }
};
