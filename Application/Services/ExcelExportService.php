<?php

declare(strict_types=1);

namespace Application\Services;

use Application\Contracts\ReportExporterInterface;
use Application\DTO\ReportData;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExcelExportService implements ReportExporterInterface
{
    private const FONT_TITLE = ['bold' => true, 'size' => 16];
    private const FONT_SUBTITLE = ['size' => 11, 'color' => ['argb' => 'FF475569']];
    private const HEADER_STYLE = [
        'font' => ['bold' => true, 'color' => ['argb' => 'FF0F172A']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE2E8F0']],
        'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCBD5E1']]],
    ];

    public function export(ReportData $data): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(mb_substr($data->title, 0, 31));

        $row = 1;
        $columnCount = max(1, count($data->headers));
        $lastColumn = Coordinate::stringFromColumnIndex($columnCount);

        // Title
        $sheet->setCellValue("A{$row}", $data->title);
        $sheet->mergeCells("A{$row}:{$lastColumn}{$row}");
        $sheet->getStyle("A{$row}")
            ->getFont()->applyFromArray(self::FONT_TITLE);
        $sheet->getStyle("A{$row}")
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        // Subtitle
        if (!empty($data->subtitle)) {
            $row++;
            $sheet->setCellValue("A{$row}", $data->subtitle);
            $sheet->mergeCells("A{$row}:{$lastColumn}{$row}");
            $sheet->getStyle("A{$row}")
                ->getFont()->applyFromArray(self::FONT_SUBTITLE);
        }

        $row += 2;

        // Headers
        $headerRow = $row;
        foreach ($data->headers as $index => $header) {
            $sheet->setCellValueByColumnAndRow($index + 1, $row, $header);
        }
        $sheet->getStyle("A{$row}:{$lastColumn}{$row}")
            ->applyFromArray(self::HEADER_STYLE);

        // Data rows
        $row++;
        if (!empty($data->rows)) {
            $sheet->fromArray($data->rows, null, "A{$row}", true);
            $lastRow = $row + count($data->rows) - 1;
        } else {
            $lastRow = $row - 1;
        }

        if ($lastRow >= $headerRow) {
            $sheet->getStyle("A{$headerRow}:{$lastColumn}{$lastRow}")
                ->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        }

        foreach (range(1, $columnCount) as $colIndex) {
            $sheet->getColumnDimensionByColumn($colIndex)->setAutoSize(true);
        }

        return $this->toBinary($spreadsheet);
    }

    private function toBinary(Spreadsheet $spreadsheet): string
    {
        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean() ?: '';
        $spreadsheet->disconnectWorksheets();

        return $content;
    }
}

