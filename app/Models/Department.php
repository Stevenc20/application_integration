<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $fillable = ['department_name'];

    public function sections()
    {
        return $this->hasMany(Section::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
