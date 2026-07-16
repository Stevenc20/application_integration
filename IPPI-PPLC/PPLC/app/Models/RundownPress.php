<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RundownPress extends Model
{
    protected $table = 'rundown_presses';

    protected $fillable = [
        'sheet_date', 'no', 'job_no', 'tipe', 'vendor', 'update_stock',
        'stock_awal', 'price', 'incoming',
        'iami', 'spare_part', 'gkd', 'sap', 'kap', 'gmo',
        'plan_day', 'plan_night', 'actual_prod',
        'stok_akhir', 'pcs_day', 'strength', 'status'
    ];

    protected $casts = [
        'stock_awal' => 'float',
        'price'      => 'float',
        'incoming'   => 'float',
        'iami'       => 'float',
        'spare_part' => 'float',
        'gkd'        => 'float',
        'sap'        => 'float',
        'kap'        => 'float',
        'gmo'        => 'float',
        'plan_day'   => 'float',
        'plan_night' => 'float',
        'actual_prod'=> 'float',
        'stok_akhir' => 'float',
        'pcs_day'    => 'float',
        'strength'   => 'float',
    ];
}
