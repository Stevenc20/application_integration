<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('schedule_revisions', 'section_scope')) {
            Schema::table('schedule_revisions', function (Blueprint $table) {
                $table->json('section_scope')->nullable()->after('action');
            });
        }

        if (! Schema::hasColumn('schedule_revisions', 'source_filename')) {
            Schema::table('schedule_revisions', function (Blueprint $table) {
                $table->string('source_filename')->nullable()->after('section_scope');
            });
        }

        Schema::table('schedule_revisions', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('schedule_revisions', function (Blueprint $table) {
            $cols = array_values(array_filter(
                ['section_scope', 'source_filename'],
                fn (string $col) => Schema::hasColumn('schedule_revisions', $col)
            ));
            if (! empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
