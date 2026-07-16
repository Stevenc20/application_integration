<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduleStamping extends Model
{
    protected $table = 'schedule_stampings';

    protected $fillable = [
        'upload_date', 'press_name', 'shift_name', 'hari', 'tgl', 'jam', 'revisi',
        'row_no', 'row_type', 'sort_order',
        'job_master', 'type_plt', 'qty_plt', 'keb_mtl', 'total_plt',
        'job_no', 'each_part',
        'plan', 'ok', 'repair', 'reject', 'total_mesin',
        'ct_detik', 'process_time', 'reg_active', 'dct', 'mct', 'plan_dct',
        'tpt', 'gsph_item',
        'start_time', 'finish_time', 'act_start', 'act_finish', 'keterangan',
        'a1', 'a2', 'a3', 'a4', 'dt_menit',
        'total_pcs', 'tpt_total',
    ];

    protected $casts = [
        'qty_plt'      => 'float',
        'keb_mtl'      => 'float',
        'total_plt'    => 'float',
        'plan'         => 'float',
        'ok'           => 'float',
        'repair'       => 'float',
        'reject'       => 'float',
        'ct_detik'     => 'float',
        'process_time' => 'float',
        'reg_active'   => 'float',
        'dct'          => 'float',
        'mct'          => 'float',
        'plan_dct'     => 'float',
        'tpt'          => 'float',
        'gsph_item'    => 'float',
        'tpt_total'    => 'float',
    ];
}