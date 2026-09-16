<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shift_submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('line_id')->nullable();
            $table->date('work_date');
            $table->tinyInteger('shift')->comment('1=Pagi, 2=Malam, 3=Non-Shift');
            $table->timestamp('submitted_at')->useCurrent();
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->timestamps();

            $table->foreign('submitted_by')->references('id')->on('users')->onDelete('set null');
            $table->unique(['line_id', 'work_date', 'shift']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_submissions');
    }
};
