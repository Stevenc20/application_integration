<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterStamping extends Model
{
    protected $table = 'master_stampings';

    protected $fillable = [
        'proses_line',
        'mach',
        'job_no',
        'job_master',
        'part_no',
        'irm_number',
        'part_name',
        'qty_unit',
        'total',
        'type_pallet',
        'qty_pallet',
        'ct_detik',
        'dct',
        'reg_active',
        'mct',
        'tpt',
        'customer',
        'remarks',
        'is_shift_pagi',
        'is_shift_malam',
    ];

    protected $casts = [
        'qty_unit'       => 'float',
        'total'          => 'float',
        'qty_pallet'     => 'float',
        'ct_detik'       => 'float',
        'dct'            => 'float',
        'reg_active'     => 'float',
        'mct'            => 'float',
        'tpt'            => 'float',
        'is_shift_pagi'  => 'boolean',
        'is_shift_malam' => 'boolean',
    ];
}
