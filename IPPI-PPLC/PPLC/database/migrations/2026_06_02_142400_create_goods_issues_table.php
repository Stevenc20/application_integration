<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('goods_issues', function (Blueprint $table) {
            $table->id();
            $table->string('no_gi')->unique();
            $table->date('tanggal_issue');
            $table->unsignedBigInteger('storage_location_id');
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->foreign('storage_location_id')->references('id')->on('storage_locations')->onDelete('cascade');
        });

        Schema::create('goods_issue_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('goods_issue_id');
            $table->unsignedBigInteger('material_id');
            $table->double('qty');
            $table->timestamps();

            $table->foreign('goods_issue_id')->references('id')->on('goods_issues')->onDelete('cascade');
            $table->foreign('material_id')->references('id')->on('materials')->onDelete('cascade');
        });

        // Seed GI matching the screenshot
        $giId = DB::table('goods_issues')->insertGetId([
            'no_gi' => 'GI-2026-00001',
            'tanggal_issue' => '2026-06-01',
            'storage_location_id' => 3, // Gudang IRM
            'keterangan' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Add 2 sample items to the GI
        for ($i = 1; $i <= 2; $i++) {
            DB::table('goods_issue_items')->insert([
                'goods_issue_id' => $giId,
                'material_id' => $i,
                'qty' => 50 * $i,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goods_issue_items');
        Schema::dropIfExists('goods_issues');
    }
};
