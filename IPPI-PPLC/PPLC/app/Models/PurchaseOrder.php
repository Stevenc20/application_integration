<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $table = 'purchase_orders';

    protected $fillable = [
        'no_po',
        'vendor_id',
        'tanggal_order',
        'estimasi_terima',
        'catatan',
        'status',
        'storage_location_id',
        'total_amount',
        'created_by',
        'approved_at',
        'approved_by',
        'skm_order_id',
    ];

    protected $casts = [
        'tanggal_order' => 'date:Y-m-d',
        'estimasi_terima' => 'date:Y-m-d',
        'approved_at' => 'datetime',
        'total_amount' => 'decimal:2',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function storageLocation()
    {
        return $this->belongsTo(StorageLocation::class, 'storage_location_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class, 'purchase_order_id');
    }

    public function goodsReceipts()
    {
        return $this->hasMany(GoodsReceipt::class, 'purchase_order_id');
    }

    // Accessors & Mutators for compatibility
    public function getPoNumberAttribute()
    {
        return $this->attributes['no_po'] ?? null;
    }

    public function setPoNumberAttribute($value)
    {
        $this->attributes['no_po'] = $value;
    }

    public function getOrderDateAttribute()
    {
        $val = $this->attributes['tanggal_order'] ?? null;
        return $val ? \Carbon\Carbon::parse($val) : null;
    }

    public function setOrderDateAttribute($value)
    {
        $this->attributes['tanggal_order'] = $value;
    }

    public function getExpectedDeliveryDateAttribute()
    {
        $val = $this->attributes['estimasi_terima'] ?? null;
        return $val ? \Carbon\Carbon::parse($val) : null;
    }

    public function setExpectedDeliveryDateAttribute($value)
    {
        $this->attributes['estimasi_terima'] = $value;
    }

    public function getNotesAttribute()
    {
        return $this->attributes['catatan'] ?? null;
    }

    public function setNotesAttribute($value)
    {
        $this->attributes['catatan'] = $value;
    }

    public function getStatusAttribute($value)
    {
        if (!$value) return null;
        return strtolower(str_replace(' ', '_', $value));
    }

    public static function generateNumber(): string
    {
        $prefix = 'EDN' . date('Y') . '-';
        $last = static::where('no_po', 'like', $prefix . '%')->orderBy('id', 'desc')->first();
        $next = $last ? (int) substr($last->no_po, -5) + 1 : 1;
        return $prefix . str_pad($next, 5, '0', STR_PAD_LEFT);
    }
}
