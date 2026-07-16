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
        Schema::table('materials', function (Blueprint $table) {
            if (!Schema::hasColumn('materials', 'vendor_id')) {
                $table->unsignedBigInteger('vendor_id')->nullable()->after('min_stok');
                $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('set null');
            }
            if (!Schema::hasColumn('materials', 'process_vendor_id')) {
                $table->unsignedBigInteger('process_vendor_id')->nullable()->after('vendor_id');
                $table->foreign('process_vendor_id')->references('id')->on('vendors')->onDelete('set null');
            }
            if (!Schema::hasColumn('materials', 'standard_price')) {
                $table->decimal('standard_price', 15, 2)->default(0)->after('process_vendor_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->dropForeign(['vendor_id']);
            $table->dropForeign(['process_vendor_id']);
            $table->dropColumn(['vendor_id', 'process_vendor_id', 'standard_price']);
        });
    }
};
