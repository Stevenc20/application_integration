<?php

namespace App\Exports;

use App\Models\BusinessEventLog;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BusinessEventLogExport implements FromQuery, WithHeadings, WithMapping, WithStyles
{
    use Exportable;

    protected $eventType;
    protected $entityType;
    protected $entityId;

    public function __construct($eventType, $entityType, $entityId)
    {
        $this->eventType = $eventType;
        $this->entityType = $entityType;
        $this->entityId = $entityId;
    }

    public function query()
    {
        $query = BusinessEventLog::query();

        if ($this->eventType !== '') {
            $query->where('event_type', 'like', "%{$this->eventType}%");
        }
        
        if ($this->entityType !== '') {
            $query->where('entity_type', 'like', "%{$this->entityType}%");
        }

        if ($this->entityId !== '') {
            $query->where('entity_id', 'like', "%{$this->entityId}%");
        }

        return $query->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            'Waktu',
            'Event',
            'Entity',
            'Entity ID',
            'User',
            'Payload',
        ];
    }

    public function map($log): array
    {
        return [
            $log->created_at ? $log->created_at->format('Y-m-d H:i:s') : '-',
            $log->event_type,
            $log->entity_type,
            $log->entity_id,
            $log->user ?? '-',
            is_array($log->payload) ? json_encode($log->payload) : $log->payload,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
