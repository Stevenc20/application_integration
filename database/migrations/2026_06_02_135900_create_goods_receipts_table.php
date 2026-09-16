<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('no_gr')->unique();
            $table->unsignedBigInteger('purchase_order_id')->nullable();
            $table->date('tanggal_terima');
            $table->unsignedBigInteger('storage_location_id');
            $table->string('status')->default('posted'); // drafted, posted
            $table->timestamps();

            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->onDelete('set null');
            $table->foreign('storage_location_id')->references('id')->on('storage_locations')->onDelete('cascade');
        });

        Schema::create('goods_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('goods_receipt_id');
            $table->unsignedBigInteger('material_id');
            $table->double('qty');
            $table->timestamps();

            $table->foreign('goods_receipt_id')->references('id')->on('goods_receipts')->onDelete('cascade');
            $table->foreign('material_id')->references('id')->on('materials')->onDelete('cascade');
        });

        // Seed GRs matching the screenshot exactly
        // PO numbers & mappings:
        // GR-2026-00006 -> PO-2026-00004 (PO ID 3)
        // GR-2026-00005 -> PO-2026-00005 (PO ID 2)
        // GR-2026-00004 -> PO-2026-00003 (PO ID 4)
        // GR-2026-00003 -> PO-2026-00002 (PO ID 5)
        // GR-2026-00002 -> PO-2026-00001 (PO ID 6)
        // GR-2026-00001 -> PO-2026-00006 (PO ID 1)
        $initialGRs = [
            ['no_gr' => 'GR-2026-00006', 'purchase_order_id' => 3, 'tanggal_terima' => '2026-05-30', 'storage_location_id' => 3, 'status' => 'posted'],
            ['no_gr' => 'GR-2026-00005', 'purchase_order_id' => 2, 'tanggal_terima' => '2026-05-30', 'storage_location_id' => 3, 'status' => 'posted'],
            ['no_gr' => 'GR-2026-00004', 'purchase_order_id' => 4, 'tanggal_terima' => '2026-05-30', 'storage_location_id' => 3, 'status' => 'posted'],
            ['no_gr' => 'GR-2026-00003', 'purchase_order_id' => 5, 'tanggal_terima' => '2026-05-30', 'storage_location_id' => 3, 'status' => 'posted'],
            ['no_gr' => 'GR-2026-00002', 'purchase_order_id' => 6, 'tanggal_terima' => '2026-05-30', 'storage_location_id' => 3, 'status' => 'posted'],
            ['no_gr' => 'GR-2026-00001', 'purchase_order_id' => 1, 'tanggal_terima' => '2026-05-30', 'storage_location_id' => 3, 'status' => 'posted'],
        ];

        foreach ($initialGRs as $gr) {
            $gr['created_at'] = now();
            $gr['updated_at'] = now();
            $grId = DB::table('goods_receipts')->insertGetId($gr);

            // Add item matching PO item quantities
            // Let's copy from PO items or insert 2 sample materials
            for ($i = 1; $i <= 2; $i++) {
                DB::table('goods_receipt_items')->insert([
                    'goods_receipt_id' => $grId,
                    'material_id' => $i,
                    'qty' => 100 * $i,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_items');
        Schema::dropIfExists('goods_receipts');
    }
};
