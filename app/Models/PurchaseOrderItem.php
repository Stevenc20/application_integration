<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $table = 'purchase_order_items';

    protected $fillable = [
        'purchase_order_id',
        'material_id',
        'qty',
        'qty_received',
        'unit_price',
        'total_price',
        'expected_delivery_date',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function material()
    {
        return $this->belongsTo(Material::class, 'material_id');
    }

    // Accessors/Mutators for compatibility with source project database columns
    public function getQuantityAttribute()
    {
        return $this->qty;
    }

    public function setQuantityAttribute($value)
    {
        $this->qty = $value;
    }

    public function getQuantityReceivedAttribute()
    {
        return $this->qty_received;
    }

    public function setQuantityReceivedAttribute($value)
    {
        $this->qty_received = $value;
    }
}
