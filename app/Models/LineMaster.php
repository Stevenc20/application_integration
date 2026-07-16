<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LineMaster extends Model
{
    use SoftDeletes;

    protected $table = 'line_masters';

    protected $fillable = [
        'line_code',
        'line_name',
        'production_start',
        'production_end',
        'capacity',
        'machine_count',
        'shift',
        'description',
        'status',
    ];

    protected $casts = [
        'capacity'      => 'integer',
        'machine_count' => 'integer',
    ];

    /**
     * Relasi ke production plans (via production_lines).
     * Production Plan pakai ProductionLine model (line_name = foreign key soft).
     */
    public function productionPlans(): HasMany
    {
        return $this->hasMany(ProductionLine::class, 'line_name', 'line_name');
    }

    /**
     * Scope: active lines only.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Accessor: badge color berdasar status.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'active'      => 'emerald',
            'maintenance' => 'amber',
            'inactive'    => 'gray',
            default       => 'gray',
        };
    }
}
