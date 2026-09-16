<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkmDemand extends Model
{
    protected $fillable = [
        'material_id',
        'demand_qty',
        'working_days',
        'period',
        'notes',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'demand_qty'   => 'decimal:3',
        'working_days' => 'integer',
        'is_active'    => 'boolean',
    ];

    public function material() { return $this->belongsTo(Material::class); }
    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }
}
