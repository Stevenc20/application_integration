<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rundown_presses', function (Blueprint $table) {
            $table->id();
            $table->string('sheet_date'); // e.g. "04 MEI"
            $table->integer('no')->nullable();
            $table->string('job_no')->nullable();
            $table->string('tipe')->nullable();
            $table->string('vendor')->nullable();
            $table->string('update_stock')->nullable();
            $table->decimal('stock_awal', 15, 2)->default(0);
            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('incoming', 15, 2)->default(0);
            
            // Order Customer Columns
            $table->decimal('iami', 15, 2)->default(0);
            $table->decimal('spare_part', 15, 2)->default(0);
            $table->decimal('gkd', 15, 2)->default(0);
            $table->decimal('sap', 15, 2)->default(0);
            $table->decimal('kap', 15, 2)->default(0);
            $table->decimal('gmo', 15, 2)->default(0); // GMO/TMMIN/FTI
            
            $table->decimal('stok_akhir', 15, 2)->default(0);
            $table->decimal('pcs_day', 15, 4)->default(0);
            $table->decimal('strength', 15, 4)->default(0);
            $table->string('status')->nullable();
            
            $table->timestamps();
            
            $table->index(['sheet_date', 'job_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rundown_presses');
    }
};
