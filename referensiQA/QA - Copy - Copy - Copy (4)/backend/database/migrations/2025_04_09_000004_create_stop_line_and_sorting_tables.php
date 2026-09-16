<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stop_line_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('line_id')->constrained('production_lines');
            $table->enum('action', ['stop', 'resume']);
            $table->text('reason');
            $table->foreignId('triggered_by')->constrained('users');
            $table->foreignId('released_by')->nullable()->constrained('users');
            $table->foreignId('inspection_id')->nullable()->constrained('inspections')->nullOnDelete();
            $table->timestamp('stopped_at');
            $table->timestamp('released_at')->nullable();
            $table->integer('duration_minutes')->nullable(); // Auto-hitung saat resume
            $table->timestamps();

            $table->index(['line_id', 'stopped_at']);
        });

        Schema::create('sorting_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_no')->unique();              // SORT-20250409-001
            $table->foreignId('inspection_id')->constrained('inspections');
            $table->foreignId('initiated_by')->constrained('users');
            $table->foreignId('line_id')->constrained('production_lines');
            $table->enum('shift', ['pagi', 'siang', 'malam']);
            $table->date('session_date');

            $table->integer('qty_to_sort');
            $table->integer('qty_ok')->default(0);
            $table->integer('qty_ng')->default(0);
            $table->integer('qty_scrap')->default(0);

            $table->enum('status', [
                'open',
                'in_progress',
                'completed',
                'cancelled',
            ])->default('open');

            $table->foreignId('completed_by')->nullable()->constrained('users');
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['session_date', 'shift']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sorting_sessions');
        Schema::dropIfExists('stop_line_logs');
    }
};