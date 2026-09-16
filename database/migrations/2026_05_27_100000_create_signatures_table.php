<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signatures', function (Blueprint $table) {
            $table->id();
            $table->string('role');
            $table->longText('signature_data');
            $table->timestamps();
            $table->unique('role');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signatures');
    }
};
