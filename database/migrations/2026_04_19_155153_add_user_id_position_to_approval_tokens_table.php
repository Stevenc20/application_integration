<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up() {
        Schema::table('approval_tokens', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null')->after('qpr_id');
            $table->string('position')->nullable()->after('role'); // operator, foreman, seksi, kasie_qa
        });
    }
};
