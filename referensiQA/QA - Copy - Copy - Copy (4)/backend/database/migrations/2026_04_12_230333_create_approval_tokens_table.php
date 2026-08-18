<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qpr_id')->constrained('qprs')->onDelete('cascade');
            $table->string('token', 64)->unique();
            $table->string('role');        // "Dibuat oleh", "Diperiksa", dll
            $table->string('nama')->nullable();
            $table->text('signature')->nullable();
            $table->boolean('is_used')->default(false);
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_tokens');
    }
};
