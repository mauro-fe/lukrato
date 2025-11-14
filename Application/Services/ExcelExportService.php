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
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExcelExportService implements ReportExporterInterface
{
    // Cores do Design System
    private const COLOR_PRIMARY = 'FFE67E22';      // Laranja principal
    private const COLOR_SECONDARY = 'FF2C3E50';    // Azul noite
    private const COLOR_TEXT = 'FF1E293B';         // Texto principal
    private const COLOR_TEXT_MUTED = 'FF475569';   // Texto secundário
    private const COLOR_BG = 'FFE6F0FA';           // Fundo azul claro
    private const COLOR_SURFACE = 'FFF0F6FC';      // Surface cards
    private const COLOR_SURFACE_MUTED = 'FFD9E6F2'; // Blocos auxiliares
    private const COLOR_BORDER = 'FFCBD5E1';       // Bordas

    // Estilos baseados no Design System
    private const FONT_TITLE = [
        'bold' => true, 
        'size' => 18,
        'color' => ['argb' => self::COLOR_SECONDARY]
    ];

    private const FONT_SUBTITLE = [
        'size' => 12, 
        'color' => ['argb' => self::COLOR_TEXT_MUTED],
        'italic' => true
    ];

    private const HEADER_STYLE = [
        'font' => [
            'bold' => true, 
            'size' => 11,
            'color' => ['argb' => self::COLOR_SECONDARY]
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_LEFT,
            'vertical' => Alignment::VERTICAL_CENTER
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID, 
            'startColor' => ['argb' => self::COLOR_SURFACE_MUTED]
        ],
        'borders' => [
            'bottom' => [
                'borderStyle' => Border::BORDER_MEDIUM, 
                'color' => ['argb' => self::COLOR_SECONDARY]
            ]
        ],
    ];

    private const DATA_ROW_STYLE = [
        'alignment' => [
            'vertical' => Alignment::VERTICAL_CENTER
        ],
        'borders' => [
            'bottom' => [
                'borderStyle' => Border::BORDER_THIN, 
                'color' => ['argb' => self::COLOR_SURFACE_MUTED]
            ]
        ]
    ];

    private const DATA_ROW_EVEN_STYLE = [
        'fill' => [
            'fillType' => Fill::FILL_SOLID, 
            'startColor' => ['argb' => self::COLOR_SURFACE]
        ]
    ];

    private const TOTAL_SECTION_STYLE = [
        'fill' => [
            'fillType' => Fill::FILL_SOLID, 
            'startColor' => ['argb' => self::COLOR_SURFACE]
        ],
        'borders' => [
            'top' => [
                'borderStyle' => Border::BORDER_MEDIUM, 
                'color' => ['argb' => self::COLOR_SECONDARY]
            ],
            'outline' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['argb' => self::COLOR_BORDER]
            ]
        ]
    ];

    private const TOTAL_LABEL_STYLE = [
        'font' => [
            'bold' => true, 
            'size' => 11,
            'color' => ['argb' => self::COLOR_TEXT_MUTED]
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_LEFT
        ]
    ];

    private const TOTAL_VALUE_STYLE = [
        'font' => [
            'bold' => true, 
            'size' => 12,
            'color' => ['argb' => self::COLOR_PRIMARY]
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_RIGHT
        ]
    ];

    public function export(ReportData $data): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(mb_substr($data->title, 0, 31));

        $row = 1;
        $columnCount = max(1, count($data->headers));
        $lastColumn = Coordinate::stringFromColumnIndex($columnCount);

        // Título
        $this->renderTitle($sheet, $data->title, $row, $lastColumn);

        // Subtítulo
        if (!empty($data->subtitle)) {
            $row++;
            $this->renderSubtitle($sheet, $data->subtitle, $row, $lastColumn);
        }

        // Metadados
        $row++;
        $this->renderMetadata($sheet, $data, $row, $lastColumn);

        $row += 2;

        // Cabeçalhos
        $headerRow = $row;
        $this->renderHeaders($sheet, $data->headers, $row, $lastColumn);

        // Dados
        $row++;
        $lastRow = $this->renderDataRows($sheet, $data->rows, $row, $columnCount, $lastColumn);

        // Totais
        if (!empty($data->totals)) {
            $this->renderTotals($sheet, $data->totals, $lastRow, $columnCount, $lastColumn);
        }

        // Auto-ajustar colunas
        $this->autoSizeColumns($sheet, $columnCount);

        // Aplicar zoom padrão
        $sheet->getSheetView()->setZoomScale(110);

        return $this->toBinary($spreadsheet);
    }

    private function renderTitle(
        Worksheet $sheet,
        string $title,
        int $row,
        string $lastColumn
    ): void {
        $sheet->setCellValue("A{$row}", $title);
        $sheet->mergeCells("A{$row}:{$lastColumn}{$row}");
        $sheet->getStyle("A{$row}")->getFont()->applyFromArray(self::FONT_TITLE);
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getRowDimension($row)->setRowHeight(25);
    }

    private function renderSubtitle(
        Worksheet $sheet,
        string $subtitle,
        int $row,
        string $lastColumn
    ): void {
        $sheet->setCellValue("A{$row}", $subtitle);
        $sheet->mergeCells("A{$row}:{$lastColumn}{$row}");
        $sheet->getStyle("A{$row}")->getFont()->applyFromArray(self::FONT_SUBTITLE);
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
    }

    private function renderMetadata(
        Worksheet $sheet,
        ReportData $data,
        int $row,
        string $lastColumn
    ): void {
        $metadata = sprintf(
            'Gerado em: %s | Total de registros: %d',
            date('d/m/Y H:i'),
            count($data->rows)
        );
        
        $sheet->setCellValue("A{$row}", $metadata);
        $sheet->mergeCells("A{$row}:{$lastColumn}{$row}");
        $sheet->getStyle("A{$row}")->getFont()->applyFromArray([
            'size' => 9,
            'color' => ['argb' => self::COLOR_TEXT_MUTED]
        ]);
    }

    private function renderHeaders(
        Worksheet $sheet,
        array $headers,
        int $row,
        string $lastColumn
    ): void {
        foreach ($headers as $index => $header) {
            $columnLetter = Coordinate::stringFromColumnIndex($index + 1);
            $sheet->setCellValue("{$columnLetter}{$row}", $header);
        }
        
        $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->applyFromArray(self::HEADER_STYLE);
        $sheet->getRowDimension($row)->setRowHeight(20);
    }

    private function renderDataRows(
        Worksheet $sheet,
        array $rows,
        int $startRow,
        int $columnCount,
        string $lastColumn
    ): int {
        if (empty($rows)) {
            return $startRow - 1;
        }

        $sheet->fromArray($rows, null, "A{$startRow}", true);
        $lastRow = $startRow + count($rows) - 1;

        // Aplicar estilo base em todas as linhas
        $sheet->getStyle("A{$startRow}:{$lastColumn}{$lastRow}")
            ->applyFromArray(self::DATA_ROW_STYLE);

        // Aplicar zebra striping (linhas alternadas)
        for ($row = $startRow; $row <= $lastRow; $row++) {
            if (($row - $startRow) % 2 === 0) {
                $sheet->getStyle("A{$row}:{$lastColumn}{$row}")
                    ->applyFromArray(self::DATA_ROW_EVEN_STYLE);
            }
            
            // Formatar valores numéricos
            $this->formatNumericCells($sheet, $row, $columnCount);
        }

        return $lastRow;
    }

    private function formatNumericCells(
        Worksheet $sheet,
        int $row,
        int $columnCount
    ): void {
        for ($col = 1; $col <= $columnCount; $col++) {
            $columnLetter = Coordinate::stringFromColumnIndex($col);
            $cell = $sheet->getCell("{$columnLetter}{$row}");
            $value = $cell->getValue();
            
            if (is_numeric($value) && strpos((string) $value, '.') !== false) {
                $cell->getStyle()->getNumberFormat()
                    ->setFormatCode('#,##0.00');
            }
        }
    }

    private function renderTotals(
        Worksheet $sheet,
        array $totals,
        int $lastDataRow,
        int $columnCount,
        string $lastColumn
    ): void {
        $totalRow = $lastDataRow + 2;
        $labelColumnIndex = max(1, $columnCount - 1);
        $labelColumn = Coordinate::stringFromColumnIndex($labelColumnIndex);

        foreach ($totals as $label => $value) {
            // Mesclar células do label
            if ($columnCount > 1) {
                $sheet->mergeCells("A{$totalRow}:{$labelColumn}{$totalRow}");
            }

            // Label
            $sheet->setCellValue("A{$totalRow}", (string) $label);
            $sheet->getStyle("A{$totalRow}:{$labelColumn}{$totalRow}")
                ->applyFromArray(self::TOTAL_LABEL_STYLE);

            // Valor
            $sheet->setCellValue("{$lastColumn}{$totalRow}", $this->formatTotalValue($value));
            $sheet->getStyle("{$lastColumn}{$totalRow}")
                ->applyFromArray(self::TOTAL_VALUE_STYLE);

            // Aplicar fundo e borda em toda a linha
            $sheet->getStyle("A{$totalRow}:{$lastColumn}{$totalRow}")
                ->applyFromArray(self::TOTAL_SECTION_STYLE);

            $sheet->getRowDimension($totalRow)->setRowHeight(22);

            $totalRow++;
        }
    }

    private function formatTotalValue($value): string
    {
        if (is_numeric($value) && strpos((string) $value, '.') !== false) {
            return number_format((float) $value, 2, ',', '.');
        }

        return (string) $value;
    }

    private function autoSizeColumns(
        Worksheet $sheet,
        int $columnCount
    ): void {
        foreach (range(1, $columnCount) as $colIndex) {
            $sheet->getColumnDimensionByColumn($colIndex)->setAutoSize(true);
        }
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