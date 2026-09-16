<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rundown_stocks', function (Blueprint $table) {
            $table->id();
            $table->integer('no')->default(0);
            $table->string('job_no')->index();
            $table->string('part_number')->nullable();
            $table->string('sourching')->nullable();
            $table->decimal('qty_palet', 10, 2)->default(0);
            $table->string('type_pallet')->nullable();
            $table->string('proses')->nullable()->index();
            $table->string('source')->nullable();
            $table->string('customer')->nullable()->index();
            $table->string('type_of_part')->nullable()->index();
            $table->string('stock_movement')->nullable()->index();
            $table->string('cycle_issue')->nullable();
            $table->decimal('pcs_day', 12, 4)->default(0);
            $table->decimal('stock_fg', 12, 2)->default(0);
            $table->decimal('strength', 10, 4)->default(0);
            $table->string('remarks')->nullable()->index();
            $table->decimal('stock_sap', 12, 2)->default(0);
            $table->decimal('stock_diff', 12, 2)->default(0);
            $table->decimal('accuracy', 10, 6)->default(0);
            $table->decimal('price_pcs', 15, 2)->default(0);
            $table->decimal('new_price', 15, 2)->default(0);
            $table->decimal('loss_gain', 15, 2)->default(0);
            $table->decimal('pending_gi', 12, 2)->default(0);
            $table->decimal('min_stock', 12, 2)->default(0);
            $table->decimal('max_stock', 12, 2)->default(0);
            $table->decimal('stock_shortage', 12, 2)->default(0);
            $table->integer('status_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rundown_stocks');
    }
};
