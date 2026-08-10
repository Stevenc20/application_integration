<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lembar_inspeksi', function (Blueprint $table) {
            $table->longText('paraf_qc')->nullable()->after('paraf_foreman');
            $table->string('qc_name')->nullable()->after('frm_name');
            $table->timestamp('qc_signed_at')->nullable()->after('foreman_signed_at');
            $table->longText('paraf_gl_bottom')->nullable()->after('paraf_qc');
            $table->longText('paraf_foreman_bottom')->nullable()->after('paraf_gl_bottom');
        });
    }

    public function down(): void
    {
        Schema::table('lembar_inspeksi', function (Blueprint $table) {
            $table->dropColumn(['paraf_qc', 'qc_name', 'qc_signed_at', 'paraf_gl_bottom', 'paraf_foreman_bottom']);
        });
    }
};
