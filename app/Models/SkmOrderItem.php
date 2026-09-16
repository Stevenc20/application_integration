<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkmOrderItem extends Model
{
    protected $fillable = [
        'skm_order_id', 'material_id', 'vendor_id',
        'kanban_qty', 'num_cards', 'order_qty',
        'expected_delivery_date', 'storage_location_id',
        'current_stock', 'min_stock', 'notes',
    ];

    protected $casts = [
        'kanban_qty'             => 'decimal:3',
        'order_qty'              => 'decimal:3',
        'current_stock'          => 'decimal:3',
        'min_stock'              => 'decimal:3',
        'expected_delivery_date' => 'date',
    ];

    public function skmOrder() { return $this->belongsTo(SkmOrder::class); }
    public function material() { return $this->belongsTo(Material::class); }
    public function vendor() { return $this->belongsTo(Vendor::class); }
    public function storageLocation() { return $this->belongsTo(StorageLocation::class); }
}
