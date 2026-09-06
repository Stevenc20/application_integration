<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permission_matrices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('position_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('section_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('feature_id')->constrained()->cascadeOnDelete();
            
            $table->boolean('can_view')->default(false);
            $table->boolean('can_create')->default(false);
            $table->boolean('can_edit')->default(false);
            $table->boolean('can_delete')->default(false);
            $table->boolean('can_approve')->default(false);
            $table->boolean('can_export')->default(false);

            $table->unique(['position_id', 'section_id', 'feature_id'], 'pos_sec_feat_unique');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_matrices');
    }
};
