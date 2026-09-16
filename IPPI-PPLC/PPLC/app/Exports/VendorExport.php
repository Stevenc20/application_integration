<?php

namespace App\Exports;

use App\Models\Vendor;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class VendorExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $search;

    public function __construct($search = null)
    {
        $this->search = $search;
    }

    public function collection()
    {
        $query = Vendor::query();
        if ($this->search) {
            $query->where(function($q) {
                $q->where('kode', 'like', '%' . $this->search . '%')
                  ->orWhere('nama', 'like', '%' . $this->search . '%');
            });
        }
        return $query->orderBy('kode', 'asc')->get();
    }

    public function headings(): array
    {
        return [
            'Kode',
            'Nama',
            'Tipe Vendor',
            'Contact Person',
            'Email',
            'Telepon',
            'Alamat',
            'Aktif'
        ];
    }

    public function map($row): array
    {
        return [
            $row->kode,
            $row->nama,
            $row->tipe,
            $row->kontak ?? '-',
            $row->email ?? '-',
            $row->telepon ?? '-',
            $row->alamat ?? '-',
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
                    'startColor' => ['argb' => 'FF1E293B']
                ]
            ],
        ];
    }
}
