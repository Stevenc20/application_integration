<?php

namespace App\Exports;

use App\Models\Material;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MaterialExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $search;
    protected $tipe;

    public function __construct($search = null, $tipe = null)
    {
        $this->search = $search;
        $this->tipe = $tipe;
    }

    public function collection()
    {
        $query = Material::query();
        
        if ($this->search) {
            $query->where(function($q) {
                $q->where('kode', 'like', '%' . $this->search . '%')
                  ->orWhere('nama', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->tipe && $this->tipe !== '') {
            $query->where('tipe', $this->tipe);
        }

        return $query->orderBy('kode', 'asc')->get();
    }

    public function headings(): array
    {
        return [
            'Kode',
            'Nama',
            'Deskripsi',
            'Tipe',
            'Satuan',
            'Harga Standar',
            'Qty/Case',
            'Min Stock',
            'Stok Saat Ini',
            'Aktif'
        ];
    }

    public function map($row): array
    {
        return [
            $row->kode,
            $row->nama,
            $row->nama, // Using nama as deskripsi for now based on data sample
            $row->tipe,
            $row->uom,
            0, // Harga standar defaults to 0
            $row->qty_case,
            $row->min_stok,
            $row->stok,
            $row->status === 'Aktif' ? 'Ya' : 'Tidak'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF1E293B'] // Dark blue background
                ]
            ],
        ];
    }
}
