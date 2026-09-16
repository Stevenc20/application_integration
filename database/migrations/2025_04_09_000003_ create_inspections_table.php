<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspections', function (Blueprint $table) {
            $table->id();
            $table->string('inspection_no')->unique();          // Auto: INS-20250409-001

            // Siapa & kapan
            $table->foreignId('inspector_id')->constrained('users');
            $table->enum('shift', ['pagi', 'siang', 'malam']); // SHIFT — wajib diisi
            $table->date('inspection_date');                    // Tanggal inspeksi (bisa beda dari created_at)

            // Part & lot
            $table->foreignId('part_id')->constrained('parts');
            $table->string('lot_number');
            $table->foreignId('line_id')->constrained('production_lines');

            // Kuantitas
            $table->integer('qty_total');
            $table->integer('qty_ok')->default(0);
            $table->integer('qty_ng')->default(0);
            $table->integer('qty_rework')->default(0);
            $table->integer('qty_scrap')->default(0);

            // Hasil & defect
            $table->enum('result', ['OK', 'NG', 'PENDING'])->default('PENDING');
            $table->json('defect_types')->nullable();

            // Status alur kerja
            $table->enum('status', [
                'draft',
                'submitted',
                'qa_review',
                'verified',
                'quarantined',
                'sorting',
                'rework',
                'scrap',
                'released',
            ])->default('draft');

            // QA yang menangani
            $table->foreignId('qa_id')->nullable()->constrained('users');
            $table->foreignId('verified_by')->nullable()->constrained('users');
            $table->timestamp('verified_at')->nullable();

            // Referensi dokumen
            $table->string('dp_number')->nullable();
            $table->boolean('stop_line_triggered')->default(false);

            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['inspection_date', 'shift']);
            $table->index(['part_id', 'lot_number']);
            $table->index(['line_id', 'status']);
            $table->index('result');
            $table->index('status');
        });

        Schema::create('inspection_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_id')->constrained('inspections')->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name');
            $table->string('disk')->default('public');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspection_photos');
        Schema::dropIfExists('inspections');
    }
};