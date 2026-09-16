<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DepartmentPositionFeature extends Model
{
    protected $table = 'department_position_feature';

    protected $fillable = [
        'department_id',
        'position_id',
        'feature_id',
        'enabled',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
        ];
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    public function feature()
    {
        return $this->belongsTo(Feature::class);
    }
}
