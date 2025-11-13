<?php

declare(strict_types=1);

namespace Application\Services;

use Application\Contracts\ReportExporterInterface;
use Application\DTO\ReportData;
use Dompdf\Dompdf;
use Dompdf\Options;

class PdfExportService implements ReportExporterInterface
{
    private Dompdf $dompdf;

    public function __construct(?Dompdf $dompdf = null)
    {
        if ($dompdf) {
            $this->dompdf = $dompdf;
            return;
        }

        $options = new Options();
        $options->setIsRemoteEnabled(true);
        $options->setDefaultFont('DejaVu Sans');
        $options->setIsHtml5ParserEnabled(true);

        $this->dompdf = new Dompdf($options);
    }

    public function export(ReportData $data): string
    {
        $html = $this->renderHtml($data);

        $this->dompdf->loadHtml($html, 'UTF-8');
        $this->dompdf->setPaper('A4', 'portrait');
        $this->dompdf->render();

        return (string) $this->dompdf->output();
    }

    private function renderHtml(ReportData $data): string
    {
        $rowsHtml = '';
        foreach ($data->rows as $row) {
            $cells = array_map(fn($cell) => $this->escape((string)$cell), $row);
            $rowsHtml .= '<tr><td class="cell">' . implode('</td><td class="cell">', $cells) . '</td></tr>';
        }

        $headersHtml = implode('', array_map(
            fn($h) => '<th class="header">' . $this->escape($h) . '</th>', 
            $data->headers
        ));

        $title = $this->escape($data->title);
        $subtitle = $this->escape($data->subtitle ?? '');

        return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8" />
    <style>
        body { font-family: 'DejaVu Sans', Helvetica, sans-serif; font-size: 12px; color: #0f172a; }
        h1 { font-size: 20px; margin: 0 0 4px 0; }
        h2 { font-size: 12px; font-weight: normal; margin: 0; color: #475569; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th.header { padding: 8px; background: #f7f9fc; text-align: left; border-bottom: 1px solid #dbe0ea; font-size: 11px; font-weight: bold; }
        td.cell { padding: 6px 8px; border-bottom: 1px solid #eef1f5; font-size: 11px; }
        tr:nth-child(even) { background: #fafbff; }
    </style>
</head>
<body>
    <h1>{$title}</h1>
    <h2>{$subtitle}</h2>
    <table>
        <thead><tr>{$headersHtml}</tr></thead>
        <tbody>{$rowsHtml}</tbody>
    </table>
</body>
</html>
HTML;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}