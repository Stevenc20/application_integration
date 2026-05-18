<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('single_parts', function (Blueprint $table) {
            $table->id();
            $table->string('sheet_date')->index(); // nama sheet, misal "13 APRL"
            $table->integer('no')->default(0);
            $table->string('job_no')->index();
            $table->string('vendor')->nullable()->index();
            $table->string('material')->nullable();
            $table->decimal('stock_awal',  12, 2)->default(0);
            $table->decimal('assy',        12, 2)->default(0);
            $table->string('delivery')->nullable();
            $table->decimal('incoming',    12, 2)->default(0);
            $table->decimal('stok_akhir',  12, 2)->default(0);
            $table->decimal('pcs_day',     12, 4)->default(0);
            $table->decimal('strength',    10, 4)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('single_parts');
    }
};
