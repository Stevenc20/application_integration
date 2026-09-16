<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pallet_mutations', function (Blueprint $table) {
            $table->id();
            $table->integer('no')->default(0);
            $table->date('month')->nullable()->index();
            $table->string('vendor')->nullable()->index();
            $table->string('type_pallet')->nullable()->index();
            $table->string('type')->nullable();
            $table->integer('initial_stock')->default(0);
            $table->integer('pallet_in')->default(0);
            $table->integer('pallet_out')->default(0);
            $table->integer('final_stock')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pallet_mutations');
    }
};
