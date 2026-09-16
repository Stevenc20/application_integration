<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaterialStock extends Model
{
    use HasFactory;

    protected $table = 'material_stocks';

    protected $fillable = [
        'material_id',
        'storage_location_id',
        'qty',
        'qty_vendor',
        'quantity',
    ];

    public function material()
    {
        return $this->belongsTo(Material::class, 'material_id');
    }

    public function storageLocation()
    {
        return $this->belongsTo(StorageLocation::class, 'storage_location_id');
    }

    // Accessors/Mutators for compatibility with source project 'stocks' (quantity)
    public function getQuantityAttribute()
    {
        return $this->qty;
    }

    public function setQuantityAttribute($value)
    {
        $this->qty = $value;
    }
}
