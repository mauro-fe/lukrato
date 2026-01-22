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
        $options->setChroot(realpath(__DIR__ . '/../../'));

        $this->dompdf = new Dompdf($options);
    }

    public function export(ReportData $data): string
    {
        $html = $this->renderHtml($data);

        $this->dompdf->loadHtml($html, 'UTF-8');
        $this->dompdf->setPaper('A4', 'portrait');
        $this->dompdf->render();

        $this->addPageNumbers();

        return (string) $this->dompdf->output();
    }

    private function addPageNumbers(): void
    {
        $canvas = $this->dompdf->getCanvas();
        $canvas->page_text(
            520,
            800,
            "Página {PAGE_NUM} de {PAGE_COUNT}",
            null,
            9,
            [0.29, 0.33, 0.42]
        );
    }

    private function renderHtml(ReportData $data): string
    {
        $css = $this->getStyles();
        $header = $this->renderHeader($data);
        $table = $this->renderTable($data);
        $totals = $this->renderTotals($data->totals);
        $footer = $this->renderFooter();

        return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <style>{$css}</style>
</head>
<body>
    {$header}
    <div class="content">
        {$table}
        {$totals}
    </div>
    {$footer}
</body>
</html>
HTML;
    }

    private function getStyles(): string
    {
        return <<<CSS
* { 
    margin: 0; 
    padding: 0; 
    box-sizing: border-box; 
}

body {
    font-family: 'DejaVu Sans', 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
    font-size: 12px;
    color: black;
    line-height: 1.5;
    background: #e6f0fa;
    padding-bottom: 100px;
}

.page-header {
    background: linear-gradient(135deg, #e67e22 0%, #d35400 100%);
    color: black;
    padding: 32px 40px;
    margin-bottom: 32px;
    border-radius: 0 0 12px 12px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

.page-header h1 {
    font-size: 28px;
    font-weight: 700;
    margin-bottom: 8px;
    letter-spacing: -0.5px;
}

.page-header h2 {
    font-size: 14px;
    font-weight: 400;
    opacity: 0.92;
    margin-bottom: 12px;
    color:black;
}

.metadata {
    display: flex;
    gap: 24px;
    margin-top: 16px;
    font-size: 11px;
    opacity: 0.88;
}

.metadata-item {
    display: flex;
    align-items: center;
    gap: 8px;
    background: rgba(255, 255, 255, 0.15);
    padding: 6px 12px;
    border-radius: 8px;
}

.content {
    padding: 0 40px 100px;
}

.table-wrapper {
    background: #f0f6fc;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(30, 41, 59, 0.1);
}

table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    table-layout: fixed;
}

thead {
    background: linear-gradient(to bottom, #f0f6fc, #d9e6f2);
    border-bottom: 2px solid #2c3e50;
}

th {
    padding: 14px 8px;
    text-align: left;
    font-size: 11px;
    font-weight: 700;
    color: black;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-right: 1px solid rgba(30, 41, 59, 0.1);
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}

/* Larguras específicas para cada coluna */
th:nth-child(1) { width: 14%; } /* DATA */
th:nth-child(2) { width: 11%; } /* TIPO */
th:nth-child(3) { width: 17%; } /* CATEGORIA */
th:nth-child(4) { width: 17%; } /* CONTA ORIGEM */
th:nth-child(5) { width: 17%; } /* CONTA DESTINO */
th:nth-child(6) { width: 18%; } /* DESCRICAO */
th:nth-child(7) { width: 13%; } /* VALOR */

th:last-child {
    border-right: none;
}

tbody tr {
    border-bottom: 1px solid #d9e6f2;
    transition: background-color 0.15s ease;
}

tbody tr:nth-child(odd) {
    background: #f0f6fc;
}

tbody tr:nth-child(even) {
    background: white;
}

td {
    padding: 12px 8px;
    font-size: 11px;
    color: black;
    border-right: 1px solid #d9e6f2;
    word-wrap: break-word;
    overflow: hidden;
}

td:last-child {
    border-right: none;
}

.totals-section {
    margin-top: 32px;
    background: linear-gradient(135deg, #f0f6fc 0%, #e6f0fa 100%);
    border: 2px solid #d9e6f2;
    border-radius: 12px;
    padding: 24px 28px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    page-break-inside: avoid;
}

.totals-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
    padding-bottom: 14px;
    border-bottom: 2px solid #2c3e50;
}

.totals-header h3 {
    font-size: 16px;
    font-weight: 700;
    color: black;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.totals-icon {
    font-size: 20px;
}

.totals-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 14px;
}

.total-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 16px;
    background: white;
    border-radius: 8px;
    border: 1px solid rgba(30, 41, 59, 0.1);
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.08);
}

.total-label {
    font-size: 11px;
    color: black;
    font-weight: 500;
}

.total-value {
    font-size: 14px;
    font-weight: 700;
    color: #e67e22;
}

.page-footer {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 16px 40px;
    background: #f0f6fc;
    border-top: 2px solid #d9e6f2;
    font-size: 9px;
    color: black;
    text-align: center;
}

.footer-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.empty-state {
    text-align: center;
    padding: 60px 40px;
    color: black
}

.empty-state-icon {
    font-size: 56px;
    margin-bottom: 20px;
    opacity: 0.5;
}

.empty-state-text {
    font-size: 16px;
    font-weight: 500;
    color: black;
}

@page {
    margin: 30px 20px 100px 20px;
}
CSS;
    }

    private function renderHeader(ReportData $data): string
    {
        $title = $this->escape($data->title);
        $subtitle = $this->escape($data->subtitle ?? '');
        $date = date('d/m/Y H:i');

        $subtitleHtml = $subtitle ? "<h2>{$subtitle}</h2>" : '';

        return <<<HTML
<div class="page-header">
    <h1>{$title}</h1>
    {$subtitleHtml}
    <div class="metadata">
        <div class="metadata-item">
            <span>📅</span>
            <span>Gerado em: {$date}</span>
        </div>
        <div class="metadata-item">
            <span>📊</span>
            <span>Total de registros: {$this->getRowCount($data)}</span>
        </div>
    </div>
</div>
HTML;
    }

    private function renderTable(ReportData $data): string
    {
        if (empty($data->headers) || empty($data->rows)) {
            return $this->renderEmptyState();
        }

        $headersHtml = $this->renderTableHeaders($data->headers);
        $rowsHtml = $this->renderTableRows($data->rows);

        return <<<HTML
<div class="table-wrapper">
    <table>
        <thead>
            <tr>{$headersHtml}</tr>
        </thead>
        <tbody>{$rowsHtml}</tbody>
    </table>
</div>
HTML;
    }

    private function renderTableHeaders(array $headers): string
    {
        return implode('', array_map(
            fn($header) => sprintf('<th>%s</th>', $this->escape($header)),
            $headers
        ));
    }

    private function renderTableRows(array $rows): string
    {
        $html = '';
        foreach ($rows as $row) {
            $cells = array_map(
                fn($cell) => sprintf('<td>%s</td>', $this->escape($this->formatCell($cell))),
                $row
            );
            $html .= '<tr>' . implode('', $cells) . '</tr>';
        }
        return $html;
    }

    private function renderTotals(array $totals): string
    {
        if (empty($totals)) {
            return '';
        }

        $itemsHtml = '';
        foreach ($totals as $label => $value) {
            $itemsHtml .= sprintf(
                '<div class="total-item">
                    <span class="total-label">%s</span>
                    <span class="total-value">%s</span>
                </div>',
                $this->escape((string) $label),
                $this->escape($this->formatCell($value))
            );
        }

        return <<<HTML
<div class="totals-section">
    <div class="totals-header">
        <span class="totals-icon">💰</span>
        <h3>Resumo dos Totais</h3>
    </div>
    <div class="totals-grid">
        {$itemsHtml}
    </div>
</div>
HTML;
    }

    private function renderFooter(): string
    {
        $year = date('Y');

        return <<<HTML
<div class="page-footer">
    <div class="footer-content">
        <span>Lukrato - © {$year} - Todos os direitos reservados</span>
        <span>Documento gerado automaticamente</span>
    </div>
</div>
HTML;
    }

    private function renderEmptyState(): string
    {
        return <<<HTML
<div class="empty-state">
    <div class="empty-state-icon">📋</div>
    <p class="empty-state-text">Nenhum dado disponível para exibição</p>
</div>
HTML;
    }

    private function formatCell($value): string
    {
        if ($value === null) {
            return '-';
        }

        if (is_numeric($value) && strpos((string) $value, '.') !== false) {
            return number_format((float) $value, 2, ',', '.');
        }

        return (string) $value;
    }

    private function getRowCount(ReportData $data): int
    {
        return count($data->rows);
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
