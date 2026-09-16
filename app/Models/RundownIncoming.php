<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RundownIncoming extends Model
{
    protected $table = 'rundown_incomings';

    protected $fillable = [
        'sheet_date', 'no', 'job_no', 'job_no_finish', 'type_pallet', 'vendor', 'category', 'customer', 'price_pc',
        'status', 'movement', 'cycle_issue',
        'stock_awal', 'assy', 'iami', 'gkd', 'sap', 'kap', 'gmo', 'delivery', 'incoming',
        'stok_akhir', 'all_price', 'pcs_day', 'strength',
    ];

    protected $casts = [
        'stock_awal'  => 'float',
        'assy'        => 'float',
        'incoming'    => 'float',
        'stok_akhir'  => 'float',
        'pcs_day'     => 'float',
        'strength'    => 'float',
        'price_pc'    => 'float',
        'all_price'   => 'float',
        'cycle_issue' => 'integer',
    ];
}
