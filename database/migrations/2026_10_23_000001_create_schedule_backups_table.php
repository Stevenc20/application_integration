<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_backups', function (Blueprint $table) {
            $table->id();
            $table->date('plan_date')->index();
            $table->string('shift_scope', 100)->default('all')->index();
            $table->string('press_scope', 100)->default('all');
            $table->string('backup_type', 40)->default('pre_import')->index(); // initial, pre_import, manual, pre_restore, pre_clear
            $table->string('label')->nullable();
            $table->string('source_filename')->nullable();
            $table->string('checksum', 64)->nullable()->index();
            $table->unsignedInteger('plans_count')->default(0);
            $table->unsignedInteger('jobs_count')->default(0);
            $table->unsignedInteger('active_jobs_count')->default(0);
            $table->unsignedInteger('signatures_count')->default(0);
            $table->json('summary_meta')->nullable();
            $table->longText('snapshot_payload');
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('restored_from_id')->nullable()->index();
            $table->timestamp('last_restored_at')->nullable();
            $table->unsignedBigInteger('last_restored_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_backups');
    }
};
