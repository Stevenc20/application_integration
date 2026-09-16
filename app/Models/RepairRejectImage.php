<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepairRejectImage extends Model
{
    protected $fillable = ['repair_reject_log_id', 'image_path', 'image_type'];

    public function repairRejectLog()
    {
        return $this->belongsTo(RepairRejectLog::class);
    }
}
