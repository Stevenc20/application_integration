<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExcelService
{
    /**
     * Apply header style (dark blue background, white bold text, centered)
     */
    public static function applyHeaderStyle(Spreadsheet $spreadsheet, string $range): void
    {
        $spreadsheet->getActiveSheet()->getStyle($range)->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 10],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E3A5F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFAAAAAA']]],
        ]);
    }

    /**
     * Apply data row style
     */
    public static function applyDataStyle(Spreadsheet $spreadsheet, string $range, bool $odd = true): void
    {
        $spreadsheet->getActiveSheet()->getStyle($range)->applyFromArray([
            'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $odd ? 'FFFFFFFF' : 'FFF3F6FA']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFDDDDDD']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
    }

    /**
     * Apply note/info style (yellow background)
     */
    public static function applyNoteStyle(Spreadsheet $spreadsheet, string $range): void
    {
        $spreadsheet->getActiveSheet()->getStyle($range)->applyFromArray([
            'font'      => ['italic' => true, 'color' => ['argb' => 'FF7B5300'], 'size' => 9],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFF8DC']],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFDDCC00']]],
        ]);
    }

    /**
     * Stream download response
     */
    public static function download(Spreadsheet $spreadsheet, string $filename): StreamedResponse
    {
        $writer = new Xlsx($spreadsheet);
        $response = new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        });
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', "attachment; filename=\"{$filename}\"");
        $response->headers->set('Cache-Control', 'max-age=0');
        return $response;
    }
}
