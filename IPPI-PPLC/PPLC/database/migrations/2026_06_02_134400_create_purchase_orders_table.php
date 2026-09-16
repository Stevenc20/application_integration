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
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('no_po')->unique();
            $table->unsignedBigInteger('vendor_id');
            $table->date('tanggal_order');
            $table->date('estimasi_terima');
            $table->text('catatan')->nullable();
            $table->string('status')->default('Draft'); // Draft, Sent, Received, Cancelled
            $table->timestamps();

            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('cascade');
        });

        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_order_id');
            $table->unsignedBigInteger('material_id');
            $table->double('qty');
            $table->double('qty_received')->default(0);
            $table->timestamps();

            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->onDelete('cascade');
            $table->foreign('material_id')->references('id')->on('materials')->onDelete('cascade');
        });

        // Seed POs matching the screenshot
        $initialPOs = [
            ['no_po' => 'PO-2026-00006', 'vendor_id' => 8, 'tanggal_order' => '2026-05-30', 'estimasi_terima' => '2026-05-30', 'catatan' => 'ADD STOK AWAL', 'status' => 'Received'],
            ['no_po' => 'PO-2026-00005', 'vendor_id' => 4, 'tanggal_order' => '2026-05-30', 'estimasi_terima' => '2026-05-30', 'catatan' => 'ADD STOK AWAL', 'status' => 'Received'],
            ['no_po' => 'PO-2026-00004', 'vendor_id' => 8, 'tanggal_order' => '2026-05-30', 'estimasi_terima' => '2026-05-30', 'catatan' => 'ADD STOK AWAL', 'status' => 'Received'],
            ['no_po' => 'PO-2026-00003', 'vendor_id' => 3, 'tanggal_order' => '2026-05-30', 'estimasi_terima' => '2026-05-30', 'catatan' => 'ADD STOK AWAL', 'status' => 'Received'],
            ['no_po' => 'PO-2026-00002', 'vendor_id' => 7, 'tanggal_order' => '2026-05-30', 'estimasi_terima' => '2026-05-30', 'catatan' => 'ADD STOK AWAL', 'status' => 'Received'],
            ['no_po' => 'PO-2026-00001', 'vendor_id' => 11, 'tanggal_order' => '2026-05-30', 'estimasi_terima' => '2026-05-30', 'catatan' => 'ADD STOK AWAL', 'status' => 'Received'],
        ];

        foreach ($initialPOs as $po) {
            $po['created_at'] = now();
            $po['updated_at'] = now();
            $poId = DB::table('purchase_orders')->insertGetId($po);

            // Add 2-3 sample items per PO
            for ($i = 1; $i <= 2; $i++) {
                DB::table('purchase_order_items')->insert([
                    'purchase_order_id' => $poId,
                    'material_id' => $i,
                    'qty' => 100 * $i,
                    'qty_received' => $po['status'] === 'Received' ? 100 * $i : 0,
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
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
    }
};
