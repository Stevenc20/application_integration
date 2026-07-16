<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recovery_schedules', function (Blueprint $table) {
            $table->id();
            $table->date('plan_date');
            $table->string('shift_name');
            $table->string('press_name')->nullable();
            $table->enum('status', ['waiting_approval', 'approved', 'rejected', 'scheduled'])->default('waiting_approval');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users');
            $table->timestamp('rejected_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['plan_date', 'shift_name', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recovery_schedules');
    }
};
