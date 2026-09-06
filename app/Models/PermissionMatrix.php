<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PermissionMatrix extends Model
{
    protected $fillable = [
        'position_id',
        'section_id',
        'feature_id',
        'can_view',
        'can_create',
        'can_edit',
        'can_delete',
        'can_approve',
        'can_export',
    ];

    protected $casts = [
        'can_view' => 'boolean',
        'can_create' => 'boolean',
        'can_edit' => 'boolean',
        'can_delete' => 'boolean',
        'can_approve' => 'boolean',
        'can_export' => 'boolean',
    ];

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function feature()
    {
        return $this->belongsTo(Feature::class);
    }
}
