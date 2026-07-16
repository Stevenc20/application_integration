<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MrpResult extends Model
{
    protected $fillable = [
        'mrp_run_id',
        'material_id',
        'current_stock',
        'required_quantity',
        'gross_requirement',
        'open_po_qty',
        'net_requirement',
        'safety_stock_qty',
        'qty_per_case',
        'shortage_quantity',
        'recommendation_type',
        'recommended_quantity',
        'recommended_date'
    ];

    protected $casts = [
        'current_stock' => 'decimal:3',
        'required_quantity' => 'decimal:3',
        'gross_requirement' => 'decimal:3',
        'open_po_qty' => 'decimal:3',
        'net_requirement' => 'decimal:3',
        'safety_stock_qty' => 'decimal:3',
        'qty_per_case' => 'decimal:3',
        'shortage_quantity' => 'decimal:3',
        'recommended_quantity' => 'decimal:3',
        'recommended_date' => 'date',
    ];

    public function mrpRun()
    {
        return $this->belongsTo(MrpRun::class, 'mrp_run_id');
    }

    public function material()
    {
        return $this->belongsTo(Material::class, 'material_id');
    }
}
