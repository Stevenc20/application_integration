<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MrpDemand extends Model
{
    protected $fillable = ['material_id', 'order_quantity', 'customer_name', 'notes', 'is_active'];

    protected $casts = [
        'order_quantity' => 'decimal:3',
        'is_active'      => 'boolean',
    ];

    public function material()
    {
        return $this->belongsTo(Material::class, 'material_id');
    }
}
