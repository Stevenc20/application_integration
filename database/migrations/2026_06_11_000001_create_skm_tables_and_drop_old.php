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
        // Drop old placeholder tables if they exist
        Schema::dropIfExists('summary_kanban_items');
        Schema::dropIfExists('summary_kanbans');

        // Create skm_demands table
        Schema::create('skm_demands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained('materials')->cascadeOnDelete();
            $table->decimal('demand_qty', 15, 3);
            $table->integer('working_days')->default(22);
            $table->string('period', 20)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Create skm_orders table
        Schema::create('skm_orders', function (Blueprint $table) {
            $table->id();
            $table->string('skm_number', 20)->unique();
            $table->date('order_date');
            $table->enum('status', ['draft', 'sent', 'partial_received', 'completed', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Create skm_order_items table
        Schema::create('skm_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('skm_order_id')->constrained('skm_orders')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materials');
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->decimal('kanban_qty', 15, 3);
            $table->integer('num_cards');
            $table->decimal('order_qty', 15, 3);
            $table->date('expected_delivery_date')->nullable();
            $table->foreignId('storage_location_id')->nullable()->constrained('storage_locations')->nullOnDelete();
            $table->decimal('current_stock', 15, 3)->default(0);
            $table->decimal('min_stock', 15, 3)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('skm_order_items');
        Schema::dropIfExists('skm_orders');
        Schema::dropIfExists('skm_demands');
    }
};
