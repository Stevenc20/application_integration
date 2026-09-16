<?php

namespace App\Exports;

use App\Models\RundownIncoming;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RundownIncomingExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $month;
    protected $year;

    public function __construct($month, $year)
    {
        $this->month = strtoupper($month);
        $this->year  = $year;
    }

    public function collection()
    {
        return RundownIncoming::where('sheet_date', 'like', '%' . $this->month . '%')
            ->whereYear('created_at', $this->year)
            ->orderBy('sheet_date', 'asc')
            ->orderBy('no', 'asc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'DATE',
            'JOB NO',
            'JOB NO FINISH',
            'TYPE PALLET',
            'KATEGORI',
            'CUSTOMER',
            'PRICE/PC',
            'VENDOR',
            'STATUS',
            'MOVEMENT',
            'CYCLE ISSUE',
            'STOCK AWAL',
            'ASSY',
            'IAMI',
            'GKD',
            'SAP',
            'KAP',
            'GMO/TMMIN/FTI',
            'INCOMING',
            'STOK AKHIR',
            'ALL PRICE',
            'PCS/DAY',
            'STRENGTH',
        ];
    }

    public function map($row): array
    {
        return [
            $row->sheet_date,
            $row->job_no,
            $row->job_no_finish,
            $row->type_pallet,
            $row->category,
            $row->customer,
            $row->price_pc,
            $row->vendor,
            $row->status,
            $row->movement,
            $row->cycle_issue,
            $row->stock_awal,
            $row->assy,
            $row->iami,
            $row->gkd,
            $row->sap,
            $row->kap,
            $row->gmo,
            $row->incoming,
            $row->stok_akhir,
            $row->all_price,
            $row->pcs_day,
            $row->strength,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
