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
       Schema::create('production_sessions', function (Blueprint $table) {

        $table->id();

        $table->foreignId('job_master_id')
            ->constrained('job_masters')
            ->onDelete('cascade');

        $table->date('work_date');

        $table->timestamp('start_time')->nullable();

        $table->timestamp('pause_time')->nullable();

        $table->timestamp('finish_time')->nullable();

        $table->integer('total_seconds')->default(0);

        $table->enum('status',[
            'idle',
            'running',
            'paused',   
            'finished'
        ])->default('idle');

        $table->timestamps();
});
    }

};
