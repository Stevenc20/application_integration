<?php

namespace App\Exports;

use App\Models\MaterialStock;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MaterialStockExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $search;
    protected $locationId;
    protected $status;
    protected $minStockOnly;

    public function __construct($search = null, $locationId = null, $status = null, $minStockOnly = false)
    {
        $this->search = $search;
        $this->locationId = $locationId;
        $this->status = $status;
        $this->minStockOnly = $minStockOnly;
    }

    public function collection()
    {
        $query = MaterialStock::with(['material', 'storageLocation']);

        if ($this->search) {
            $query->whereHas('material', function($q) {
                $q->where('kode', 'like', '%' . $this->search . '%')
                  ->orWhere('nama', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->locationId && $this->locationId !== 'Semua Lokasi') {
            $query->where('storage_location_id', $this->locationId);
        }

        // Apply status and min stock filters dynamically
        $stocks = $query->get();

        if ($this->status && $this->status !== 'Semua Status') {
            $stocks = $stocks->filter(function($row) {
                $qty = $row->qty;
                $min = $row->material->min_stok ?? 0;
                
                if ($qty <= 0) {
                    $rowStatus = 'Habis';
                } elseif ($qty <= $min) {
                    $rowStatus = 'Rendah';
                } else {
                    $rowStatus = 'Normal';
                }
                
                return $rowStatus === $this->status;
            });
        }

        if ($this->minStockOnly) {
            $stocks = $stocks->filter(function($row) {
                $qty = $row->qty;
                $min = $row->material->min_stok ?? 0;
                return $qty <= $min;
            });
        }

        return $stocks;
    }

    public function headings(): array
    {
        return [
            'KODE',
            'NAMA MATERIAL',
            'TIPE',
            'LOKASI',
            'QTY STOK',
            'STOK DI VENDOR',
            'UOM',
            'MIN. STOK',
            'STATUS'
        ];
    }

    public function map($row): array
    {
        $qty = $row->qty;
        $min = $row->material->min_stok ?? 0;
        
        if ($qty <= 0) {
            $status = 'Habis';
        } elseif ($qty <= $min) {
            $status = 'Rendah';
        } else {
            $status = 'Normal';
        }

        return [
            $row->material->kode ?? '-',
            $row->material->nama ?? '-',
            $row->material->tipe ?? '-',
            $row->storageLocation->nama ?? '-',
            $qty,
            $row->qty_vendor > 0 ? $row->qty_vendor : '-',
            $row->material->uom ?? '-',
            $min,
            $status
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
