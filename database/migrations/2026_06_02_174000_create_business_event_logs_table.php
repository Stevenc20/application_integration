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
        Schema::create('business_event_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event_type')->index(); // e.g., 'created', 'updated', 'deleted'
            $table->string('entity_type')->index(); // e.g., 'Material', 'PurchaseOrder'
            $table->unsignedBigInteger('entity_id')->index();
            $table->string('user')->nullable(); // user name or id
            $table->text('payload')->nullable(); // json string of changes/data
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_event_logs');
    }
};
