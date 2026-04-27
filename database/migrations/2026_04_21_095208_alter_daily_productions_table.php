<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_productions', function (Blueprint $table) {

            $table->string('line')->nullable()->after('work_date');
            $table->string('shift')->nullable()->after('line');

            $table->integer('target_qty')->default(0)->after('shift');

            $table->integer('runtime_seconds')->default(0)->after('repair_qty');
            $table->integer('downtime_seconds')->default(0)->after('runtime_seconds');

            $table->decimal('efficiency',5,2)->default(0)->after('downtime_seconds');

            $table->unsignedBigInteger('saved_by')->nullable()->after('remarks');

            $table->string('status')->default('open')->after('saved_by');
        });
    }

    public function down(): void
    {
        Schema::table('daily_productions', function (Blueprint $table) {

            $table->dropColumn([
                'line',
                'shift',
                'target_qty',
                'runtime_seconds',
                'downtime_seconds',
                'efficiency',
                'saved_by',
                'status'
            ]);
        });
    }
};