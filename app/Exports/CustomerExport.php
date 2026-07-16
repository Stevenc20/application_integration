<?php

namespace App\Exports;

use App\Models\Customer;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CustomerExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $search;

    public function __construct($search = null)
    {
        $this->search = $search;
    }

    public function collection()
    {
        $query = Customer::query();
        
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
            'KODE',
            'NAMA CUSTOMER',
            'CONTACT PERSON',
            'EMAIL',
            'TELEPON',
            'STATUS'
        ];
    }

    public function map($row): array
    {
        return [
            $row->kode,
            $row->nama,
            $row->kontak ?? '-',
            $row->email ?? '-',
            $row->telepon ?? '-',
            $row->status
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
