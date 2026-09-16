<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StorageLocation extends Model
{
    use HasFactory;

    protected $table = 'storage_locations';

    protected $fillable = [
        'kode',
        'nama',
        'deskripsi',
        'tipe_material',
        'is_scrap',
        'vendor_id',
    ];

    protected $casts = [
        'is_scrap' => 'boolean',
    ];

    // Accessors/Mutators for compatibility
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

    public function getDescriptionAttribute()
    {
        return $this->deskripsi;
    }

    public function setDescriptionAttribute($value)
    {
        $this->deskripsi = $value;
    }

    public function getMaterialTypeAttribute()
    {
        return $this->tipe_material;
    }

    public function setMaterialTypeAttribute($value)
    {
        $this->tipe_material = $value;
    }

    public function stocks()
    {
        return $this->hasMany(MaterialStock::class, 'storage_location_id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }
}
