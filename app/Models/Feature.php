<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feature extends Model
{
    protected $fillable = [
        'feature_code',
        'feature_name',
        'group_name',
        'description',
    ];

    public function roleFeatures()
    {
        return $this->hasMany(RoleFeature::class);
    }
}
