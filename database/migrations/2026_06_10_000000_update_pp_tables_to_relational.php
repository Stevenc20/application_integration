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
        // Drop existing mock tables
        Schema::dropIfExists('production_orders');
        Schema::dropIfExists('boms');

        // BOMs (Bill of Materials header)
        Schema::create('boms', function (Blueprint $table) {
            $table->id();
            $table->string('bom_number', 20)->unique();
            $table->foreignId('material_id')->constrained('materials')->onDelete('cascade');
            $table->decimal('base_quantity', 15, 3)->default(1);
            $table->date('valid_from');
            $table->date('valid_to')->nullable();
            $table->string('status')->default('active'); // active, inactive
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // BOM Items (components)
        Schema::create('bom_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bom_id')->constrained('boms')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materials')->onDelete('cascade');
            $table->decimal('quantity', 15, 3);
            $table->string('unit', 10)->default('PCS');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Production Orders
        Schema::create('production_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 20)->unique();
            $table->foreignId('material_id')->constrained('materials')->onDelete('cascade');
            $table->foreignId('bom_id')->nullable()->constrained('boms')->onDelete('set null');
            $table->unsignedBigInteger('routing_id')->nullable(); // nullable routing reference
            $table->decimal('quantity_planned', 15, 3);
            $table->decimal('quantity_produced', 15, 3)->default(0);
            $table->decimal('quantity_ok', 15, 3)->default(0);
            $table->decimal('quantity_ng', 15, 3)->default(0);
            $table->date('planned_start_date');
            $table->date('planned_end_date');
            $table->date('actual_start_date')->nullable();
            $table->date('actual_end_date')->nullable();
            $table->string('status')->default('created'); // created, released, in_progress, confirmed, completed, cancelled
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });

        // Production Order Components
        Schema::create('production_order_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')->constrained('production_orders')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materials')->onDelete('cascade');
            $table->decimal('quantity_required', 15, 3);
            $table->decimal('quantity_issued', 15, 3)->default(0);
            $table->foreignId('storage_location_id')->nullable()->constrained('storage_locations')->onDelete('set null');
            $table->timestamps();
        });

        // MRP Runs
        Schema::create('mrp_runs', function (Blueprint $table) {
            $table->id();
            $table->dateTime('run_date');
            $table->foreignId('run_by')->constrained('users')->onDelete('cascade');
            $table->string('status')->default('completed'); // completed, failed
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // MRP Results
        Schema::create('mrp_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mrp_run_id')->constrained('mrp_runs')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materials')->onDelete('cascade');
            $table->decimal('current_stock', 15, 3)->default(0);
            $table->decimal('required_quantity', 15, 3)->default(0);
            $table->decimal('gross_requirement', 15, 3)->default(0);
            $table->decimal('open_po_qty', 15, 3)->default(0);
            $table->decimal('net_requirement', 15, 3)->default(0);
            $table->decimal('safety_stock_qty', 15, 3)->default(0);
            $table->decimal('qty_per_case', 15, 3)->default(0);
            $table->decimal('shortage_quantity', 15, 3)->default(0);
            $table->string('recommendation_type')->default('purchase'); // purchase, production
            $table->decimal('recommended_quantity', 15, 3)->default(0);
            $table->date('recommended_date');
            $table->timestamps();
        });

        // MRP Demands
        Schema::create('mrp_demands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained('materials')->onDelete('cascade');
            $table->decimal('order_quantity', 15, 3);
            $table->string('customer_name')->nullable();
            $table->string('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mrp_demands');
        Schema::dropIfExists('mrp_results');
        Schema::dropIfExists('mrp_runs');
        Schema::dropIfExists('production_order_components');
        Schema::dropIfExists('production_orders');
        Schema::dropIfExists('bom_items');
        Schema::dropIfExists('boms');
    }
};
