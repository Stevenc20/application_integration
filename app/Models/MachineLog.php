<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MachineLog extends Model
{
   protected $fillable = [
        'machine_id',
        'status',
        'downtime_start',
        'downtime_end'
    ];

    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }
}
