<?php

namespace App\Exports;

use App\Models\StorageLocation;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StorageLocationExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $search;

    public function __construct($search = null)
    {
        $this->search = $search;
    }

    public function collection()
    {
        $query = StorageLocation::query();
        
        if ($this->search) {
            $query->where(function($q) {
                $q->where('kode', 'like', '%' . $this->search . '%')
                  ->orWhere('nama', 'like', '%' . $this->search . '%')
                  ->orWhere('deskripsi', 'like', '%' . $this->search . '%');
            });
        }

        return $query->orderBy('kode', 'asc')->get();
    }

    public function headings(): array
    {
        return [
            'KODE',
            'NAMA LOKASI',
            'DESKRIPSI',
            'TIPE MATERIAL',
            'SCRAP'
        ];
    }

    public function map($row): array
    {
        return [
            $row->kode,
            $row->nama,
            $row->deskripsi ?? '-',
            $row->tipe_material,
            $row->is_scrap ? 'Scrap' : 'Bukan Scrap'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
