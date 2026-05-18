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
        Schema::table('production_plans', function (Blueprint $table) {
            $table->string('shift_name')->nullable()->after('status');
            $table->string('press_name')->nullable()->after('shift_name');
            $table->string('hari')->nullable()->after('press_name');
            $table->string('tgl')->nullable()->after('hari');
            $table->string('jam')->nullable()->after('tgl');
            $table->string('revisi')->nullable()->after('jam');
            
            $table->integer('row_no')->nullable()->after('revisi');
            $table->string('row_type')->default('job')->after('row_no');
            
            $table->string('job_master')->nullable()->after('row_type');
            $table->string('type_plt')->nullable()->after('job_master');
            $table->float('qty_plt')->nullable()->after('type_plt');
            $table->float('keb_mtl')->nullable()->after('qty_plt');
            $table->float('total_plt')->nullable()->after('keb_mtl');
            
            $table->string('job_no')->nullable()->after('total_plt');
            $table->string('each_part')->nullable()->after('job_no');
            
            $table->float('plan')->nullable()->after('each_part');
            $table->float('ok')->default(0)->after('plan');
            $table->float('repair')->default(0)->after('ok');
            $table->float('reject')->default(0)->after('repair');
            $table->integer('total_mesin')->default(1)->after('reject');
            
            $table->float('ct_detik')->nullable()->after('total_mesin');
            $table->float('process_time')->nullable()->after('ct_detik');
            $table->float('reg_active')->default(0)->after('process_time');
            $table->float('dct')->default(0)->after('reg_active');
            $table->float('mct')->default(0)->after('dct');
            $table->float('plan_dct')->nullable()->after('mct');
            $table->float('tpt')->nullable()->after('plan_dct');
            $table->float('gsph_item')->nullable()->after('tpt');
            
            $table->string('start_time')->nullable()->after('gsph_item');
            $table->string('finish_time')->nullable()->after('start_time');
            $table->string('act_start')->nullable()->after('finish_time');
            $table->string('act_finish')->nullable()->after('act_start');
            
            $table->text('keterangan')->nullable()->after('act_finish');
            
            $table->integer('a1')->nullable()->after('keterangan');
            $table->integer('a2')->nullable()->after('a1');
            $table->integer('a3')->nullable()->after('a2');
            $table->integer('a4')->nullable()->after('a3');
            
            $table->integer('dt_menit')->default(0)->after('a4');
            $table->float('total_pcs')->default(0)->after('dt_menit');
            $table->float('tpt_total')->nullable()->after('total_pcs');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_plans', function (Blueprint $table) {
            $table->dropColumn([
                'shift_name', 'press_name', 'hari', 'tgl', 'jam', 'revisi',
                'row_no', 'row_type', 'job_master', 'type_plt', 'qty_plt',
                'keb_mtl', 'total_plt', 'job_no', 'each_part', 'plan',
                'ok', 'repair', 'reject', 'total_mesin', 'ct_detik',
                'process_time', 'reg_active', 'dct', 'mct', 'plan_dct',
                'tpt', 'gsph_item', 'start_time', 'finish_time',
                'act_start', 'act_finish', 'keterangan', 'a1', 'a2',
                'a3', 'a4', 'dt_menit', 'total_pcs', 'tpt_total'
            ]);
        });
    }
};
