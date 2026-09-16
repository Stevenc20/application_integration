<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('intercom_calls', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lembar_inspeksi_id');
            $table->string('status')->nullable(); // 'calling_gl', 'calling_foreman', 'answered', 'declined', 'completed'
            $table->string('responder_name')->nullable();
            $table->string('response_msg')->nullable();
            $table->timestamp('called_at')->nullable();
            $table->timestamps();

            $table->foreign('lembar_inspeksi_id')->references('id')->on('lembar_inspeksi')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('intercom_calls');
    }
};
