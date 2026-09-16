<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class IntercomCall extends Model
{
    use HasFactory;

    protected $table = 'intercom_calls';

    protected $fillable = [
        'lembar_inspeksi_id',
        'status',
        'responder_name',
        'response_msg',
        'called_at',
        'arrived_at',
        'arrived_name',
    ];

    protected $casts = [
        'called_at'  => 'datetime',
        'arrived_at' => 'datetime',
    ];

    public function lembarInspeksi()
    {
        return $this->belongsTo(LembarInspeksi::class, 'lembar_inspeksi_id');
    }
}
