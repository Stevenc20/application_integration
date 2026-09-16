<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PalletMutation extends Model
{
    protected $table = 'pallet_mutations';

    protected $fillable = [
        'no', 'month', 'vendor', 'type_pallet', 'type',
        'initial_stock', 'pallet_in', 'pallet_out', 'final_stock',
    ];

    protected $casts = [
        'month'         => 'date',
        'initial_stock' => 'integer',
        'pallet_in'     => 'integer',
        'pallet_out'    => 'integer',
        'final_stock'   => 'integer',
    ];
}
