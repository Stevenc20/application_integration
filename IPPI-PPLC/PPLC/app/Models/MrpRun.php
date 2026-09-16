<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MrpRun extends Model
{
    protected $fillable = ['run_date', 'run_by', 'status', 'notes'];

    protected $casts = [
        'run_date' => 'datetime'
    ];

    public function runBy()
    {
        return $this->belongsTo(User::class, 'run_by');
    }

    public function results()
    {
        return $this->hasMany(MrpResult::class, 'mrp_run_id');
    }
}
