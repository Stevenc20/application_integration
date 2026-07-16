<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionOrder extends Model
{
    protected $fillable = [
        'order_number',
        'material_id',
        'bom_id',
        'routing_id',
        'quantity_planned',
        'quantity_produced',
        'quantity_ok',
        'quantity_ng',
        'planned_start_date',
        'planned_end_date',
        'actual_start_date',
        'actual_end_date',
        'status',
        'notes',
        'created_by'
    ];

    protected $casts = [
        'planned_start_date' => 'date',
        'planned_end_date' => 'date',
        'actual_start_date' => 'date',
        'actual_end_date' => 'date',
        'quantity_planned' => 'decimal:3',
        'quantity_produced' => 'decimal:3',
        'quantity_ok' => 'decimal:3',
        'quantity_ng' => 'decimal:3',
    ];

    public function material()
    {
        return $this->belongsTo(Material::class, 'material_id');
    }

    public function bom()
    {
        return $this->belongsTo(Bom::class, 'bom_id');
    }

    public function components()
    {
        return $this->hasMany(ProductionOrderComponent::class, 'production_order_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function generateNumber(): string
    {
        $prefix = 'PRO-' . date('Y') . '-';
        $last = static::where('order_number', 'like', $prefix . '%')->orderBy('id', 'desc')->first();
        $next = $last ? (int) substr($last->order_number, -5) + 1 : 1;
        return $prefix . str_pad($next, 5, '0', STR_PAD_LEFT);
    }
}
