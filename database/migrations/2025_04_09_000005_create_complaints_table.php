<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->string('complaint_no')->unique();            // CMP-2025-001

            // Pelapor — bisa customer login atau QA input manual
            $table->foreignId('reported_by')->constrained('users');
            $table->string('customer_name');
            $table->string('customer_contact')->nullable();

            // Detail part & lot yang dikomplain
            $table->foreignId('part_id')->constrained('parts');
            $table->string('lot_number')->nullable();
            $table->string('delivery_no')->nullable();          // Nomor surat jalan / DO
            $table->date('delivery_date')->nullable();
            $table->string('defect_category');
            $table->text('description');
            $table->integer('qty_complained')->default(0);
            $table->integer('qty_returned')->default(0);

            // Prioritas & status
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('status', [
                'open',
                'investigation',
                'capa',
                'verification',
                'closed',
                're_open',
            ])->default('open');

            // Penanganan
            $table->foreignId('assigned_qa_id')->nullable()->constrained('users');
            $table->text('root_cause')->nullable();
            $table->text('containment_action')->nullable();
            $table->date('target_close_date')->nullable();
            $table->date('actual_close_date')->nullable();

            // Feedback ke customer
            $table->boolean('feedback_sent')->default(false);
            $table->timestamp('feedback_sent_at')->nullable();
            $table->foreignId('feedback_sent_by')->nullable()->constrained('users');

            // Link ke inspeksi terkait (untuk traceability)
            $table->foreignId('inspection_id')->nullable()->constrained('inspections')->nullOnDelete();

            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'priority']);
            $table->index('part_id');
            $table->index('assigned_qa_id');
        });

        // Setiap langkah 8D tersimpan sebagai satu record di sini
        Schema::create('complaint_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('complaint_id')->constrained('complaints')->cascadeOnDelete();
            $table->enum('d_step', [
                'D1', // Pembentukan tim
                'D2', // Deskripsi masalah
                'D3', // Containment action
                'D4', // Root cause analysis
                'D5', // Permanent corrective action
                'D6', // Implementasi CAPA
                'D7', // Preventive action
                'D8', // Closure & feedback
            ])->nullable();
            $table->string('action_type');                      // note / containment / capa / feedback / etc
            $table->text('description');
            $table->foreignId('created_by')->constrained('users');
            $table->json('attachments')->nullable();             // Array path file
            $table->timestamps();

            $table->index(['complaint_id', 'd_step']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaint_actions');
        Schema::dropIfExists('complaints');
    }
};