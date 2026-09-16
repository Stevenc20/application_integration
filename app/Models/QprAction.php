<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class QprAction extends Model
{
    protected $table = 'qpr_actions';

    protected $fillable = [
        'qpr_id', 'action', 'schedule',
        'tgl_verif_1', 'tgl_verif_2', 'tgl_verif_3',
        'verif_1_status', 'verif_2_status', 'verif_3_status',
        'pdca', 'pic', 'status',
        'evidence_file', 'evidence_remarks'
    ];

    protected $casts = [
        'schedule'    => 'date',
        'tgl_verif_1' => 'date',
        'tgl_verif_2' => 'date',
        'tgl_verif_3' => 'date',
    ];

    public function qpr()
    {
        return $this->belongsTo(Qpr::class, 'qpr_id');
    }
}