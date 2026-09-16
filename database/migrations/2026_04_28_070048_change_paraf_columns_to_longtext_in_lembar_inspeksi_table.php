<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lembar_inspeksi', function (Blueprint $table) {
            $table->longText('paraf_gl')->nullable()->change();
            $table->longText('paraf_foreman')->nullable()->change();
            $table->longText('prepared_paraf')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('lembar_inspeksi', function (Blueprint $table) {
            $table->string('paraf_gl')->nullable()->change();
            $table->string('paraf_foreman')->nullable()->change();
            $table->string('prepared_paraf')->nullable()->change();
        });
    }
};
