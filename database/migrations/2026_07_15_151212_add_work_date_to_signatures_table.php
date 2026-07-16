<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('signatures', function (Blueprint $table) {
            $table->date('work_date')->after('role')->nullable();
        });

        // Drop old unique on role alone, add composite unique on (role, work_date)
        Schema::table('signatures', function (Blueprint $table) {
            $table->dropUnique(['role']);
            $table->unique(['role', 'work_date']);
        });
    }

    public function down(): void
    {
        Schema::table('signatures', function (Blueprint $table) {
            $table->dropUnique(['role', 'work_date']);
            $table->unique(['role']);
            $table->dropColumn('work_date');
        });
    }
};
