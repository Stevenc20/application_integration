<?php

namespace App\Exports;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class ProductionRecapExport implements FromCollection, WithStyles, ShouldAutoSize
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $rows = collect();

        // ================= HEADER =================
        $rows->push(['PT INTI PANTJA PRESS INDUSTRI']);
        $rows->push(['PRODUCTION SUMMARY REPORT']);
        $rows->push(['Tanggal: ' . now()->format('d F Y')]);
        $rows->push([]);

        // ================= INFO =================
        $rows->push(['Department', ': Production']);
        $rows->push(['Supervisor', ': John Doe']);
        $rows->push(['Manager', ': Manager Name']);
        $rows->push([]);

        // ================= TABLE HEADER =================
        $rows->push([
            'No','Order','Job Name','Line','Process','Shift','OK','Repair','Reject','Total'
        ]);

        // ================= DATA =================
        $dummy = [
            ['PO-001','JOB A','Line 1','Assembly','Shift 1',120,5,3],
            ['PO-002','JOB B','Line 2','Welding','Shift 2',90,2,1],
            ['PO-003','JOB C','Line 3','Painting','Shift 1',150,4,2],
        ];

        $no = 1;
        $totalOk = 0;
        $totalRepair = 0;
        $totalReject = 0;

        foreach ($dummy as $d) {

            $total = $d[5] + $d[6] + $d[7];

            $rows->push([
                $no++,
                $d[0],
                $d[1],
                $d[2],
                $d[3],
                $d[4],
                $d[5],
                $d[6],
                $d[7],
                $total
            ]);

            $totalOk += $d[5];
            $totalRepair += $d[6];
            $totalReject += $d[7];
        }

        // ================= TOTAL =================
        $rows->push([
            '',
            '',
            '',
            '',
            '',
            'TOTAL',
            $totalOk,
            $totalRepair,
            $totalReject,
            $totalOk + $totalRepair + $totalReject
        ]);

        // ================= SIGNATURE =================
        $rows->push([]);
        $rows->push(['Prepared By', '', '', '', 'Checked By', '', '', '', 'Approved By']);
        $rows->push(['(____________)', '', '', '', '(____________)', '', '', '', '(____________)']);

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        // ================= MERGE =================
        $sheet->mergeCells('A1:J1');
        $sheet->mergeCells('A2:J2');
        $sheet->mergeCells('A3:J3');

        // ================= HEADER STYLE =================
        $sheet->getStyle('A1:A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A3')->getFont()->setBold(true);

        // ================= TABLE HEADER =================
        $sheet->getStyle('A9:J9')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFF200']
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THICK]
            ]
        ]);

        // ================= BORDER TABLE =================
        $sheet->getStyle('A9:J12')->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN]
            ]
        ]);

        // ================= ALIGN =================
        $sheet->getStyle('A10:F12')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('G10:J12')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // ================= TOTAL STYLE =================
        $sheet->getStyle('F12:J12')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'D9D9D9']
            ]
        ]);

        // ================= COLUMN WIDTH =================
        $sheet->getColumnDimension('B')->setWidth(18);
        $sheet->getColumnDimension('C')->setWidth(18);
        $sheet->getColumnDimension('D')->setWidth(12);
        $sheet->getColumnDimension('E')->setWidth(15);

        return [];
    }

    // ================= LOGO =================
    public function drawings()
    {
        $drawing = new Drawing();
        $drawing->setPath(public_path('logo.png')); // taro logo disini
        $drawing->setHeight(50);
        $drawing->setCoordinates('A1');

        return $drawing;
    }
}