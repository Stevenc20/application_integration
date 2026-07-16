<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShiftSubmission extends Model
{
    protected $fillable = [
        'line_id',
        'work_date',
        'shift',
        'submitted_at',
        'submitted_by',
    ];

    protected $casts = [
        'work_date' => 'date',
        'submitted_at' => 'datetime',
    ];

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }
}
