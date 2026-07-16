<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoodsIssue extends Model
{
    use HasFactory;

    protected $table = 'goods_issues';

    protected $fillable = [
        'no_gi',
        'tanggal_issue',
        'storage_location_id',
        'keterangan',
    ];

    protected $casts = [
        'tanggal_issue' => 'date:Y-m-d',
    ];

    public function storageLocation()
    {
        return $this->belongsTo(StorageLocation::class, 'storage_location_id');
    }

    public function items()
    {
        return $this->hasMany(GoodsIssueItem::class, 'goods_issue_id');
    }
}
