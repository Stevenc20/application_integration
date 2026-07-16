<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    protected $table = 'vendors';

    protected $fillable = [
        'kode',
        'nama',
        'tipe',
        'alamat',
        'kontak',
        'email',
        'telepon',
        'status',
    ];

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

    public function getVendorTypeAttribute()
    {
        if (stripos($this->tipe, 'coil') !== false) {
            return 'coil_center';
        }
        if (stripos($this->tipe, 'process') !== false) {
            return 'process';
        }
        return 'general';
    }

    public function setVendorTypeAttribute($value)
    {
        if ($value === 'coil_center') {
            $this->tipe = 'Coil Center (Supplier Bahan Baku)';
        } elseif ($value === 'process') {
            $this->tipe = 'Process / Makloon';
        } else {
            $this->tipe = 'Umum';
        }
    }

    public function getIsActiveAttribute()
    {
        return $this->status === 'Aktif';
    }

    public function setIsActiveAttribute($value)
    {
        $this->status = $value ? 'Aktif' : 'Tidak Aktif';
    }

    public function isCoilCenter(): bool
    {
        return $this->vendor_type === 'coil_center';
    }

    public function isProcessVendor(): bool
    {
        return $this->vendor_type === 'process';
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class, 'vendor_id');
    }
}
