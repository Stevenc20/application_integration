<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionOrderComponent extends Model
{
    protected $fillable = [
        'production_order_id',
        'material_id',
        'quantity_required',
        'quantity_issued',
        'storage_location_id'
    ];

    protected $casts = [
        'quantity_required' => 'decimal:3',
        'quantity_issued' => 'decimal:3'
    ];

    public function productionOrder()
    {
        return $this->belongsTo(ProductionOrder::class, 'production_order_id');
    }

    public function material()
    {
        return $this->belongsTo(Material::class, 'material_id');
    }

    public function storageLocation()
    {
        return $this->belongsTo(StorageLocation::class, 'storage_location_id');
    }
}
