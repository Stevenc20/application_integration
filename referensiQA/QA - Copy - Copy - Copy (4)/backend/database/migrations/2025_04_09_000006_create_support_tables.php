<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Notifikasi in-app (Laravel Notification → database driver)
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');                             // Class notifikasi
            $table->morphs('notifiable');                       // notifiable_type + notifiable_id
            $table->text('data');                               // JSON payload
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['notifiable_id', 'notifiable_type', 'read_at']);
        });

        // Audit trail — semua perubahan data penting tercatat
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');                           // created / updated / deleted / login / etc
            $table->string('model_type')->nullable();           // App\Models\Inspection
            $table->unsignedBigInteger('model_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('url')->nullable();
            $table->timestamps();

            $table->index(['model_type', 'model_id']);
            $table->index(['user_id', 'created_at']);
            $table->index('action');
        });

        // Log integrasi ke sistem lain (MES, ERP masa depan, dll)
        // Dibuat fleksibel karena "belum tahu sistem apa" — pakai JSON
        Schema::create('integration_logs', function (Blueprint $table) {
            $table->id();
            $table->string('system_name');                      // MES / ERP / EMAIL / dll
            $table->string('direction');                        // inbound / outbound
            $table->string('event_type');                       // inspection.created / stopline.triggered / dll
            $table->string('endpoint')->nullable();             // URL endpoint tujuan
            $table->string('method', 10)->nullable();           // GET / POST / PUT / WEBHOOK
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->integer('http_status')->nullable();
            $table->boolean('success')->default(false);
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('next_retry_at')->nullable();
            $table->timestamps();

            $table->index(['system_name', 'success']);
            $table->index(['event_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_logs');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('notifications');
    }
};