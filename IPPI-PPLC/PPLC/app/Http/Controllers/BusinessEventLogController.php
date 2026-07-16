<?php

namespace App\Http\Controllers;

use App\Models\BusinessEventLog;
use App\Services\ExcelService;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class BusinessEventLogController extends Controller
{
    public function index(Request $request)
    {
        $query = $this->buildFilteredQuery($request);

        $logs = $query->latest()->paginate(30)->withQueryString();

        $eventType = trim($request->get('event_type', ''));
        $entityType = trim($request->get('entity_type', ''));
        $entityId = trim($request->get('entity_id', ''));

        return view('business_logs.index', compact('logs', 'eventType', 'entityType', 'entityId'));
    }

    public function exportExcel(Request $request)
    {
        $logs = $this->buildFilteredQuery($request)
            ->latest()
            ->limit(5000)
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Business Logs');

        $headers = ['Timestamp', 'Event Type', 'Entity Type', 'Entity ID', 'User', 'Payload'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }

        $row = 2;
        foreach ($logs as $log) {
            $sheet->setCellValue('A' . $row, $log->created_at?->format('Y-m-d H:i:s'));
            $sheet->setCellValue('B' . $row, $log->event_type);
            $sheet->setCellValue('C' . $row, $log->entity_type);
            $sheet->setCellValue('D' . $row, $log->entity_id);
            
            // Get user display: relationship or fallback to string column
            $userName = '-';
            if ($log->relationLoaded('user') && $log->user) {
                $userName = $log->user->name;
            } elseif (isset($log->attributes['user'])) {
                $userName = $log->attributes['user'];
            }
            $sheet->setCellValue('E' . $row, $userName);
            
            $sheet->setCellValue('F' . $row, json_encode($log->payload, JSON_UNESCAPED_UNICODE));
            $row++;
        }

        ExcelService::applyHeaderStyle($spreadsheet, 'A1:F1');
        foreach (range('A', 'F') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        if ($row > 2) {
            for ($dataRow = 2; $dataRow < $row; $dataRow++) {
                ExcelService::applyDataStyle($spreadsheet, 'A' . $dataRow . ':F' . $dataRow, $dataRow % 2 !== 0);
            }
        }

        $filename = 'business-event-logs-' . now()->format('Ymd-His') . '.xlsx';

        return ExcelService::download($spreadsheet, $filename);
    }

    private function buildFilteredQuery(Request $request)
    {
        $query = BusinessEventLog::with('user');

        if ($request->event_type) {
            $query->where('event_type', 'like', '%' . $request->event_type . '%');
        }

        if ($request->entity_type) {
            $query->where('entity_type', 'like', '%' . $request->entity_type . '%');
        }

        if ($request->entity_id) {
            $query->where('entity_id', $request->entity_id);
        }

        return $query;
    }
}
