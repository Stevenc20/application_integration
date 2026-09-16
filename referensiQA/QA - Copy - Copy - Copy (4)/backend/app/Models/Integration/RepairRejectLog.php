<?php

namespace App\Models\Integration;

use Illuminate\Database\Eloquent\Model;

class RepairRejectLog extends Model
{
    protected $connection = 'mysql_integration';
    protected $table = 'repair_reject_logs';

    // type: 'repair' | 'reject'
    // defect_name: nama cacat
    // area_problem: lokasi cacat
    // root_cause, countermeasure, qty_a, qty_b
    protected $fillable = [
        'job_master_id', 'type', 'sketch_no', 'repair_category',
        'defect_name', 'qty_a', 'qty_b', 'area_problem',
        'root_cause', 'countermeasure', 'created_by'
    ];
}

