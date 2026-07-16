<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionLog extends Model
{
    protected $fillable = ['job_master_id', 'ok_qty', 'repair_qty', 'reject_qty'];

    public function jobMaster()
    {
        return $this->belongsTo(JobMaster::class);
    }
}
