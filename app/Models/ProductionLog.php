<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionLog extends Model
{
    protected $fillable = ['job_master_id', 'ok_qty', 'repair_qty', 'reject_qty', 'created_at', 'updated_at'];

    public function jobMaster()
    {
        return $this->belongsTo(JobMaster::class);
    }
}
