<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShiftSubmission extends Model
{
    protected $fillable = [
        'line_id',
        'work_date',
        'shift',
        'shift_master_id',
        'submitted_at',
        'submitted_by',
        'comment',
        'cancelled_at',
        'cancelled_by',
        'cancel_count',
    ];

    protected $casts = [
        'work_date' => 'date',
        'submitted_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function line()
    {
        return $this->belongsTo(LineMaster::class, 'line_id');
    }
}
