<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BomItem extends Model
{
    protected $fillable = ['bom_id', 'material_id', 'quantity', 'unit', 'notes'];

    protected $casts = [
        'quantity' => 'decimal:3'
    ];

    public function bom()
    {
        return $this->belongsTo(Bom::class, 'bom_id');
    }

    public function material()
    {
        return $this->belongsTo(Material::class, 'material_id');
    }
}
