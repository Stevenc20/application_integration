<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LembarInspeksi extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'lembar_inspeksi';

    protected $fillable = [
        'no_form', 'job_no', 'part_name', 'part_no', 'type',
        'spec_material', 'type_pallet', 'lokasi', 'view_package',
        'judgement', 'image_path', 'proses_route',

        'dimensi1', 'dimensi2', 'dimensi3', 'dimensi4', 'dimensi5', 'dimensi6', 'dimensi7',
        'dimensi1_item', 'dimensi2_item', 'dimensi3_item', 'dimensi4_item', 'dimensi5_item', 'dimensi6_item', 'dimensi7_item',
        'dimensi1_method', 'dimensi2_method', 'dimensi3_method', 'dimensi4_method', 'dimensi5_method', 'dimensi6_method', 'dimensi7_method',
        'dimensi1_nominal', 'dimensi2_nominal', 'dimensi3_nominal', 'dimensi4_nominal', 'dimensi5_nominal', 'dimensi6_nominal', 'dimensi7_nominal',
        'dimensi1_plus', 'dimensi2_plus', 'dimensi3_plus', 'dimensi4_plus', 'dimensi5_plus', 'dimensi6_plus', 'dimensi7_plus',
        'dimensi1_minus', 'dimensi2_minus', 'dimensi3_minus', 'dimensi4_minus', 'dimensi5_minus', 'dimensi6_minus', 'dimensi7_minus',
        'appearance6', 'appearance7', 'appearance8', 'appearance9',
        'appearance10', 'appearance11', 'appearance12', 'appearance13', 'appearance14',

        'max_sample',
        'tact_time', 'ct_dimensi', 'ct_tanpa_dimensi',

        'dimensi1_sample_1', 'dimensi1_sample_2', 'dimensi1_sample_3',
        'dimensi2_sample_1', 'dimensi2_sample_2', 'dimensi2_sample_3',
        'dimensi3_sample_1', 'dimensi3_sample_2', 'dimensi3_sample_3',
        'dimensi4_sample_1', 'dimensi4_sample_2', 'dimensi4_sample_3',
        'dimensi5_sample_1', 'dimensi5_sample_2', 'dimensi5_sample_3',
        'dimensi6_sample_1', 'dimensi6_sample_2', 'dimensi6_sample_3',
        'dimensi7_sample_1', 'dimensi7_sample_2', 'dimensi7_sample_3',

        'appearance6_results', 'appearance7_results', 'appearance8_results',
        'appearance9_results', 'appearance10_results', 'appearance11_results',
        'appearance12_results', 'appearance13_results', 'appearance14_results',

        'dimensi1_results', 'dimensi2_results', 'dimensi3_results', 'dimensi4_results', 'dimensi5_results', 'dimensi6_results', 'dimensi7_results',

        'ng_details', 'coil_numbers',

        'qg_judgement', 'qg_name', 'tgl_bulan', 'shift',
        'total_produksi', 'repair', 'reject', 'catatan', 'catatan_revisi',
        'prepared_paraf', 'prepared_at',
        'paraf_gl', 'paraf_gl_cols', 'paraf_foreman', 'gl_signed_at', 'foreman_signed_at',
        'gl_name', 'frm_name', 'qc_name',
        'paraf_qc', 'qc_signed_at',
        'paraf_gl_bottom', 'paraf_foreman_bottom',
        'paraf_gl_bottom_name', 'paraf_fm_bottom_name',

        'created_by', 'foreman_id', 'status',
        'qpr_generated', 'qpr_id',
        'bundle_checks', 'bundle_tindakan',
        'revision_records',
        'assigned_operator_id',
        'assigned_gl_id', 
        'assigned_foreman_id',
        'assigned_at',
        'operator_claimed_at',
        'field_revisions', 'sampling_cols',
    ];

    protected $casts = [
        'appearance6_results'  => 'array',
        'appearance7_results'  => 'array',
        'appearance8_results'  => 'array',
        'appearance9_results'  => 'array',
        'appearance10_results' => 'array',
        'appearance11_results' => 'array',
        'appearance12_results' => 'array',
        'appearance13_results' => 'array',
        'appearance14_results' => 'array',
        'dimensi1_results'     => 'array',
        'dimensi2_results'     => 'array',
        'dimensi3_results'     => 'array',
        'dimensi4_results'     => 'array',
        'dimensi5_results'     => 'array',
        'dimensi6_results'     => 'array',
        'dimensi7_results'     => 'array',
        'ng_details'           => 'array',
        'coil_numbers'         => 'array',
        'qpr_generated'        => 'boolean',
        'tgl_bulan'            => 'date',
        'prepared_at'          => 'datetime',
        'paraf_gl_cols'        => 'array',
        'gl_signed_at'         => 'datetime',
        'foreman_signed_at'    => 'datetime',
        'qc_signed_at'         => 'datetime',
        'bundle_checks'        => 'array',
        'revision_records'     => 'array',
        'assigned_at'          => 'datetime',
        'operator_claimed_at'  => 'datetime',
        'field_revisions'      => 'array',
        'sampling_cols'        => 'array',
    ];

    // ── Relasi ──

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function foreman()
    {
        return $this->belongsTo(User::class, 'foreman_id');
    }

    public function qpr()
    {
        return $this->belongsTo(Qpr::class, 'qpr_id');
    }

    public function assignedOperator()
    {
        return $this->belongsTo(User::class, 'assigned_operator_id');
    }

    public function assignedGl()
    {
        return $this->belongsTo(User::class, 'assigned_gl_id');
    }

    public function assignedForeman()
    {
        return $this->belongsTo(User::class, 'assigned_foreman_id');
    }

    public function itemChecks()
    {
        return $this->hasMany(ItemCheck::class, 'lembar_inspeksi_id');
    }

    // ── Helper: cari detail NG untuk sel appearance (format keyed "6_1" atau legacy list) ──

    protected function findNgDetailForCell(int $row, $sample): ?array
    {
        $ngDetails = $this->ng_details ?? [];
        if (empty($ngDetails) || ! is_array($ngDetails)) {
            return null;
        }

        if (! array_is_list($ngDetails)) {
            $key = "{$row}_{$sample}";
            return (isset($ngDetails[$key]) && is_array($ngDetails[$key])) ? $ngDetails[$key] : null;
        }

        foreach ($ngDetails as $d) {
            if (! is_array($d)) {
                continue;
            }
            if (($d['row'] ?? null) == $row && ($d['sample'] ?? null) == $sample) {
                return $d;
            }
        }

        return null;
    }

    // ── Helper: ambil semua item NG dari hasil inspeksi ──

    public function getNgItems(): array
    {
        $ng = [];
        $ngDetails = $this->ng_details ?? [];

        if (is_array($ngDetails) && !empty($ngDetails)) {
            // If it's a list format (legacy)
            if (array_is_list($ngDetails)) {
                foreach ($ngDetails as $detail) {
                    if (!is_array($detail)) continue;
                    $row = $detail['row'] ?? null;
                    $sample = $detail['sample'] ?? null;
                    if (!$row || !$sample) continue;

                    $standar = '';
                    $val = '';
                    if ($row >= 1 && $row <= 7) {
                        $standar = $this->{"dimensi{$row}_item"} . ' (' . $this->{"dimensi{$row}_nominal"} . ')';
                        $results = $this->{"dimensi{$row}_results"} ?? [];
                        $val = $results[$sample] ?? '';
                    } elseif ($row >= 6 && $row <= 14) {
                        $standar = $this->{"appearance{$row}"};
                        $results = $this->{"appearance{$row}_results"} ?? [];
                        $val = $results[$sample] ?? '';
                    }
                    
                    $problems = $detail['problems'] ?? $detail['problem'] ?? [];
                    $penyebab = $detail['causes'] ?? $detail['penyebab'] ?? [];

                    $ng[] = [
                        'row'      => $row,
                        'sample'   => $sample,
                        'val'      => $val,
                        'standar'  => $standar,
                        'proses'   => $detail['proses'] ?? null,
                        'problem'  => is_array($problems) ? $problems : ($problems ? [$problems] : []),
                        'penyebab' => is_array($penyebab) ? $penyebab : ($penyebab ? [$penyebab] : []),
                        'jam'      => $detail['jam'] ?? null,
                        'photo'    => $detail['photo'] ?? null,
                    ];
                }
            } else {
                // Keyed format like "1_141" => {...}
                foreach ($ngDetails as $key => $detail) {
                    if (!is_array($detail)) continue;
                    $parts = explode('_', $key);
                    if (count($parts) < 2) continue;
                    $row = (int)$parts[0];
                    $sample = (int)$parts[1];

                    $standar = '';
                    $val = '';
                    if ($row >= 1 && $row <= 7) {
                        $standar = $this->{"dimensi{$row}_item"} . ' (' . $this->{"dimensi{$row}_nominal"} . ')';
                        $results = $this->{"dimensi{$row}_results"} ?? [];
                        $val = $results[$sample] ?? '';
                    } elseif ($row >= 6 && $row <= 14) {
                        $standar = $this->{"appearance{$row}"};
                        $results = $this->{"appearance{$row}_results"} ?? [];
                        $val = $results[$sample] ?? '';
                    }

                    $problems = $detail['problems'] ?? $detail['problem'] ?? [];
                    $penyebab = $detail['causes'] ?? $detail['penyebab'] ?? [];

                    $ng[] = [
                        'row'      => $row,
                        'sample'   => $sample,
                        'val'      => $val,
                        'standar'  => $standar,
                        'proses'   => $detail['proses'] ?? null,
                        'problem'  => is_array($problems) ? $problems : ($problems ? [$problems] : []),
                        'penyebab' => is_array($penyebab) ? $penyebab : ($penyebab ? [$penyebab] : []),
                        'jam'      => $detail['jam'] ?? null,
                        'photo'    => $detail['photo'] ?? null,
                    ];
                }
            }
            return $ng;
        }

        // Fallback to checking appearance results directly if ng_details is completely empty
        for ($row = 6; $row <= 14; $row++) {
            $results = $this->{"appearance{$row}_results"} ?? [];
            if (! is_array($results)) {
                continue;
            }
            foreach ($results as $sample => $val) {
                if (in_array($val, ['△', '⨉', '✕', 'ng', 'NG'], true)) {
                    $detail = $this->findNgDetailForCell($row, $sample);
                    $problems = $detail ? ($detail['problems'] ?? $detail['problem'] ?? []) : [];
                    $penyebab = $detail ? ($detail['causes'] ?? $detail['penyebab'] ?? []) : [];
                    $ng[] = [
                        'row'      => $row,
                        'sample'   => $sample,
                        'val'      => $val,
                        'standar'  => $this->{"appearance{$row}"},
                        'proses'   => $detail['proses'] ?? null,
                        'problem'  => is_array($problems) ? $problems : ($problems ? [$problems] : []),
                        'penyebab' => is_array($penyebab) ? $penyebab : ($penyebab ? [$penyebab] : []),
                        'jam'      => $detail['jam'] ?? null,
                        'photo'    => $detail['photo'] ?? null,
                    ];
                }
            }
        }

        return $ng;
    }

    // ── Helper: cek apakah ada NG ──

    public function hasNg(): bool
    {
        if (strtoupper((string) ($this->qg_judgement ?? '')) === 'NG') {
            return true;
        }
        $ngDetails = $this->ng_details ?? [];
        if (is_array($ngDetails) && count($ngDetails) > 0) {
            return true;
        }

        return count($this->getNgItems()) > 0;
    }

    // ── Helper: build defect string untuk QPR ──

    public function getDefectString(): string
    {
        $problems = [];
        foreach ($this->ng_details ?? [] as $detail) {
            if (! is_array($detail)) {
                continue;
            }
            $list = $detail['problems'] ?? $detail['problem'] ?? [];
            foreach ((array) $list as $p) {
                if ($p && ! in_array($p, $problems, true)) {
                    $problems[] = $p;
                }
            }
        }

        if (empty($problems)) {
            foreach ($this->getNgItems() as $item) {
                foreach ($item['problem'] ?? [] as $p) {
                    if ($p && ! in_array($p, $problems, true)) {
                        $problems[] = $p;
                    }
                }
            }
        }

        return implode(', ', $problems);
    }
}