<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

class LiTemplate extends Model
{
    use SoftDeletes;

    protected $table = 'li_templates';

    protected $fillable = [
        'template_name', 'job_no', 'part_no', 'part_name', 'type',

        'spec_material', 'type_pallet', 'view_package', 'image_path',
        'tact_time', 'ct_dimensi', 'ct_tanpa_dimensi',
        'dimensi1', 'dimensi2', 'dimensi3', 'dimensi4', 'dimensi5', 'dimensi6', 'dimensi7',
        'dimensi1_item', 'dimensi2_item', 'dimensi3_item', 'dimensi4_item', 'dimensi5_item', 'dimensi6_item', 'dimensi7_item',
        'dimensi1_method', 'dimensi2_method', 'dimensi3_method', 'dimensi4_method', 'dimensi5_method', 'dimensi6_method', 'dimensi7_method',
        'appearance6', 'appearance7', 'appearance8', 'appearance9',
        'appearance10', 'appearance11', 'appearance12', 'appearance13', 'appearance14',
        'created_by', 'sampling_cols'
    ];

    protected $casts = [
        'sampling_cols' => 'array',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

