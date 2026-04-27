<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionProcess extends Model
{

protected $fillable = [
    'user_id',
    'production_order_number',
    'job_id',
    'process_type',
    'line',
    'machine_status',
    'shift',
    'qty_ok',
    'qty_repair',
    'qty_reject',
    'status'
];

public function job()
{
return $this->belongsTo(JobMaster::class,'job_id');
}

}