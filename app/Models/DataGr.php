<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataGr extends Model
{
    protected $table = 'data_grs';

    protected $fillable = [
        'gr_status', 'po_number', 'job_number', 'material', 'vendor_name',
        'qty', 'dn_number', 'kanban_number', 'gr_number_edn', 'dn_date',
        'gr_date', 'gr_number_sap', 'sap_message',
    ];

    protected $casts = [
        'qty'     => 'integer',
        'dn_date' => 'date',
        'gr_date' => 'datetime',
    ];
}
