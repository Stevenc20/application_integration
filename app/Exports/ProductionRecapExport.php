<?php

namespace App\Exports;

use App\Models\DailyProduction;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ProductionRecapExport implements FromCollection, WithStyles, ShouldAutoSize
{
    protected $request;
    protected $totalRowIndex = 6;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $rows = collect();

        $type = $this->request->type ?? 'daily';

        if ($type === 'monthly') {
            $month = $this->request->month ?? now()->format('Y-m');
            $productions = DailyProduction::with('jobMaster')
                ->whereMonth('work_date', date('m', strtotime($month)))
                ->whereYear('work_date', date('Y', strtotime($month)))
                ->get();
            $dateLabel = \Carbon\Carbon::parse($month)->format('F Y');
        } elseif ($type === 'weekly') {
            $start = $this->request->start ?? now()->startOfWeek()->toDateString();
            $end   = $this->request->end   ?? now()->endOfWeek()->toDateString();
            $productions = DailyProduction::with('jobMaster')
                ->whereBetween('work_date', [$start, $end])
                ->get();
            $dateLabel = $start . ' - ' . $end;
        } else {
            $date = $this->request->date ?? now()->toDateString();
            $productions = DailyProduction::with('jobMaster')
                ->whereDate('work_date', $date)
                ->get();
            $dateLabel = \Carbon\Carbon::parse($date)->format('d F Y');
        }

        // ================= HEADER =================
        $rows->push(['PT INTI PANTJA PRESS INDUSTRI']);
        $rows->push(['PRODUCTION SUMMARY REPORT']);
        $rows->push(['Periode: ' . $dateLabel]);
        $rows->push([]);

        // ================= TABLE HEADER =================
        $rows->push([
            'No', 'Date', 'Job Name', 'Line', 'Shift', 'Target', 'OK', 'Repair', 'Reject', 'Total', 'Runtime', 'Downtime', 'Efisiensi'
        ]);

        // ================= DATA =================
        $no = 1;
        $totalOk = 0;
        $totalRepair = 0;
        $totalReject = 0;

        foreach ($productions as $p) {
            $total = (int) $p->actual_ok + (int) $p->actual_repair + (int) $p->actual_reject;

            $rows->push([
                $no++,
                \Carbon\Carbon::parse($p->work_date)->format('d/m/Y'),
                $p->jobMaster->job_name ?? $p->jobMaster->job_number ?? '-',
                $p->line ?? $p->jobMaster->line ?? '-',
                $p->shift ?? '-',
                (int) $p->target_qty,
                (int) $p->actual_ok,
                (int) $p->actual_repair,
                (int) $p->actual_reject,
                $total,
                gmdate('H:i', $p->runtime_seconds),
                gmdate('H:i', $p->downtime_seconds),
                number_format($p->efficiency, 1) . '%',
            ]);

            $totalOk += (int) $p->actual_ok;
            $totalRepair += (int) $p->actual_repair;
            $totalReject += (int) $p->actual_reject;
        }

        // ================= TOTAL =================
        $dataStartRow = 6;
        $dataEndRow = $dataStartRow + $productions->count() - 1;
        $totalRow = $dataEndRow + 1;

        $rows->push([
            '', '', '', '', '', '', 'TOTAL',
            $totalOk,
            $totalRepair,
            $totalReject,
            $totalOk + $totalRepair + $totalReject,
            '', '',
        ]);

        // ================= SIGNATURE =================
        $rows->push([]);
        $rows->push(['Prepared By', '', '', '', 'Checked By', '', '', '', '', '', '', '', 'Approved By']);
        $rows->push(['(____________)', '', '', '', '(____________)', '', '', '', '', '', '', '', '(____________)']);

        $this->totalRowIndex = $totalRow;

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        // ================= MERGE =================
        $sheet->mergeCells('A1:M1');
        $sheet->mergeCells('A2:M2');
        $sheet->mergeCells('A3:M3');

        // ================= HEADER STYLE =================
        $sheet->getStyle('A1:A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A3')->getFont()->setBold(true);

        // ================= TABLE HEADER =================
        $headerRow = 5;
        $sheet->getStyle("A{$headerRow}:M{$headerRow}")->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFF200'],
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THICK],
            ],
        ]);

        // ================= BORDER DATA =================
        $totalRow = $this->totalRowIndex ?? 6;
        $lastDataRow = $totalRow;
        if ($sheet->getHighestRow() >= 6) {
            $sheet->getStyle("A{$headerRow}:M{$lastDataRow}")->applyFromArray([
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                ],
            ]);
        }

        // ================= ALIGN =================
        $dataStartRow = $headerRow + 1;
        if ($lastDataRow >= $dataStartRow) {
            $sheet->getStyle("A{$dataStartRow}:F{$lastDataRow}")->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("G{$dataStartRow}:M{$lastDataRow}")->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        // ================= TOTAL STYLE =================
        if ($totalRow >= $dataStartRow) {
            $sheet->getStyle("F{$totalRow}:M{$totalRow}")->applyFromArray([
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'D9D9D9'],
                ],
            ]);
        }

        // ================= COLUMN WIDTH =================
        $sheet->getColumnDimension('B')->setWidth(18);
        $sheet->getColumnDimension('C')->setWidth(18);
        $sheet->getColumnDimension('D')->setWidth(12);
        $sheet->getColumnDimension('E')->setWidth(15);

        return [];
    }
}
