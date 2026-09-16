<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('lembar_inspeksi', function (Blueprint $table) {
            $table->unsignedBigInteger('assigned_operator_id')->nullable()->after('foreman_id');
            $table->unsignedBigInteger('assigned_gl_id')->nullable()->after('assigned_operator_id');
            $table->unsignedBigInteger('assigned_foreman_id')->nullable()->after('assigned_gl_id');

            $table->foreign('assigned_operator_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('assigned_gl_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('assigned_foreman_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('lembar_inspeksi', function (Blueprint $table) {
            $table->dropForeign(['assigned_operator_id']);
            $table->dropForeign(['assigned_gl_id']);
            $table->dropForeign(['assigned_foreman_id']);
            $table->dropColumn(['assigned_operator_id', 'assigned_gl_id', 'assigned_foreman_id']);
        });
    }
};
