<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qprs', function (Blueprint $table) {
            $table->string('analisa_man_ket')->nullable()->after('analisa_man');
            $table->string('analisa_method_ket')->nullable()->after('analisa_method');
            $table->string('analisa_machine_ket')->nullable()->after('analisa_machine');
            $table->string('analisa_material_ket')->nullable()->after('analisa_material');
            $table->string('analisa_environment_ket')->nullable()->after('analisa_environment');
        });
    }

    public function down(): void
    {
        Schema::table('qprs', function (Blueprint $table) {
            $table->dropColumn([
                'analisa_man_ket', 'analisa_method_ket', 'analisa_machine_ket',
                'analisa_material_ket', 'analisa_environment_ket'
            ]);
        });
    }
};
