<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Carbon\Carbon;

class ExcelScheduleParser
{

    public function parse(string $filePath, string $originalName): array
    {
        try {
            $sheetNames = $this->getSheetNames($filePath);
            $sheetsToProcess = $this->chooseSheets($sheetNames);
            if (empty($sheetsToProcess)) {
                return ['error' => 'Tidak ada sheet Shift Pagi / Shift Malam yang ditemukan di file ini.'];
            }

            $reader = IOFactory::createReaderForFile($filePath);
            $reader->setReadDataOnly(true);
            $reader->setLoadSheetsOnly($sheetsToProcess);
            $reader->setReadEmptyCells(false);
            $spreadsheet = $reader->load($filePath);

            $resultSheets = [];
            foreach ($spreadsheet->getAllSheets() as $ws) {
                $parsed = $this->parseSheet($ws, $ws->getTitle());
                foreach ($parsed as $key => $section) {
                    $resultSheets[$key] = $section;
                }
            }

            $spreadsheet->disconnectWorksheets();

            $uploadDate = $this->extractDate($resultSheets, $originalName);

            $totalExcelRows = 0;
            $totalDataRows = 0;
            $totalMetaSkipped = 0;
            $importLog = [];

            foreach ($resultSheets as $key => $section) {
                $parts = explode('|||', $key);
                $sheetName = $parts[0] ?? $key;
                if (!isset($importLog[$sheetName])) {
                    $importLog[$sheetName] = ['sections' => [], 'sheet_excel_rows' => 0];
                }
                $stats = $section['_stats'] ?? [];
                $sr = $stats['section_total_rows'] ?? 0;
                $ms = $stats['meta_rows_skipped'] ?? 0;
                $dr = $stats['data_rows_output'] ?? 0;
                $importLog[$sheetName]['sections'][$section['press_name']] = [
                    'section_rows' => $sr,
                    'meta_rows_before_header' => $ms,
                    'data_rows_output' => $dr,
                    'header_local_idx' => $stats['header_local_idx'] ?? null,
                    'header_content' => $stats['header_content'] ?? null,
                ];
                $importLog[$sheetName]['sheet_excel_rows'] += $sr;
                $totalExcelRows += $sr;
                $totalMetaSkipped += $ms;
                $totalDataRows += $dr;
            }
            $importLog['summary'] = [
                'total_excel_rows_scanned' => $totalExcelRows,
                'total_meta_rows_before_header' => $totalMetaSkipped,
                'total_data_rows_output' => $totalDataRows,
            ];

            foreach ($resultSheets as $key => $section) {
                unset($resultSheets[$key]['_stats']);
            }

            $resultSheets = array_filter($resultSheets, fn($s) => !empty($s['rows']));

            if (empty($resultSheets)) {
                return ['error' => 'Tidak ada data job yang berhasil dibaca dari file Excel. Pastikan format file sesuai.'];
            }

            return [
                'success' => true,
                'upload_date' => $uploadDate,
                'sheets' => $resultSheets,
                'log' => $importLog,
            ];

        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function getSheetNames(string $filePath): array
    {
        try {
            $zip = new \ZipArchive();
            if ($zip->open($filePath) !== true) return [];
            $xml = simplexml_load_string($zip->getFromName('xl/workbook.xml'));
            $names = [];
            if ($xml && isset($xml->sheets)) {
                foreach ($xml->sheets->sheet as $sheet) {
                    $names[] = (string)$sheet['name'];
                }
            }
            $zip->close();
            return $names;
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function chooseSheets(array $sheetNames): array
    {
        $targets = ['Shift Pagi', 'Shift Malam'];
        $result = [];

        foreach ($targets as $base) {
            $rev = $base . ' (Rev)';
            $foundRev = null;
            $foundBase = null;

            foreach ($sheetNames as $sn) {
                $su = strtoupper($sn);
                $baseUpper = strtoupper($base);
                if (strtoupper($sn) === $baseUpper) {
                    $foundBase = $sn;
                } elseif (str_contains($su, $baseUpper) && str_contains($su, 'REV')) {
                    $foundRev = $sn;
                }
            }

            if ($foundRev) {
                $result[] = $foundRev;
                if ($foundBase) $result[] = $foundBase;
            } elseif ($foundBase) {
                $result[] = $foundBase;
            }
        }

        if (empty($result)) {
            foreach ($sheetNames as $sn) {
                if (str_contains(strtoupper($sn), 'SHIFT') && !str_contains(strtoupper($sn), 'MASTER')) {
                    $result[] = $sn;
                }
            }
        }

        return $result;
    }

    private function parseSheet($ws, string $sheetName): array
    {
        $allRows = $ws->toArray(null, null, $ws->getHighestDataRow(), null, false, false, false);
        $total = count($allRows);
        $result = [];

        $pressStarts = [];
        foreach ($allRows as $rIdx => $row) {
            $col2 = $row[2] ?? null;
            if ($col2 !== null && preg_match('/^PRESS\s+[A-Z]$/i', trim((string)$col2))) {
                $pressStarts[] = ['idx' => $rIdx, 'name' => strtoupper(trim((string)$col2))];
            }
        }

        if (empty($pressStarts)) {
            $pressName = 'PRESS A';
            foreach (['PRESS A', 'PRESS B', 'PRESS C', 'PRESS D'] as $p) {
                if (stripos($sheetName, $p) !== false) { $pressName = $p; break; }
            }
            $pressStarts[] = ['idx' => 0, 'name' => $pressName];
        }

        for ($i = 0; $i < count($pressStarts); $i++) {
            $startR = $pressStarts[$i]['idx'];
            $pressName = $pressStarts[$i]['name'];
            $endR = ($i + 1 < count($pressStarts)) ? $pressStarts[$i + 1]['idx'] : $total;

            $sectionRows = array_slice($allRows, $startR, $endR - $startR);
            $section = $this->parsePressSection($sectionRows, $startR, $sheetName, $pressName);
            if ($section && !empty($section['rows'])) {
                $key = $sheetName . '|||' . $pressName;
                $result[$key] = $section;
            }
        }

        return $result;
    }

    private function parsePressSection(array $sectionRows, int $offset, string $sheetName, string $pressName): ?array
    {
        $hari = null;
        $tgl = null;
        $jam = null;
        $revisi = null;
        $lastJobMaster = null;

        $headerLocalIdx = null;
        $colMap = [];
        $jobNoMain = null;
        $jobNoAlt = null;

        foreach ($sectionRows as $localI => $row) {
            $col2 = $row[2] ?? null;
            if ($this->isHeaderRow($row)) {
                $headerLocalIdx = $localI;
                foreach ($row as $j => $v) {
                    if ($v === null) continue;
                    $vStr = strtoupper(str_replace("\n", ' ', trim((string)$v)));
                    if (in_array($vStr, ['NO', 'NO.']) && !isset($colMap['row_no'])) $colMap['row_no'] = $j;
                    elseif (str_contains($vStr, 'JOB MASTER') && !isset($colMap['job_master'])) $colMap['job_master'] = $j;
                    elseif (str_contains($vStr, 'TYPE PLT') && !isset($colMap['type_plt'])) $colMap['type_plt'] = $j;
                    elseif ((str_contains($vStr, 'QTY/PLT') || str_contains($vStr, 'QTY/ PLT')) && !isset($colMap['qty_plt'])) $colMap['qty_plt'] = $j;
                    elseif (str_contains($vStr, 'KEB. MTL') && !isset($colMap['keb_mtl'])) $colMap['keb_mtl'] = $j;
                    elseif (str_contains($vStr, 'TOTAL PLT') && !isset($colMap['total_plt'])) $colMap['total_plt'] = $j;
                    elseif (str_contains($vStr, 'JOB NO.') && $jobNoMain === null) $jobNoMain = $j;
                    elseif ($vStr === 'JOB NO' && $jobNoAlt === null) $jobNoAlt = $j;
                    elseif (str_contains($vStr, 'EACH PART') && !isset($colMap['each_part'])) $colMap['each_part'] = $j;
                    elseif ((str_contains($vStr, 'PLAN (PCS)') || $vStr === 'PLAN') && !isset($colMap['plan'])) $colMap['plan'] = $j;
                    elseif ($vStr === 'OK' && !isset($colMap['ok'])) $colMap['ok'] = $j;
                    elseif ($vStr === 'REPAIR' && !isset($colMap['repair'])) $colMap['repair'] = $j;
                    elseif ($vStr === 'REJECT' && !isset($colMap['reject'])) $colMap['reject'] = $j;
                    elseif (str_contains($vStr, 'TOTAL MESIN') && !isset($colMap['total_mesin'])) $colMap['total_mesin'] = $j;
                    elseif ((str_contains($vStr, 'CT (') || str_contains($vStr, 'CYCLE TIME')) && !isset($colMap['ct_detik'])) $colMap['ct_detik'] = $j;
                    elseif (str_contains($vStr, 'PROCESS TIME') && !isset($colMap['process_time'])) $colMap['process_time'] = $j;
                    elseif ((str_contains($vStr, 'REG. ACTIVE') || str_contains($vStr, 'REG.ACTIVE')) && !isset($colMap['reg_active'])) $colMap['reg_active'] = $j;
                    elseif ($vStr === 'DCT' && !isset($colMap['dct'])) $colMap['dct'] = $j;
                    elseif ($vStr === 'MCT' && !isset($colMap['mct'])) $colMap['mct'] = $j;
                    elseif (str_contains($vStr, 'PLAN DCT') && !isset($colMap['plan_dct'])) $colMap['plan_dct'] = $j;
                    elseif ($vStr === 'TPT' && !isset($colMap['tpt'])) $colMap['tpt'] = $j;
                    elseif (str_contains($vStr, 'GSPH') && !isset($colMap['gsph_item'])) $colMap['gsph_item'] = $j;
                    elseif ($vStr === 'START' && !isset($colMap['start_time'])) $colMap['start_time'] = $j;
                    elseif ($vStr === 'FINISH' && !isset($colMap['finish_time'])) $colMap['finish_time'] = $j;
                    elseif (str_contains($vStr, 'ACT START') && !isset($colMap['act_start'])) $colMap['act_start'] = $j;
                    elseif (str_contains($vStr, 'ACT FINISH') && !isset($colMap['act_finish'])) $colMap['act_finish'] = $j;
                    elseif (str_contains($vStr, 'KETERANGAN') && !isset($colMap['keterangan'])) $colMap['keterangan'] = $j;
                    elseif (in_array($vStr, ['A-1', 'B-1', 'C-1', 'D-1']) && !isset($colMap['a1'])) $colMap['a1'] = $j;
                    elseif (in_array($vStr, ['A-2', 'B-2', 'C-2', 'D-2']) && !isset($colMap['a2'])) $colMap['a2'] = $j;
                    elseif (in_array($vStr, ['A-3', 'B-3', 'C-3', 'D-3']) && !isset($colMap['a3'])) $colMap['a3'] = $j;
                    elseif (in_array($vStr, ['A-4', 'B-4', 'C-4', 'D-4']) && !isset($colMap['a4'])) $colMap['a4'] = $j;
                    elseif (str_contains($vStr, 'DT (MENIT)') && !str_contains($vStr, 'TOTAL') && !isset($colMap['dt_menit'])) $colMap['dt_menit'] = $j;
                    elseif (str_contains($vStr, 'TOTAL PCS') && !isset($colMap['total_pcs'])) $colMap['total_pcs'] = $j;
                    elseif (str_contains($vStr, 'TPT TOTAL') && !isset($colMap['tpt_total'])) $colMap['tpt_total'] = $j;
                }
                $colMap['job_no'] = $jobNoMain !== null ? $jobNoMain : $jobNoAlt;
                break;
            }
        }

        if ($headerLocalIdx === null) return null;

        foreach ($sectionRows as $row) {
            foreach ($row as $cell) {
                if ($cell === null) continue;
                $vStr = strtoupper((string)$cell);
                if (preg_match('/^HARI\s*:/', $vStr)) {
                    $hari = trim(explode(':', (string)$cell, 2)[1] ?? '');
                }
            }
            $col2 = $row[2] ?? null;
            $col3 = $row[3] ?? null;
            if ($col2 && $col3) {
                $label = strtoupper(trim((string)$col2));
                $val = trim((string)$col3);
                if (in_array($label, ['HARI', 'HARI :', 'HARI:'])) $hari = ltrim($val, ':');
                elseif (in_array($label, ['TGL', 'TGL :', 'TGL:'])) $tgl = ltrim($val, ':');
                elseif (in_array($label, ['JAM', 'JAM :', 'JAM:'])) $jam = ltrim($val, ':');
                elseif (in_array($label, ['REVISI', 'REVISI :', 'REVISI:'])) $revisi = ltrim($val, ':');
            }
        }

        $dataStartLocal = $headerLocalIdx + 1;
        $rowsOut = [];
        $rowCounter = 0;

        $SUMMARY_KEYWORDS = ['TOTAL STROKE', 'TOTAL TPT', 'TOTAL FINISH', 'TOTAL PROD',
            'PLAN STROKE', 'GSPH TOTAL', 'GSPH ITEM', 'TARGET GSPH', 'GSPH',
            'TOTAL', 'TOTAL PCS'];

        $isSummaryRow = function ($jm, $start, $finish) use ($SUMMARY_KEYWORDS) {
            if (empty($jm)) return false;
            $jmUp = strtoupper(trim($jm));
            foreach ($SUMMARY_KEYWORDS as $kw) {
                if ($jmUp === $kw || str_starts_with($jmUp, $kw)) return true;
            }
            if ($jmUp === 'PLAN' && $start === null && $finish === null) return true;
            return false;
        };

        for ($localI = $dataStartLocal; $localI < count($sectionRows); $localI++) {
            $row = $sectionRows[$localI];
            if (empty(array_filter($row, fn($v) => $v !== null))) continue;

            $jobNoValMain = $row[$jobNoMain] ?? null;
            $jobNoValAlt = $row[$jobNoAlt] ?? null;
            $jobNoVal = $this->nonEmpty($jobNoValMain) ? $jobNoValMain : $jobNoValAlt;
            $jobMasterVal = $row[$colMap['job_master'] ?? -1] ?? null;
            $planValRaw = $row[$colMap['plan'] ?? -1] ?? null;

            $startRawEarly = $row[$colMap['start_time'] ?? -1] ?? null;
            $finishRawEarly = $row[$colMap['finish_time'] ?? -1] ?? null;
            $startStrEarly = $this->fmtTime($startRawEarly);
            $finishStrEarly = $this->fmtTime($finishRawEarly);

            $jmStr = trim((string)($jobMasterVal ?? ''));
            $jnStr = trim((string)($jobNoVal ?? ''));

            if ($isSummaryRow($jmStr, $startStrEarly, $finishStrEarly) || $isSummaryRow($jnStr, $startStrEarly, $finishStrEarly)) {
                if (str_contains(strtoupper($jmStr), 'TOTAL FINISH') || str_contains(strtoupper($jmStr), 'TOTAL FNISH')) {
                } elseif ($this->isHeaderRow($row)) {
                    break;
                }
            } else {
                if (!$jobNoVal && !$jobMasterVal) continue;
            }

            $jobNoStr = trim((string)($jobNoVal ?? ''));
            if (str_starts_with($jobNoStr, '#')) continue;

            $rowType = 'job';
            if ($isSummaryRow($jmStr, $startStrEarly, $finishStrEarly) || $isSummaryRow($jnStr, $startStrEarly, $finishStrEarly)) {
                $rowType = 'summary';
            } else {
                $checkStr = strtoupper($jnStr);
                if (empty($checkStr) && $jobMasterVal) $checkStr = strtoupper((string)$jobMasterVal);
                $checkClean = str_replace(' ', '', $checkStr);
                if (str_contains($checkClean, 'ISTIRAHAT') || str_contains($checkClean, 'CINGKORAK') || str_contains($checkClean, 'BREAKTIME') || str_contains($checkStr, 'ISHOMA')) {
                    $rowType = 'break';
                }
            }

            $colMapKeys = [
                'type_plt', 'qty_plt', 'keb_mtl', 'total_plt', 'each_part', 'ok', 'repair', 'reject',
                'total_mesin', 'ct_detik', 'process_time', 'reg_active', 'dct', 'mct', 'plan_dct',
                'tpt', 'gsph_item', 'act_start', 'act_finish', 'keterangan', 'a1', 'a2', 'a3', 'a4',
                'dt_menit', 'total_pcs', 'tpt_total',
            ];
            $vals = [];
            foreach ($colMapKeys as $k) {
                $idx = $colMap[$k] ?? null;
                $vals[$k] = ($idx !== null && isset($row[$idx])) ? $row[$idx] : null;
            }

            $rowNoVal = $row[$colMap['row_no'] ?? -1] ?? null;
            if ($rowNoVal === null || trim((string)$rowNoVal) === '') {
                $rowCounter++;
                $rowNoVal = $rowCounter;
            } else {
                $parsedRowNo = (int)((float)((string)$rowNoVal));
                if ($rowType === 'job') $rowCounter = $parsedRowNo;
                $rowNoVal = $parsedRowNo;
            }

            $currentJobMaster = trim((string)($jobMasterVal ?? ''));
            if ($rowType === 'job') {
                if (empty($currentJobMaster) && $lastJobMaster) {
                    $currentJobMaster = $lastJobMaster;
                } elseif (!empty($currentJobMaster)) {
                    $lastJobMaster = $currentJobMaster;
                }
            }

            $rowsOut[] = [
                'row_no' => (int)$rowNoVal,
                'row_type' => $rowType,
                'job_master' => $currentJobMaster,
                'type_plt' => (string)($vals['type_plt'] ?? ''),
                'qty_plt' => $this->safeF($vals['qty_plt']),
                'keb_mtl' => $this->safeF($vals['keb_mtl']),
                'total_plt' => $this->safeF($vals['total_plt']),
                'job_no' => $jobNoStr,
                'each_part' => (string)($vals['each_part'] ?? ''),
                'plan' => $this->safeF($planValRaw),
                'ok' => $this->safeF($vals['ok']),
                'repair' => $this->safeF($vals['repair']),
                'reject' => $this->safeF($vals['reject']),
                'total_mesin' => $this->safeI($vals['total_mesin']),
                'ct_detik' => $this->safeF($vals['ct_detik']),
                'process_time' => $this->safeF($vals['process_time']),
                'reg_active' => $this->safeF($vals['reg_active']),
                'dct' => $this->safeF($vals['dct']),
                'mct' => $this->safeF($vals['mct']),
                'plan_dct' => $this->safeF($vals['plan_dct']),
                'tpt' => $this->safeF($vals['tpt']),
                'gsph_item' => $this->safeF($vals['gsph_item']),
                'start_time' => $startStrEarly,
                'finish_time' => $finishStrEarly,
                'act_start' => $this->fmtTime($vals['act_start']),
                'act_finish' => $this->fmtTime($vals['act_finish']),
                'keterangan' => (string)($vals['keterangan'] ?? ''),
                'a1' => $this->safeF($vals['a1']),
                'a2' => $this->safeF($vals['a2']),
                'a3' => $this->safeF($vals['a3']),
                'a4' => $this->safeF($vals['a4']),
                'dt_menit' => $this->safeF($vals['dt_menit']),
                'total_pcs' => $this->safeF($vals['total_pcs']),
                'tpt_total' => $this->safeF($vals['tpt_total']),
            ];

            if ($rowType === 'summary' && (str_contains(strtoupper($jmStr), 'TOTAL FINISH') || str_contains(strtoupper($jmStr), 'TOTAL FNISH'))) {
                break;
            }
        }

        return [
            'shift_name' => $sheetName,
            'press_name' => $pressName,
            'hari' => $hari,
            'tgl' => $tgl,
            'jam' => $jam,
            'revisi' => $revisi,
            'rows' => $rowsOut,
            '_stats' => [
                'section_total_rows' => count($sectionRows),
                'meta_rows_skipped' => $headerLocalIdx ?? 0,
                'data_rows_output' => count($rowsOut),
                'header_local_idx' => $headerLocalIdx,
            ],
        ];
    }

    private function isHeaderRow(array $row): bool
    {
        foreach ($row as $cell) {
            if ($cell !== null && in_array(strtoupper(trim((string)$cell)), ['NO.', 'NO', 'JOB MASTER'])) {
                return true;
            }
        }
        return false;
    }

    private function fmtTime($v): ?string
    {
        if ($v === null) return null;
        if ($v instanceof \DateTimeInterface) return $v->format('H:i');
        if (is_float($v) || is_int($v)) {
            if ($v >= 0 && $v <= 1) {
                $totalMin = round($v * 24 * 60);
                return sprintf('%02d:%02d', ($totalMin / 60) % 24, $totalMin % 60);
            }
        }
        if (is_string($v)) {
            $v = trim($v);
            if (preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $v)) return substr($v, 0, 5);
        }
        return null;
    }

    private function safeF($v): float
    {
        if ($v === null) return 0.0;
        if (is_numeric($v)) return (float)$v;
        $s = trim(str_replace(',', '.', (string)$v));
        if (in_array($s, ['', '-', '#N/A', 'N/A', '#REF!', '#VALUE!'])) return 0.0;
        return (float)$s;
    }

    private function safeI($v): int
    {
        if ($v === null) return 0;
        return (int)((float)((string)$v));
    }

    private function nonEmpty($v): bool
    {
        if ($v === null) return false;
        $s = trim((string)$v);
        return !in_array($s, ['', '0', '#N/A', '#REF!', '#VALUE!']);
    }

    private function extractDate(array $resultSheets, string $originalName): string
    {
        foreach ($resultSheets as $section) {
            if (!empty($section['tgl'])) {
                $d = $this->parseDateStr($section['tgl']);
                if ($d) return $d;
            }
        }

        $d = $this->parseDateStr($originalName);
        if ($d) return $d;

        return now()->format('d M Y');
    }

    private function parseDateStr(string $text): ?string
    {
        $textUp = strtoupper($text);
        $months = [
            'JANUARI' => '01', 'FEBRUARI' => '02', 'MARET' => '03', 'APRIL' => '04',
            'MEI' => '05', 'JUNI' => '06', 'JULI' => '07', 'AGUSTUS' => '08',
            'SEPTEMBER' => '09', 'OKTOBER' => '10', 'NOVEMBER' => '11', 'DESEMBER' => '12',
        ];
        foreach ($months as $name => $num) {
            if (preg_match('/(\d{1,2})[\s\-/]+' . $name . '[\s\-/]+(\d{4})/', $textUp, $m)) {
                return sprintf('%02d', (int)$m[1]) . ' ' . $name . ' ' . $m[2];
            }
        }
        if (preg_match('/(\d{2})[-/](\d{2})[-/](\d{4})/', $textUp, $m)) {
            $monthNames = array_keys($months);
            return sprintf('%02d %s %s', (int)$m[1], $monthNames[(int)$m[2] - 1], $m[3]);
        }
        return null;
    }
}
