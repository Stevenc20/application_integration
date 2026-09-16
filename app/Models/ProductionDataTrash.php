<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionDataTrash extends Model
{
    protected $table = 'production_data_trash';

    protected $fillable = [
        'original_table',
        'original_id',
        'data',
        'trashed_at',
        'trashed_by',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'trashed_at' => 'datetime',
            'expires_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')
            ->whereNull('deleted_at')
            ->where('expires_at', '<', now());
    }

    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }
}
