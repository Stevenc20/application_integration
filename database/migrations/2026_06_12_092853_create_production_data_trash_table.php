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
        Schema::create('production_data_trash', function (Blueprint $table) {
            $table->id();
            $table->string('original_table');
            $table->bigInteger('original_id');
            $table->json('data');
            $table->timestamp('trashed_at')->nullable();
            $table->string('trashed_by')->nullable()->comment('Command name or user identifier');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();

            $table->index('original_table');
            $table->index('expires_at');
            $table->index('trashed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_data_trash');
    }
};
