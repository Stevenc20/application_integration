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
        Schema::create('qpr_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qpr_id')->constrained()->cascadeOnDelete();
            $table->text('action')->nullable();
            $table->date('schedule')->nullable();
            $table->string('status')->nullable();
            $table->string('pic')->nullable();

$table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qpr_actions');
    }
};
