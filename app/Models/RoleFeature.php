<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoleFeature extends Model
{
    protected $table = 'role_feature';

    protected $fillable = [
        'role',
        'feature_id',
        'enabled',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
        ];
    }

    public function feature()
    {
        return $this->belongsTo(Feature::class);
    }
}
