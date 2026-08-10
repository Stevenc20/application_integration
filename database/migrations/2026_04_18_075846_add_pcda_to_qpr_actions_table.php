<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up() {
    Schema::table('qpr_actions', function (Blueprint $table) {
        $table->string('pcda', 10)->nullable()->after('tgl_verif_3');
    });
}
};
