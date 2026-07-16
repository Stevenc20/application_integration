<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->string('job_no')->index();
            $table->string('item_name')->nullable()->index();
            $table->string('proses')->nullable()->index();
            $table->string('source')->nullable()->index();
            $table->string('customer')->nullable()->index();
            $table->decimal('pcs_day', 12, 4)->default(0);
            $table->decimal('stock', 12, 2)->default(0);
            $table->decimal('strength', 10, 4)->default(0);
            $table->string('remarks')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
