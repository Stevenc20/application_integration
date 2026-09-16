<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    use HasFactory;

    protected $table = 'materials';

    protected $fillable = [
        'kode',
        'nama',
        'tipe',
        'uom',
        'qty_case',
        'stok',
        'min_stok',
        'status',
        'vendor_id',
        'process_vendor_id',
        'standard_price',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function processVendor()
    {
        return $this->belongsTo(Vendor::class, 'process_vendor_id');
    }

    // Relations
    public function stocks()
    {
        return $this->hasMany(MaterialStock::class, 'material_id');
    }

    public function boms()
    {
        return $this->hasMany(Bom::class, 'material_id');
    }

    public function bomItems()
    {
        return $this->hasMany(BomItem::class, 'material_id');
    }

    public function productionOrders()
    {
        return $this->hasMany(ProductionOrder::class, 'material_id');
    }

    // Accessors/Mutators for compatibility with source project database columns
    public function getCodeAttribute()
    {
        return $this->kode;
    }

    public function setCodeAttribute($value)
    {
        $this->kode = $value;
    }

    public function getNameAttribute()
    {
        return $this->nama;
    }

    public function setNameAttribute($value)
    {
        $this->nama = $value;
    }

    public function getTypeAttribute()
    {
        return $this->tipe;
    }

    public function setTypeAttribute($value)
    {
        $this->tipe = $value;
    }

    public function getUnitOfMeasureAttribute()
    {
        return $this->uom;
    }

    public function setUnitOfMeasureAttribute($value)
    {
        $this->uom = $value;
    }

    public function getQtyPerCaseAttribute()
    {
        return $this->qty_case;
    }

    public function setQtyPerCaseAttribute($value)
    {
        $this->qty_case = $value;
    }

    public function getMinStockAttribute()
    {
        return $this->min_stok;
    }

    public function setMinStockAttribute($value)
    {
        $this->min_stok = $value;
    }

    public function getIsActiveAttribute()
    {
        return $this->status === 'Aktif';
    }

    public function setIsActiveAttribute($value)
    {
        $this->status = $value ? 'Aktif' : 'Tidak Aktif';
    }

    public function getStockQuantity(?int $storageLocationId = null): float
    {
        $query = $this->stocks();
        if ($storageLocationId) {
            $query->where('storage_location_id', $storageLocationId);
        }
        return (float) $query->sum('qty');
    }
}
