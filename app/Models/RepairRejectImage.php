<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepairRejectImage extends Model
{
    protected $fillable = ['repair_reject_log_id', 'image_path', 'image_type'];

    protected $appends = ['url'];

    public function getUrlAttribute(): string
    {
        $path = $this->image_path;
        if (!$path) {
            return '';
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        $clean = ltrim($path, '/');
        // Cek di public/uploads
        if (file_exists(public_path('uploads/' . $clean))) {
            return '/uploads/' . $clean;
        }
        // Cek di public/storage
        if (file_exists(public_path('storage/' . $clean))) {
            return '/storage/' . $clean;
        }
        // Cek di storage/app/public
        if (file_exists(storage_path('app/public/' . $clean))) {
            return '/storage/' . $clean;
        }
        // Default ke uploads
        return '/uploads/' . $clean;
    }

    public function repairRejectLog()
    {
        return $this->belongsTo(RepairRejectLog::class);
    }
}
