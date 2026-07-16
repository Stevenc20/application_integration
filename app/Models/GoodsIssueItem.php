<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoodsIssueItem extends Model
{
    use HasFactory;

    protected $table = 'goods_issue_items';

    protected $fillable = [
        'goods_issue_id',
        'material_id',
        'qty',
    ];

    public function goodsIssue()
    {
        return $this->belongsTo(GoodsIssue::class, 'goods_issue_id');
    }

    public function material()
    {
        return $this->belongsTo(Material::class, 'material_id');
    }
}
