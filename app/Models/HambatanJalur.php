<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HambatanJalur extends Model
{
    protected $table = 'hambatan_jalur';

    protected $fillable = [
        'downtime_id',
        'line_name',
        'mesin',
        'job_no',
        'nama_part',
        'jenis_hambatan',
        'sub_jenis',
        'problem',
        'penyebab',
        'penanggulangan',
        'pic_hambatan',
        'waktu',
        'status',
        'signature_image',
        'signed_at',
        'signed_by',
        'leader_signature_image',
        'leader_signed_at',
        'leader_signed_by',
    ];

    public function downtime()
    {
        return $this->belongsTo(Downtime::class);
    }

    public function signer()
    {
        return $this->belongsTo(User::class, 'signed_by');
    }

    public function leaderSigner()
    {
        return $this->belongsTo(User::class, 'leader_signed_by');
    }
}
