<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\MachineLog;

class Machine extends Model
{
    protected $fillable = ['name','line'];

    public function logs()
    {
        return $this->hasMany(MachineLog::class);
    }
}