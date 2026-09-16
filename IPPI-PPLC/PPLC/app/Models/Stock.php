<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    protected $fillable = [
        'job_no',
        'item_name',
        'proses',
        'source',
        'customer',
        'pcs_day',
        'stock',
        'strength',
        'remarks',
    ];

    protected $casts = [
        'pcs_day'   => 'float',
        'stock'     => 'float',
        'strength'  => 'float',
    ];

    // Scope filter
    public function scopeSearch($query, $keyword)
    {
        return $query->where(function ($q) use ($keyword) {
            $q->where('job_no',    'like', "%{$keyword}%")
              ->orWhere('item_name', 'like', "%{$keyword}%")
              ->orWhere('source',    'like', "%{$keyword}%");
        });
    }

    public function scopeByProses($query, $proses)
    {
        return $proses ? $query->where('proses', $proses) : $query;
    }

    public function scopeByCustomer($query, $customer)
    {
        return $customer ? $query->where('customer', $customer) : $query;
    }

    public function scopeByRemarks($query, $remarks)
    {
        return $remarks ? $query->where('remarks', $remarks) : $query;
    }
}