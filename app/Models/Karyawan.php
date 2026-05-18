<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Karyawan extends Model
{
    protected $table = 'karyawans';
    protected $primaryKey = 'id_karyawan';

    protected $fillable = [
        'nama_karyawan',
        'nrp_karyawan',
        'jabatan',
    ];
}
