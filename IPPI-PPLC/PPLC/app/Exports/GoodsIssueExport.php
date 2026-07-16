<?php

namespace App\Exports;

use App\Models\GoodsIssue;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GoodsIssueExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $search;
    protected $startDate;
    protected $endDate;
    protected $locationId;

    public function __construct($search = null, $startDate = null, $endDate = null, $locationId = null)
    {
        $this->search = $search;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->locationId = $locationId;
    }

    public function collection()
    {
        $query = GoodsIssue::with(['storageLocation', 'items']);
        
        if ($this->search) {
            $query->where(function($q) {
                $q->where('no_gi', 'like', '%' . $this->search . '%')
                  ->orWhere('keterangan', 'like', '%' . $this->search . '%')
                  ->orWhereHas('storageLocation', function($locQ) {
                      $locQ->where('nama', 'like', '%' . $this->search . '%');
                  });
            });
        }

        if ($this->startDate) {
            $query->whereDate('tanggal_issue', '>=', $this->startDate);
        }

        if ($this->endDate) {
            $query->whereDate('tanggal_issue', '<=', $this->endDate);
        }

        if ($this->locationId) {
            $query->where('storage_location_id', $this->locationId);
        }

        return $query->orderBy('no_gi', 'desc')->get();
    }

    public function headings(): array
    {
        return [
            'NO. GI',
            'TANGGAL ISSUE',
            'DARI LOKASI',
            'KETERANGAN',
            'JUMLAH ITEM'
        ];
    }

    public function map($row): array
    {
        return [
            $row->no_gi,
            $row->tanggal_issue->format('d/m/Y'),
            $row->storageLocation->nama ?? '-',
            $row->keterangan ?? '-',
            $row->items->count()
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
