<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RundownStock extends Model
{
    protected $table = 'rundown_stocks';

    protected $fillable = [
        'no', 'job_no', 'part_number', 'sourching', 'qty_palet',
        'type_pallet', 'proses', 'source', 'customer', 'type_of_part',
        'stock_movement', 'cycle_issue', 'pcs_day', 'stock_fg', 'strength',
        'remarks', 'stock_sap', 'stock_diff', 'accuracy', 'price_pcs',
        'new_price', 'loss_gain', 'pending_gi', 'min_stock', 'max_stock',
        'stock_shortage', 'status_order',
    ];

    protected $casts = [
        'strength' => 'float',
        'stock_fg' => 'float',
        'pcs_day'  => 'float',
        'accuracy' => 'float',
    ];
}