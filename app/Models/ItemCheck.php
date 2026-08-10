<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemCheck extends Model
{
    //
    protected $fillable = [
        'lembar_inspeksi_id',
        'production_plan_id',
        'operator_id',
        'tanggal',
        'shift',
        'waktu_mulai',
        'waktu_selesai',
        'status',
        'hasil_dimensi',
        'hasil_visual',
        'ng_details',
        'judgement',
        'catatan',
        'catatan_revisi',
        'field_revisions',
        'paraf_operator',
        'paraf_foreman',
        'paraf_leader',
        'qpr_generated',
        'qpr_id',
        'repair',
        'reject',
        'assigned_gl_id',
        'assigned_foreman_id',
        'bundle_checks',
        'bundle_tindakan',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'waktu_mulai' => 'datetime',
        'waktu_selesai' => 'datetime',
        'hasil_dimensi' => 'array',
        'hasil_visual' => 'array',
        'ng_details' => 'array',
        'qpr_generated' => 'boolean',
        'bundle_checks' => 'array',
        'field_revisions' => 'array',
    ];

    public function masterTemplate()
    {
        return $this->belongsTo(LembarInspeksi::class, 'lembar_inspeksi_id')->withTrashed();
    }

    public function schedule()
    {
        return $this->belongsTo(ProductionPlan::class, 'production_plan_id');
    }

    public function operator()
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function assignedGl()
    {
        return $this->belongsTo(User::class, 'assigned_gl_id');
    }

    public function assignedForeman()
    {
        return $this->belongsTo(User::class, 'assigned_foreman_id');
    }

    public function getDimensionNgs(): array
    {
        $ngs = [];
        $dimData = $this->hasil_dimensi ?? [];
        if (!is_array($dimData) || empty($dimData)) return $ngs;

        $master = $this->masterTemplate;
        if (!$master) return $ngs;

        for ($ri = 0; $ri < 5; $ri++) {
            $nominal = $master->{"dimensi" . ($ri + 1) . "_nominal"};
            if ($nominal === null || $nominal === '') continue;

            $plus = (float)($master->{"dimensi" . ($ri + 1) . "_plus"} ?? 0);
            $minus = (float)($master->{"dimensi" . ($ri + 1) . "_minus"} ?? 0);
            $target = (float)$nominal;
            $min = $target - $minus;
            $max = $target + $plus;
            $item_name = $master->{"dimensi" . ($ri + 1) . "_item"};

            // dimData keys are usually like "0_1", "0_2" where 0 is the row index and 1 is the col index
            foreach ($dimData as $key => $val) {
                $parts = explode('_', $key);
                if (count($parts) === 2 && (int)$parts[0] === $ri) {
                    $col = $parts[1];
                    $valFloat = (float)str_replace(',', '.', (string)$val);
                    if ($val !== '' && $val !== null && ($valFloat < $min || $valFloat > $max)) {
                        $ngs[] = [
                            'row' => $ri,
                            'col' => $col,
                            'item' => $item_name,
                            'val' => $val,
                            'min' => $min,
                            'max' => $max,
                            'standard' => "Ø {$nominal} +{$plus}/-{$minus}",
                        ];
                    }
                }
            }
        }
        return $ngs;
    }

    public function hasNg(): bool
    {
        // 1. Cek dari judgement manual (jika ada)
        $judgement = $this->judgement;
        if (is_string($judgement)) {
            $decoded = json_decode($judgement, true);
            if (is_array($decoded)) {
                foreach ($decoded as $val) {
                    if (strtoupper((string)$val) === 'NG') return true;
                }
            } elseif (strtoupper((string)$judgement) === 'NG') {
                return true;
            }
        }

        // 2. Cek dari visual/appearance NG details
        $ngDetails = $this->ng_details ?? [];
        if (is_array($ngDetails) && count($ngDetails) > 0) return true;

        // 3. Cek dimensi secara dinamis
        if (count($this->getDimensionNgs()) > 0) return true;

        return false;
    }

    public function getDefectTypesString(): string
    {
        $problems = [];
        
        // Visual/Appearance defects
        foreach ($this->ng_details ?? [] as $detail) {
            if (!is_array($detail)) continue;
            
            $list = $detail['problems'] ?? $detail['problem'] ?? [];
            foreach ((array) $list as $p) {
                if ($p && !in_array($p, $problems, true)) {
                    $problems[] = $p;
                }
            }
        }

        return implode(", ", $problems);
    }

    public function getDefectKeteranganString(): string
    {
        $details = [];

        // 1. Catatan from visual/appearance defects
        foreach ($this->ng_details ?? [] as $key => $detail) {
            if (!is_array($detail)) continue;
            
            $catatan = $detail['catatan'] ?? '';
            $problems = implode(", ", (array)($detail['problems'] ?? $detail['problem'] ?? []));
            if ($catatan) {
                $details[] = "Catatan ({$problems}): {$catatan}";
            }
        }

        // 2. Dimension defects
        $dimNgs = $this->getDimensionNgs();
        foreach ($dimNgs as $ng) {
            $desc = "Dimensi NG: {$ng['item']} (PCS {$ng['col']}) = {$ng['val']} [Std: {$ng['standard']}]";
            if (!in_array($desc, $details, true)) {
                $details[] = $desc;
            }
        }

        return implode("\n", $details);
    }

    public function getProsesRepairString(): string
    {
        $prosesList = [];
        foreach ($this->ng_details ?? [] as $detail) {
            if (!is_array($detail)) continue;
            $p = $detail['proses'] ?? '';
            if ($p) {
                if (is_array($p)) {
                    $parts = $p;
                } else {
                    $parts = array_map('trim', explode(',', $p));
                }
                foreach ($parts as $part) {
                    $partStr = trim((string)$part);
                    if ($partStr && !in_array($partStr, $prosesList, true)) {
                        $prosesList[] = $partStr;
                    }
                }
            }
        }
        return implode(", ", $prosesList);
    }

    public function getJamKejadianString(): string
    {
        foreach ($this->ng_details ?? [] as $detail) {
            if (!is_array($detail)) continue;
            if (!empty($detail['jam'])) {
                // Return only the first jam, replace dot with colon for TIME column compatibility
                $time = str_replace('.', ':', $detail['jam']);
                // Ensure it's roughly HH:MM
                if (preg_match('/^(\d{1,2}:\d{2})/', $time, $matches)) {
                    return $matches[1];
                }
                return $time;
            }
        }
        return '';
    }
    
    public function getAreaKejadianString(): string
    {
        $areaList = [];
        foreach ($this->ng_details ?? [] as $detail) {
            if (!is_array($detail)) continue;
            
            $areas = $detail['areas'] ?? [];
            foreach ((array)$areas as $areaVal) {
                if ($areaVal && !in_array((string)$areaVal, $areaList, true)) {
                    $areaList[] = (string)$areaVal;
                }
            }
        }
        
        // Sort area numerically if possible
        usort($areaList, function($a, $b) {
            return (int)$a - (int)$b;
        });
        
        return implode(", ", $areaList);
    }

    public function getAreaProblemsArray(): array
    {
        $areaProblems = [];
        foreach ($this->ng_details ?? [] as $detail) {
            if (!is_array($detail)) continue;
            
            $areas = (array)($detail['areas'] ?? []);
            $problems = (array)($detail['problems'] ?? $detail['problem'] ?? []);
            
            foreach ($areas as $areaVal) {
                $aStr = (string)$areaVal;
                if (!$aStr) continue;
                
                if (!isset($areaProblems[$aStr])) {
                    $areaProblems[$aStr] = [];
                }
                
                foreach ($problems as $p) {
                    if ($p && !in_array($p, $areaProblems[$aStr], true)) {
                        $areaProblems[$aStr][] = $p;
                    }
                }
            }
        }
        return $areaProblems;
    }
}
