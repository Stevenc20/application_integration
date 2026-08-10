<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up() {
        Schema::table('qprs', function (Blueprint $table) {
            $table->longText('actions')->nullable()->after('pencegahan');
        });
    }
};
