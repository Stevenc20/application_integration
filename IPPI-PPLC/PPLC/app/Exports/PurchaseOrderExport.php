<?php

namespace App\Exports;

use App\Models\PurchaseOrder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PurchaseOrderExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $search;
    protected $startDate;
    protected $endDate;
    protected $vendorId;
    protected $status;

    public function __construct($search = null, $startDate = null, $endDate = null, $vendorId = null, $status = null)
    {
        $this->search = $search;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->vendorId = $vendorId;
        $this->status = $status;
    }

    public function collection()
    {
        $query = PurchaseOrder::with(['vendor', 'items']);
        
        if ($this->search) {
            $query->where(function($q) {
                $q->where('no_po', 'like', '%' . $this->search . '%')
                  ->orWhereHas('vendor', function($vQ) {
                      $vQ->where('nama', 'like', '%' . $this->search . '%');
                  });
            });
        }

        if ($this->startDate) {
            $query->whereDate('tanggal_order', '>=', $this->startDate);
        }

        if ($this->endDate) {
            $query->whereDate('tanggal_order', '<=', $this->endDate);
        }

        if ($this->vendorId) {
            $query->where('vendor_id', $this->vendorId);
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        return $query->orderBy('no_po', 'desc')->get();
    }

    public function headings(): array
    {
        return [
            'NO. PO',
            'VENDOR',
            'TANGGAL ORDER',
            'EST. TERIMA',
            'CATATAN',
            'STATUS',
            'JUMLAH ITEM'
        ];
    }

    public function map($row): array
    {
        return [
            $row->no_po,
            $row->vendor->nama ?? '-',
            $row->tanggal_order->format('d/m/Y'),
            $row->estimasi_terima->format('d/m/Y'),
            $row->catatan ?? '-',
            $row->status,
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
