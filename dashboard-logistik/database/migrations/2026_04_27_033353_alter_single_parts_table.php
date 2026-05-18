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
        Schema::table('single_parts', function (Blueprint $table) {
            $table->renameColumn('material', 'finish_part');
            $table->string('customer')->nullable()->after('material');
            $table->decimal('price_pc', 15, 2)->default(0)->after('customer');
            $table->string('status')->nullable()->after('price_pc');
            $table->string('category')->nullable()->after('status');
            $table->integer('cycle_issue')->nullable()->after('category');
            $table->decimal('all_price', 20, 2)->default(0)->after('stok_akhir');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('single_parts', function (Blueprint $table) {
            $table->dropColumn(['customer', 'price_pc', 'status', 'category', 'cycle_issue', 'all_price']);
            $table->renameColumn('finish_part', 'material');
        });
    }
};
