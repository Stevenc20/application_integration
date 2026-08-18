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
        Schema::create('li_templates', function (Blueprint $table) {
            $table->id();
            $table->string('template_name')->nullable();
            $table->string('part_no')->unique();
            $table->string('part_name')->nullable();
            $table->string('type')->nullable();
            $table->string('spec_material')->nullable();
            $table->string('type_pallet')->nullable();
            $table->string('view_package')->nullable();
            $table->string('image_path')->nullable();

            // Dimension Standards
            for ($i = 1; $i <= 5; $i++) {
                $table->string("dimensi{$i}")->nullable();
                $table->string("dimensi{$i}_item")->nullable();
                $table->string("dimensi{$i}_method")->nullable();
            }

            // Appearance Standards
            for ($i = 6; $i <= 14; $i++) {
                $table->text("appearance{$i}")->nullable();
            }

            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('li_templates');
    }
};
