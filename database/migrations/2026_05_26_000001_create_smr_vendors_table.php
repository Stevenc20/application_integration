<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('smr_vendors', function (Blueprint $table) {
            $table->id();
            $table->integer('no')->default(0);
            $table->string('month')->nullable()->index();
            $table->string('vendor')->nullable()->index();
            $table->string('no_smr')->nullable()->index();
            $table->string('part_name')->nullable()->index();
            $table->integer('qty')->default(0);
            $table->text('problem')->nullable();
            $table->date('tanggal_keluar')->nullable();
            $table->date('tanggal_masuk')->nullable();
            $table->bigInteger('qty_pengganti')->default(0);
            $table->string('status_barang')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('smr_vendors');
    }
};
