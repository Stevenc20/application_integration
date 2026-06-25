<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE recovery_items MODIFY COLUMN status ENUM('pending','approved','rejected','scheduled','completed') DEFAULT 'pending'");
        }

        Schema::table('recovery_items', function (Blueprint $table) {
            $table->date('source_date')->nullable();
            $table->string('source_shift')->nullable();
            $table->float('actual_qty')->default(0);
            $table->float('recovery_qty')->default(0);
            $table->float('duration_minutes')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users');
            $table->text('rejection_notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('recovery_items', function (Blueprint $table) {
            $table->dropColumn([
                'source_date',
                'source_shift',
                'actual_qty',
                'recovery_qty',
                'duration_minutes',
                'queued_at',
                'rejected_at',
                'rejected_by',
                'rejection_notes',
            ]);
        });

        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE recovery_items MODIFY COLUMN status ENUM('pending','approved','rejected','scheduled') DEFAULT 'pending'");
        }
    }
};
