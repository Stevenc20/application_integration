<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Downtime extends Model
{
    protected $attributes = [
        'image_path' => null,
        'image_path_part' => null,
        'image_path_tooling' => null,
    ];

    protected $fillable = [
        'job_master_id',
        'jenis_downtime',
        'problem',
        'penyebab',
        'action',
        'pic',
        'image_path',
        'image_path_part',
        'image_path_tooling',
        'start_time',
        'finish_time',
        'duration_seconds',
    ];

    public function jobMaster()
    {
        return $this->belongsTo(JobMaster::class);
    }

    public function hambatanJalur()
    {
        return $this->hasOne(HambatanJalur::class, 'downtime_id');
    }

    /**
     * Resolve full public URL for a given relative image path.
     */
    public static function resolveImageUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $clean = ltrim($path, '/');
        // Check in public/uploads
        if (file_exists(public_path('uploads/' . $clean))) {
            return asset('uploads/' . $clean);
        }
        // Check in public/storage
        if (file_exists(public_path('storage/' . $clean))) {
            return asset('storage/' . $clean);
        }
        // Check in storage/app/public
        if (file_exists(storage_path('app/public/' . $clean))) {
            return asset('storage/' . $clean);
        }

        return asset('uploads/' . $clean);
    }

    public function getImageUrlAttribute(): ?string
    {
        $effective = $this->image_path_part ?: ($this->image_path_tooling ?: $this->image_path);
        return self::resolveImageUrl($effective);
    }

    public function getImageUrlPartAttribute(): ?string
    {
        return self::resolveImageUrl($this->image_path_part ?: $this->image_path);
    }

    public function getImageUrlToolingAttribute(): ?string
    {
        return self::resolveImageUrl($this->image_path_tooling);
    }
}
