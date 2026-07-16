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
        Schema::table('purchase_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_orders', 'storage_location_id')) {
                $table->unsignedBigInteger('storage_location_id')->nullable()->after('vendor_id');
                $table->foreign('storage_location_id')->references('id')->on('storage_locations')->onDelete('set null');
            }
            if (!Schema::hasColumn('purchase_orders', 'total_amount')) {
                $table->decimal('total_amount', 15, 2)->default(0)->after('status');
            }
            if (!Schema::hasColumn('purchase_orders', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('total_amount');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('purchase_orders', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('created_by');
            }
            if (!Schema::hasColumn('purchase_orders', 'approved_by')) {
                $table->string('approved_by')->nullable()->after('approved_at');
            }
            if (!Schema::hasColumn('purchase_orders', 'skm_order_id')) {
                $table->unsignedBigInteger('skm_order_id')->nullable()->after('approved_by');
            }
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_order_items', 'unit_price')) {
                $table->decimal('unit_price', 15, 2)->default(0)->after('qty_received');
            }
            if (!Schema::hasColumn('purchase_order_items', 'total_price')) {
                $table->decimal('total_price', 15, 2)->default(0)->after('unit_price');
            }
            if (!Schema::hasColumn('purchase_order_items', 'expected_delivery_date')) {
                $table->date('expected_delivery_date')->nullable()->after('total_price');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropColumn(['unit_price', 'total_price', 'expected_delivery_date']);
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['storage_location_id']);
            $table->dropForeign(['created_by']);
            $table->dropColumn([
                'storage_location_id',
                'total_amount',
                'created_by',
                'approved_at',
                'approved_by',
                'skm_order_id'
            ]);
        });
    }
};
