<?php

namespace App\Exports;

use App\Models\GoodsReceipt;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GoodsReceiptExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $search;
    protected $startDate;
    protected $endDate;
    protected $vendorId;
    protected $locationId;

    public function __construct($search = null, $startDate = null, $endDate = null, $vendorId = null, $locationId = null)
    {
        $this->search = $search;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->vendorId = $vendorId;
        $this->locationId = $locationId;
    }

    public function collection()
    {
        $query = GoodsReceipt::with(['purchaseOrder.vendor', 'storageLocation', 'items']);
        
        if ($this->search) {
            $query->where(function($q) {
                $q->where('no_gr', 'like', '%' . $this->search . '%')
                  ->orWhereHas('purchaseOrder', function($poQ) {
                      $poQ->where('no_po', 'like', '%' . $this->search . '%')
                          ->orWhereHas('vendor', function($vQ) {
                              $vQ->where('nama', 'like', '%' . $this->search . '%');
                          });
                  });
            });
        }

        if ($this->startDate) {
            $query->whereDate('tanggal_terima', '>=', $this->startDate);
        }

        if ($this->endDate) {
            $query->whereDate('tanggal_terima', '<=', $this->endDate);
        }

        if ($this->vendorId) {
            $query->whereHas('purchaseOrder', function($poQ) {
                $poQ->where('vendor_id', $this->vendorId);
            });
        }

        if ($this->locationId) {
            $query->where('storage_location_id', $this->locationId);
        }

        return $query->orderBy('no_gr', 'desc')->get();
    }

    public function headings(): array
    {
        return [
            'NO. GR',
            'NO. PO',
            'VENDOR',
            'TANGGAL TERIMA',
            'LOKASI',
            'STATUS',
            'JUMLAH ITEM'
        ];
    }

    public function map($row): array
    {
        return [
            $row->no_gr,
            $row->purchaseOrder->no_po ?? '-',
            $row->purchaseOrder->vendor->nama ?? '-',
            $row->tanggal_terima->format('d/m/Y'),
            $row->storageLocation->nama ?? '-',
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
