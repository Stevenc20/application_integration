<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class LkhActualExport
{
    protected array $jobsData;
    protected $totals;
    protected $summary;
    protected string $lineName;
    protected string $shiftName;
    protected string $date;
    protected array $signatureStatus;
    protected $shiftDisplayStart;
    protected $shiftDisplayEnd;

    const COLUMNS = 34;
    const RED = 'FF991B1B';
    const WHITE = 'FFFFFFFF';
    const LIGHT_GRAY = 'FFF9FAFB';
    const MED_GRAY = 'FFF1F5F9';
    const DARK_BORDER = 'FF991B1B';
    const THIN_BORDER = 'FFE5E7EB';

    public function __construct(
        array $jobsData,
        $totals,
        $summary,
        string $lineName,
        string $shiftName,
        string $date,
        array $signatureStatus,
        $shiftDisplayStart = '07:00',
        $shiftDisplayEnd = '21:00'
    ) {
        $this->jobsData = $jobsData;
        $this->totals = $totals;
        $this->summary = $summary;
        $this->lineName = $lineName;
        $this->shiftName = $shiftName;
        $this->date = $date;
        $this->signatureStatus = $signatureStatus;
        $this->shiftDisplayStart = $shiftDisplayStart;
        $this->shiftDisplayEnd = $shiftDisplayEnd;
    }

    public function download()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Actual Lapangan');

        $this->buildHeader($sheet);
        $this->buildTableHeaders($sheet);
        $dataStartRow = $this->buildDataRows($sheet);
        $totalRow = $this->buildTotalRow($sheet, $dataStartRow);
        $this->buildSignatureSection($sheet, $totalRow);
        $this->applyColumnWidths($sheet);

        $filename = 'LKH_Actual_Lapangan_'
            . str_replace('-', '', $this->date) . '_'
            . preg_replace('/[^A-Za-z0-9]/', '', $this->lineName) . '.xlsx';

        $writer = new Xlsx($spreadsheet);
        $tempPath = tempnam(sys_get_temp_dir(), 'LKH_');
        $writer->save($tempPath);

        return response()->download($tempPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    protected function buildHeader($sheet): void
    {
        $lastCol = $this->colLetter(self::COLUMNS);

        // Row 1: Company name
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->setCellValue('A1', 'PT INTI PANTJA PRESS INDUSTRI');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'name' => 'Calibri'],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);

        // Row 2: Report title
        $sheet->mergeCells("A2:{$lastCol}2");
        $sheet->setCellValue('A2', 'LAPORAN KERJA HARIAN STAMPING - ACTUAL LAPANGAN');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'name' => 'Calibri'],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(22);

        // Row 3: Info
        $sheet->mergeCells("A3:{$lastCol}3");
        $dateFormatted = \Carbon\Carbon::parse($this->date)->format('d/m/Y');
        $sheet->setCellValue('A3', "Line: {$this->lineName}  |  Shift: {$this->shiftName}  |  Tanggal: {$dateFormatted}");
        $sheet->getStyle('A3')->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'name' => 'Calibri'],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(3)->setRowHeight(20);

        // Row 4: spacer
        $sheet->getRowDimension(4)->setRowHeight(6);
    }

    protected function buildTableHeaders($sheet): void
    {
        // Row 5: Group headers (merged)
        $groupHeaders = [
            ['label' => 'Schedule', 'colStart' => 1, 'colEnd' => 12],
            ['label' => 'CT Actual', 'colStart' => 13, 'colEnd' => 14],
            ['label' => 'Press Time', 'colStart' => 15, 'colEnd' => 15],
            ['label' => 'Uchi Dandori', 'colStart' => 16, 'colEnd' => 18],
            ['label' => 'Down Time', 'colStart' => 19, 'colEnd' => 24],
            ['label' => 'TPT', 'colStart' => 25, 'colEnd' => 26],
            ['label' => 'Break', 'colStart' => 27, 'colEnd' => 28],
            ['label' => 'Work Time', 'colStart' => 29, 'colEnd' => 29],
            ['label' => 'Quality Rate', 'colStart' => 30, 'colEnd' => 32],
            ['label' => 'OEE', 'colStart' => 33, 'colEnd' => 33],
            ['label' => 'GSPH', 'colStart' => 34, 'colEnd' => 34],
        ];

        $groupStyle = [
            'font' => ['bold' => true, 'size' => 8, 'color' => ['rgb' => 'FFFFFF'], 'name' => 'Calibri'],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '991B1B']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '7A1414']]],
        ];

        foreach ($groupHeaders as $group) {
            $start = $this->colLetter($group['colStart']);
            $end = $this->colLetter($group['colEnd']);
            if ($group['colStart'] !== $group['colEnd']) {
                $sheet->mergeCells("{$start}5:{$end}5");
            }
            $sheet->setCellValue("{$start}5", $group['label']);
            $sheet->getStyle("{$start}5")->applyFromArray($groupStyle);
            // Fill merged range
            $sheet->getStyle("{$start}5:{$end}5")->applyFromArray($groupStyle);
        }
        $sheet->getRowDimension(5)->setRowHeight(28);

        // Row 6: Column headers
        $colHeaders = [
            1 => 'No',
            2 => 'Job No',
            3 => 'Plan Qty',
            4 => 'Act Qty',
            5 => 'Good',
            6 => 'Repair',
            7 => 'Reject',
            8 => 'Stroke',
            9 => 'PL Start',
            10 => 'PL Fin',
            11 => 'Act Start',
            12 => 'Act Fin',
            13 => 'CT Record',
            14 => 'CT LKH',
            15 => 'Press Time',
            16 => 'Dies & Var',
            17 => '1st-Q Ck',
            18 => 'Dan (min)',
            19 => 'Dies',
            20 => 'Machine',
            21 => 'Material',
            22 => 'Log',
            23 => 'Production',
            24 => 'Total',
            25 => 'Plan',
            26 => 'Actual',
            27 => 'Type',
            28 => 'Time',
            29 => 'Work Time',
            30 => 'Pass%',
            31 => 'Rep%',
            32 => 'Rej%',
            33 => 'OEE (%)',
            34 => 'GSPH',
        ];

        $colHeaderStyle = [
            'font' => ['bold' => true, 'size' => 7, 'color' => ['rgb' => 'FFFFFF'], 'name' => 'Calibri'],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '991B1B']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '7A1414']]],
        ];

        foreach ($colHeaders as $col => $label) {
            $letter = $this->colLetter($col);
            $sheet->setCellValue("{$letter}6", $label);
            $sheet->getStyle("{$letter}6")->applyFromArray($colHeaderStyle);
        }
        $sheet->getRowDimension(6)->setRowHeight(32);

        // Freeze panes below headers
        $sheet->freezePane('A7');
    }

    protected function buildDataRows($sheet): int
    {
        $row = 7;
        $jobRows = collect($this->jobsData)->where('row_type', '!=', 'break')->values();
        $lastJobFinish = null;

        foreach ($this->jobsData as $j) {
            if (($j['row_type'] ?? 'job') === 'job' && ($j['schedule_finish'] ?? null)) {
                $lastJobFinish = $j['schedule_finish'];
            }
        }

        $dataStyle = [
            'font' => ['size' => 8, 'name' => 'Calibri'],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E5E7EB']]],
        ];

        $altColor = 'FFF9FAFB';

        $idx = 0;
        foreach ($this->jobsData as $job) {
            if (($job['row_type'] ?? 'job') === 'break') {
                continue;
            }

            $actGood = $job['actual_good'] ?? 0;
            $actRepair = $job['actual_repair'] ?? 0;
            $actReject = $job['actual_reject'] ?? 0;
            $totalStroke = $actGood + $actRepair + $actReject;
            $ctActual = $job['act_ct'] ?? 0;
            $ctRecord = $job['plan_ct'] ?? 0;
            $procAct = $job['press_time'] ?? $job['process_time'] ?? 0;
            $dctActual = $job['dandori_time'] ?? 0;
            $tptActual = $job['tpt_act'] ?? 0;
            $tptPlan = $job['tpt_plan'] ?? 0;
            $breakTime = $job['break_time_duration'] ?? 0;
            $workTime = max(0, $tptActual + $breakTime);
            $passRate = $totalStroke > 0 ? round($actGood / $totalStroke * 100, 1) : 0;
            $repairRate = $totalStroke > 0 ? round($actRepair / $totalStroke * 100, 1) : 0;
            $rejectRate = $totalStroke > 0 ? round($actReject / $totalStroke * 100, 1) : 0;
            $oee = $job['oee'] ?? 0;
            $gsphActual = $job['gsph'] ?? 0;

            $planStart = $job['schedule_start'] ?? null;
            $planFinish = $job['schedule_finish'] ?? null;
            if ($planFinish && $lastJobFinish && $planFinish->eq($lastJobFinish)) {
                $planFinish = $this->shiftDisplayEnd;
            }
            $actStart = $job['actual_start'] ?? null;
            $actFinish = $job['actual_finish'] ?? null;

            $dtDies = $job['dt_breakdown']['dies_t'] ?? 0;
            $dtMach = $job['dt_breakdown']['mach_t'] ?? 0;
            $dtMatl = $job['dt_breakdown']['mat_t'] ?? 0;
            $dtLog = $job['dt_breakdown']['log_t'] ?? 0;
            $dtProd = $job['dt_breakdown']['prod_t'] ?? 0;
            $dtTotal = $job['dt_total'] ?? 0;

            $timeCell = fn($dt) => $dt ? (is_string($dt) ? $dt : $dt->format('H:i')) : '-';

            $values = [
                1 => $job['display_no'] ?? ($idx + 1),
                2 => $job['job_master'] ?? '-',
                3 => $job['plan_qty'] ?? 0,
                4 => $totalStroke,
                5 => $actGood,
                6 => $actRepair,
                7 => $actReject,
                8 => $totalStroke,
                9 => $timeCell($planStart),
                10 => $timeCell($planFinish),
                11 => $timeCell($actStart),
                12 => $timeCell($actFinish),
                13 => $ctRecord,
                14 => $ctActual,
                15 => $procAct,
                16 => $job['dies_variant_time'] ?? 0,
                17 => $job['qcheck_time'] ?? 0,
                18 => $dctActual,
                19 => $dtDies,
                20 => $dtMach,
                21 => $dtMatl,
                22 => $dtLog,
                23 => $dtProd,
                24 => $dtTotal,
                25 => $tptPlan,
                26 => $tptActual,
                27 => $breakTime > 0 ? 'BREAK' : '-',
                28 => $breakTime,
                29 => $workTime,
                30 => $passRate,
                31 => $repairRate,
                32 => $rejectRate,
                33 => $oee,
                34 => $gsphActual,
            ];

            $isAlt = $idx % 2 === 1;

            foreach ($values as $col => $val) {
                $letter = $this->colLetter($col);
                $sheet->setCellValue("{$letter}{$row}", $val);
                $sheet->getStyle("{$letter}{$row}")->applyFromArray($dataStyle);

                if ($isAlt) {
                    $sheet->getStyle("{$letter}{$row}")->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setARGB($altColor);
                }
            }

            // Style job no & number columns left-aligned
            $sheet->getStyle("B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

            $row++;
            $idx++;
        }

        return $row; // next row after data
    }

    protected function buildTotalRow($sheet, int $dataStartRow): int
    {
        $actRows = collect($this->jobsData)->where('row_type', 'job');
        $tActGood = $actRows->sum('actual_good');
        $tActRepair = $actRows->sum('actual_repair');
        $tActReject = $actRows->sum('actual_reject');
        $tActStroke = $tActGood + $tActRepair + $tActReject;
        $tActPlan = $actRows->sum('plan_qty');
        $tActProc = $actRows->sum('press_time');
        $tActDct = $actRows->sum('dandori_time');
        $tActQcheck = $actRows->sum('qcheck_time');
        $tActTptPlan = $actRows->sum('tpt_plan');
        $tActTpt = $actRows->sum('tpt_act');
        $tActBreak = $actRows->sum('break_time_duration');
        $tActWork = $tActTpt + $tActBreak;
        $tDtDies = $actRows->sum(fn($r) => $r['dt_breakdown']['dies_t'] ?? 0);
        $tDtMach = $actRows->sum(fn($r) => $r['dt_breakdown']['mach_t'] ?? 0);
        $tDtMatl = $actRows->sum(fn($r) => $r['dt_breakdown']['mat_t'] ?? 0);
        $tDtLog = $actRows->sum(fn($r) => $r['dt_breakdown']['log_t'] ?? 0);
        $tDtProd = $actRows->sum(fn($r) => $r['dt_breakdown']['prod_t'] ?? 0);
        $tDtTotal = $actRows->sum(fn($r) => $r['dt_total'] ?? 0);
        $tPassRate = $tActStroke > 0 ? round($tActGood / $tActStroke * 100, 1) : 0;
        $tRepRate = $tActStroke > 0 ? round($tActRepair / $tActStroke * 100, 1) : 0;
        $tRejRate = $tActStroke > 0 ? round($tActReject / $tActStroke * 100, 1) : 0;
        $tOee = $this->totals['weighted_oee'] ?? 0;
        $tGsph = $this->totals['weighted_gsph'] ?? 0;

        $totalValues = [
            1 => '',
            2 => 'TOTAL SHIFT',
            3 => $tActPlan,
            4 => $tActStroke,
            5 => $tActGood,
            6 => $tActRepair,
            7 => $tActReject,
            8 => $tActStroke,
            9 => '', 10 => '', 11 => '', 12 => '',
            13 => '', 14 => '',
            15 => (int) ceil($tActProc),
            16 => $actRows->sum('dies_variant_time'),
            17 => $tActQcheck,
            18 => $tActDct,
            19 => $tDtDies,
            20 => $tDtMach,
            21 => $tDtMatl,
            22 => $tDtLog,
            23 => $tDtProd,
            24 => $tDtTotal,
            25 => $tActTptPlan,
            26 => $tActTpt,
            27 => '',
            28 => $tActBreak,
            29 => $tActWork,
            30 => $tPassRate,
            31 => $tRepRate,
            32 => $tRejRate,
            33 => $tOee,
            34 => $tGsph,
        ];

        $totalRowStyle = [
            'font' => ['bold' => true, 'size' => 8, 'name' => 'Calibri'],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFE4E6']],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '991B1B']],
                'top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '991B1B']],
            ],
        ];

        $row = $dataStartRow;
        foreach ($totalValues as $col => $val) {
            $letter = $this->colLetter($col);
            $sheet->setCellValue("{$letter}{$row}", $val);
            $sheet->getStyle("{$letter}{$row}")->applyFromArray($totalRowStyle);
        }
        $sheet->getStyle("B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getRowDimension($row)->setRowHeight(20);

        return $row + 1;
    }

    protected function buildSignatureSection($sheet, int $startRow): void
    {
        $lastCol = $this->colLetter(self::COLUMNS);

        // Spacer
        $startRow++;

        // Signature header
        $sheet->mergeCells("A{$startRow}:{$lastCol}{$startRow}");
        $sheet->setCellValue("A{$startRow}", 'SIGNATURE / TANDA TANGAN');
        $sheet->getStyle("A{$startRow}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'name' => 'Calibri'],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F5F9']],
        ]);
        $sheet->getRowDimension($startRow)->setRowHeight(24);
        $startRow++;

        // Signatures in columns A-D, F-I, K-N
        $sigColumns = [
            ['role' => 'supervisor', 'label' => 'Supervisor', 'colStart' => 1, 'colEnd' => 4],
            ['role' => 'foreman', 'label' => 'Foreman', 'colStart' => 6, 'colEnd' => 9],
            ['role' => 'teamleader', 'label' => 'Team Leader', 'colStart' => 11, 'colEnd' => 14],
        ];

        $labelStyle = [
            'font' => ['bold' => true, 'size' => 10, 'name' => 'Calibri'],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ];

        $subLabelStyle = [
            'font' => ['size' => 7, 'name' => 'Calibri', 'italic' => true, 'color' => ['rgb' => '9CA3AF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ];

        $signBorderStyle = [
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '374151']]],
        ];

        $nameStyle = [
            'font' => ['size' => 10, 'name' => 'Calibri'],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '374151']]],
        ];

        // Role labels
        foreach ($sigColumns as $sig) {
            $startLetter = $this->colLetter($sig['colStart']);
            $endLetter = $this->colLetter($sig['colEnd']);
            $sheet->mergeCells("{$startLetter}{$startRow}:{$endLetter}{$startRow}");
            $sheet->setCellValue("{$startLetter}{$startRow}", $sig['label']);
            $sheet->getStyle("{$startLetter}{$startRow}")->applyFromArray($labelStyle);
        }
        $startRow++;

        // Tall empty signature row for manual pen signing
        foreach ($sigColumns as $sig) {
            $startLetter = $this->colLetter($sig['colStart']);
            $endLetter = $this->colLetter($sig['colEnd']);
            $sheet->mergeCells("{$startLetter}{$startRow}:{$endLetter}{$startRow}");
            $sheet->setCellValue("{$startLetter}{$startRow}", '');
            $sheet->getStyle("{$startLetter}{$startRow}")->applyFromArray($signBorderStyle);
        }
        $sheet->getRowDimension($startRow)->setRowHeight(50);
        $startRow++;

        // "Tanda Tangan" label
        foreach ($sigColumns as $sig) {
            $startLetter = $this->colLetter($sig['colStart']);
            $endLetter = $this->colLetter($sig['colEnd']);
            $sheet->mergeCells("{$startLetter}{$startRow}:{$endLetter}{$startRow}");
            $sheet->setCellValue("{$startLetter}{$startRow}", '(Tanda Tangan)');
            $sheet->getStyle("{$startLetter}{$startRow}")->applyFromArray($subLabelStyle);
        }
        $startRow++;

        // Name lines (with underline)
        foreach ($sigColumns as $sig) {
            $roleKey = $sig['role'];
            $sigState = $this->signatureStatus[$roleKey] ?? ['signed' => false, 'name' => ''];
            $sigName = $sigState['name'] ?? '(___________________)';

            $startLetter = $this->colLetter($sig['colStart']);
            $endLetter = $this->colLetter($sig['colEnd']);
            $sheet->mergeCells("{$startLetter}{$startRow}:{$endLetter}{$startRow}");
            $sheet->setCellValue("{$startLetter}{$startRow}", $sigName);
            $sheet->getStyle("{$startLetter}{$startRow}")->applyFromArray($nameStyle);
        }
        $startRow++;

        // "Nama Terang" label
        foreach ($sigColumns as $sig) {
            $startLetter = $this->colLetter($sig['colStart']);
            $endLetter = $this->colLetter($sig['colEnd']);
            $sheet->mergeCells("{$startLetter}{$startRow}:{$endLetter}{$startRow}");
            $sheet->setCellValue("{$startLetter}{$startRow}", '(Nama Terang)');
            $sheet->getStyle("{$startLetter}{$startRow}")->applyFromArray($subLabelStyle);
        }

        // Footer note
        $footerRow = $startRow + 2;
        $sheet->mergeCells("A{$footerRow}:{$lastCol}{$footerRow}");
        $sheet->setCellValue("A{$footerRow}", 'Dicetak otomatis dari sistem ' . now()->format('d/m/Y H:i:s'));
        $sheet->getStyle("A{$footerRow}")->applyFromArray([
            'font' => ['size' => 8, 'name' => 'Calibri', 'italic' => true, 'color' => ['rgb' => '9CA3AF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
    }

    protected function applyColumnWidths($sheet): void
    {
        // Default widths for all columns
        $widths = [
            1 => 5,    // No
            2 => 20,   // Job No
            3 => 9,    // Plan Qty
            4 => 9,    // Act Qty
            5 => 9,    // Good
            6 => 8,    // Repair
            7 => 8,    // Reject
            8 => 8,    // Stroke
            9 => 9,    // PL Start
            10 => 9,   // PL Fin
            11 => 9,   // Act Start
            12 => 9,   // Act Fin
            13 => 8,   // CT Record
            14 => 8,   // CT LKH
            15 => 9,   // Press Time
            16 => 9,   // Dies & Var
            17 => 8,   // 1st-Q Ck
            18 => 8,   // Dan (min)
            19 => 7,   // Dies
            20 => 8,   // Machine
            21 => 8,   // Material
            22 => 7,   // Log
            23 => 9,   // Prod Handl
            24 => 7,   // Total
            25 => 7,   // Plan (TPT)
            26 => 7,   // Actual (TPT)
            27 => 7,   // Type (Break)
            28 => 7,   // Time (Break)
            29 => 9,   // Work Time
            30 => 7,   // Pass%
            31 => 7,   // Rep%
            32 => 7,   // Rej%
            33 => 8,   // OEE (%)
            34 => 8,   // GSPH
        ];

        foreach ($widths as $col => $width) {
            $sheet->getColumnDimension($this->colLetter($col))->setWidth($width);
        }
    }

    protected function colLetter(int $index): string
    {
        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index);
    }
}
