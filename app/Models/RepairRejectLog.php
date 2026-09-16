<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepairRejectLog extends Model
{
    protected $fillable = [
        'job_master_id',
        'type',
        'sketch_no',
        'repair_category',
        'defect_name',
        'qty_a',
        'qty_b',
        'pcs_number',
        'area_problem',
        'root_cause',
        'countermeasure',
        'created_by'
    ];

    public function jobMaster()
    {
        return $this->belongsTo(JobMaster::class, 'job_master_id');
    }

    public function images()
    {
        return $this->hasMany(RepairRejectImage::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
