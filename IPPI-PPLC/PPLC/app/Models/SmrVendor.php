<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmrVendor extends Model
{
    protected $table = 'smr_vendors';

    protected $fillable = [
        'no', 'month', 'vendor', 'no_smr', 'part_name',
        'qty', 'problem', 'tanggal_keluar', 'tanggal_masuk',
        'qty_pengganti', 'status_barang',
    ];

    protected $casts = [
        'qty'            => 'integer',
        'qty_pengganti'  => 'integer',
        'tanggal_keluar' => 'date',
        'tanggal_masuk'  => 'date',
    ];
}
