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
        Schema::create('master_shifts', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->time('planned_start_time');
            $table->time('planned_end_time');
            $table->timestamps();
        });

        // Insert initial data based on typical values
        \Illuminate\Support\Facades\DB::table('master_shifts')->insert([
            ['name' => 'Shift Pagi', 'planned_start_time' => '07:30:00', 'planned_end_time' => '21:00:00', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Shift Malam', 'planned_start_time' => '21:00:00', 'planned_end_time' => '07:30:00', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_shifts');
    }
};
