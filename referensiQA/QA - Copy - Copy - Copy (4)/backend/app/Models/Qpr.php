<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Qpr extends Model
{
    use SoftDeletes;

    protected $table = 'qprs';

    protected $fillable = [
        'no_job', 'model', 'tanggal', 'nama_part', 'no_qpr', 'kontrol_part',
        'rework_qty', 'reject_qty', 'stock_ippi_qty', 'rencana_produksi', 'proses_repair',
        'kategori_problem', 'defect', 'defect_keterangan',
        'area', 'area_problems', 'lokasi', 'shift', 'jam', 'last_date_problem', 'dokumen',
        'analisa_man', 'analisa_method', 'analisa_machine', 'analisa_material', 'analisa_environment',
        'analisa_man_ket', 'analisa_method_ket', 'analisa_machine_ket',
        'analisa_material_ket', 'analisa_environment_ket',
        'correction', 'correction_items', 'target', 'pic', 'pic_seksi',
        'status', 'pencegahan', 'dampak_items',
        'sketch', 'created_by', 'approval_signatures', 'sketches', 'assigned_foreman_id',
        'target_selesai', 'verif_1', 'verif_2', 'verif_3', 'hasil', 'remark',
        'is_a3_required', 'a3_due_date', 'a3_document',
        'inspeksi_id', 'source',
    ];

    protected $casts = [
        'tanggal'             => 'date',
        'target'              => 'date',
        'target_selesai'      => 'date',
        'analisa_man'         => 'boolean',
        'analisa_method'      => 'boolean',
        'analisa_machine'     => 'boolean',
        'analisa_material'    => 'boolean',
        'analisa_environment' => 'boolean',
        'approval_signatures' => 'array',
        'correction_items'    => 'array',
        'dampak_items'        => 'array',
        'sketches'            => 'array',
        'area_problems'       => 'array',
        'is_a3_required'      => 'boolean',
    ];
    public function actions()
    {
        return $this->hasMany(QprAction::class, 'qpr_id');
    }

    public function inspeksi()
    {
        return $this->belongsTo(LembarInspeksi::class, 'inspeksi_id');
    }
}