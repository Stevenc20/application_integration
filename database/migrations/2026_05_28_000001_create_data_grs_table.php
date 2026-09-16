<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_grs', function (Blueprint $table) {
            $table->id();
            $table->string('gr_status')->nullable()->index();
            $table->string('po_number')->nullable()->index();
            $table->string('job_number')->nullable()->index();
            $table->string('material')->nullable()->index();
            $table->string('vendor_name')->nullable()->index();
            $table->integer('qty')->default(0);
            $table->string('dn_number')->nullable()->index();
            $table->string('kanban_number')->nullable()->index();
            $table->string('gr_number_edn')->nullable()->index();
            $table->date('dn_date')->nullable();
            $table->dateTime('gr_date')->nullable();
            $table->string('gr_number_sap')->nullable()->index();
            $table->text('sap_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_grs');
    }
};
