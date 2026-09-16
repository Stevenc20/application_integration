<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lembar_inspeksi', function (Blueprint $table) {
            $table->id();

            // ── Header Part ──
            $table->string('no_form')->nullable();          // FISM-PRO-02-35-01
            $table->string('job_no')->nullable();
            $table->string('part_name')->nullable();
            $table->string('part_no')->nullable();
            $table->string('type')->nullable();
            $table->string('spec_material')->nullable();
            $table->string('type_pallet')->nullable();
            $table->string('lokasi')->nullable();
            $table->string('view_package')->nullable();
            $table->string('judgement')->nullable();        // OK / NG
            $table->string('image_path')->nullable();       // sketch/foto part

            // ── Standar (template) ──
            // Dimensi 1-5
            $table->string('dimensi1')->nullable();
            $table->string('dimensi2')->nullable();
            $table->string('dimensi3')->nullable();
            $table->string('dimensi4')->nullable();
            $table->string('dimensi5')->nullable();
            // Appearance 6-12
            $table->string('appearance6')->nullable();
            $table->string('appearance7')->nullable();
            $table->string('appearance8')->nullable();
            $table->string('appearance9')->nullable();
            $table->string('appearance10')->nullable();
            $table->string('appearance11')->nullable();     // jumlah hole standar
            $table->string('appearance12')->nullable();

            // ── Hasil Inspeksi ──
            $table->integer('max_sample')->default(120);

            // Dimensi hasil ukur sample 1,2,3
            $table->string('dimensi1_sample_1')->nullable();
            $table->string('dimensi1_sample_2')->nullable();
            $table->string('dimensi1_sample_3')->nullable();
            $table->string('dimensi2_sample_1')->nullable();
            $table->string('dimensi2_sample_2')->nullable();
            $table->string('dimensi2_sample_3')->nullable();
            $table->string('dimensi3_sample_1')->nullable();
            $table->string('dimensi3_sample_2')->nullable();
            $table->string('dimensi3_sample_3')->nullable();
            $table->string('dimensi4_sample_1')->nullable();
            $table->string('dimensi4_sample_2')->nullable();
            $table->string('dimensi4_sample_3')->nullable();
            $table->string('dimensi5_sample_1')->nullable();
            $table->string('dimensi5_sample_2')->nullable();
            $table->string('dimensi5_sample_3')->nullable();

            // Appearance hasil inspeksi — disimpan JSON per row
            // Format: {"1": "✓", "2": "✓", "3": "△", "10": "✓", ...}
            $table->json('appearance6_results')->nullable();
            $table->json('appearance7_results')->nullable();
            $table->json('appearance8_results')->nullable();
            $table->json('appearance9_results')->nullable();
            $table->json('appearance10_results')->nullable();
            $table->json('appearance11_results')->nullable();
            $table->json('appearance12_results')->nullable();

            // Detail NG per sample — disimpan JSON
            // Format: [{"sample": 3, "row": 7, "proses": "OP10", "problem": [...], "penyebab": [...]}]
            $table->json('ng_details')->nullable();

            // Coil number per sample — JSON
            $table->json('coil_numbers')->nullable();

            // ── Footer ──
            $table->string('qg_judgement')->nullable();     // OK / NG
            $table->string('qg_name')->nullable();
            $table->date('tgl_bulan')->nullable();
            $table->string('shift')->nullable();
            $table->integer('total_produksi')->default(0);
            $table->integer('repair')->default(0);
            $table->integer('reject')->default(0);
            $table->text('catatan')->nullable();

            // ── TTD ──
            $table->string('paraf_gl')->nullable();         // base64 / path
            $table->string('paraf_foreman')->nullable();
            $table->timestamp('gl_signed_at')->nullable();
            $table->timestamp('foreman_signed_at')->nullable();
            $table->string('gl_name')->nullable();
            $table->string('frm_name')->nullable();

            // ── Relasi & Status ──
            $table->unsignedBigInteger('created_by')->nullable();   // GL yang buat
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->unsignedBigInteger('foreman_id')->nullable();    // Foreman yang TTD
            $table->foreign('foreman_id')->references('id')->on('users')->nullOnDelete();

            // Status: draft → submitted → waiting_foreman → approved / revisi
            $table->string('status')->default('draft');

            // Sudah di-generate QPR?
            $table->boolean('qpr_generated')->default(false);
            $table->unsignedBigInteger('qpr_id')->nullable();       // QPR yang di-generate

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('created_by');
            $table->index('job_no');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lembar_inspeksi');
    }
};