<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_revisions', function (Blueprint $table) {
            $table->id();
            $table->date('plan_date');
            $table->string('shift_name');
            $table->string('action');
            $table->json('snapshot_before')->nullable();
            $table->json('snapshot_after')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_revisions');
    }
};
