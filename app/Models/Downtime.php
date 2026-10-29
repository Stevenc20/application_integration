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

    protected $appends = [
        'image_url',
        'image_url_part',
        'image_url_tooling',
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
     * Resolve public URL or root-relative path for a given relative image path.
     */
    public static function resolveImageUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            // Jika URL adalah localhost namun diakses di environment lain/HTTPS, jadikan root-relative
            $parsed = parse_url($path);
            if (isset($parsed['host']) && in_array(strtolower($parsed['host']), ['localhost', '127.0.0.1'])) {
                return ($parsed['path'] ?? '') . (isset($parsed['query']) ? '?' . $parsed['query'] : '');
            }
            return $path;
        }

        $clean = ltrim($path, '/');
        // Check in public/uploads
        if (file_exists(public_path('uploads/' . $clean))) {
            return '/uploads/' . $clean;
        }
        // Check in public/storage
        if (file_exists(public_path('storage/' . $clean))) {
            return '/storage/' . $clean;
        }
        // Check in storage/app/public
        if (file_exists(storage_path('app/public/' . $clean))) {
            return '/storage/' . $clean;
        }

        return '/uploads/' . $clean;
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
