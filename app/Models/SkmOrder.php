<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkmOrder extends Model
{
    protected $fillable = ['skm_number', 'order_date', 'status', 'notes', 'created_by'];

    protected $casts = ['order_date' => 'date'];

    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }
    public function items() { return $this->hasMany(SkmOrderItem::class); }

    public static function generateNumber(): string
    {
        $prefix = 'SKM-' . date('Y') . '-';
        $last = static::where('skm_number', 'like', $prefix . '%')->orderBy('id', 'desc')->first();
        $next = $last ? (int) substr($last->skm_number, -5) + 1 : 1;
        return $prefix . str_pad($next, 5, '0', STR_PAD_LEFT);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft'            => 'Draft',
            'sent'             => 'Dikirim ke Vendor',
            'partial_received' => 'Diterima Sebagian',
            'completed'        => 'Selesai (Semua Diterima)',
            'cancelled'        => 'Dibatalkan',
            default            => ucfirst($this->status),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft'            => 'bg-gray-100 text-gray-700',
            'sent'             => 'bg-blue-100 text-blue-700',
            'partial_received' => 'bg-yellow-100 text-yellow-700',
            'completed'        => 'bg-green-100 text-green-700',
            'cancelled'        => 'bg-red-100 text-red-700',
            default            => 'bg-gray-100 text-gray-700',
        };
    }

    /** Sync status based on linked POs' GR completion. Called from GoodsReceiptController. */
    public function syncReceivingStatus(): void
    {
        $pos = $this->purchaseOrders()->with('items')->get();
        if ($pos->isEmpty()) return;

        $totalOrdered  = 0;
        $totalReceived = 0;
        foreach ($pos as $po) {
            foreach ($po->items as $item) {
                $totalOrdered  += (float) ($item->qty ?? $item->quantity ?? 0);
                $totalReceived += (float) ($item->qty_received ?? $item->quantity_received ?? 0);
            }
        }
        if ($totalOrdered == 0) return;

        if ($totalReceived >= $totalOrdered) {
            $this->update(['status' => 'completed']);
        } elseif ($totalReceived > 0) {
            $this->update(['status' => 'partial_received']);
        }
    }

    public function purchaseOrders() { return $this->hasMany(\App\Models\PurchaseOrder::class, 'skm_order_id'); }
}
