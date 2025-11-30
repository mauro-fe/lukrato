<?php

declare(strict_types=1);

namespace Application\Services;

use Application\Controllers\Api\LancamentoTipo;
use Application\DTO\ReportData;
use Carbon\Carbon;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class LancamentoExportService
{
    private ExcelExportService $excelExporter;
    private PdfExportService $pdfExporter;

    public function __construct(
        ?ExcelExportService $excelExporter = null,
        ?PdfExportService $pdfExporter = null
    ) {
        $this->excelExporter = $excelExporter ?? new ExcelExportService();
        $this->pdfExporter = $pdfExporter ?? new PdfExportService();
    }

    public function export(int $userId, array $filters): array
    {
        $format = $this->resolveFormat($filters['format'] ?? 'excel');
        [$start, $end] = $this->resolvePeriod($filters);
        $criteria = $this->normalizeCriteria($filters);

        $rows = $this->fetchRows($userId, $start, $end, $criteria);
        $reportData = $this->buildReportData($rows, $start, $end, $criteria);

        $binary = $format === 'pdf'
            ? $this->pdfExporter->export($reportData)
            : $this->excelExporter->export($reportData);

        $extension = $format === 'pdf' ? 'pdf' : 'xlsx';
        $mime = $format === 'pdf'
            ? 'application/pdf'
            : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        return [
            'binary' => $binary,
            'mime' => $mime,
            'filename' => $this->buildFilename($start, $end, $extension),
        ];
    }

    private function resolveFormat(string $rawFormat): string
    {
        $format = strtolower(trim($rawFormat));
        return $format === 'pdf' ? 'pdf' : 'excel';
    }

    /**
     * @return array{start:Carbon,end:Carbon,categoria?:array{isNull:bool,id:?int},tipo:?string,account:?int,include_transfers:bool}
     */
    private function normalizeCriteria(array $filters): array
    {
        $categoria = $this->parseCategoria((string)($filters['categoria_id'] ?? ''));
        $tipo = $this->parseTipo($filters['tipo'] ?? null);

        $accountId = $filters['account_id'] ?? null;
        $account = null;
        if ($accountId !== null && $accountId !== '') {
            if (!preg_match('/^\d+$/', (string)$accountId)) {
                throw new InvalidArgumentException('Conta invalida para exportacao.');
            }
            $account = (int)$accountId;
        }

        $includeTransfers = filter_var($filters['include_transfers'] ?? false, FILTER_VALIDATE_BOOLEAN);

        return [
            'categoria' => $categoria,
            'tipo' => $tipo,
            'account' => $account,
            'include_transfers' => $includeTransfers,
        ];
    }

    /**
     * @return array{Carbon,Carbon}
     */
    private function resolvePeriod(array $filters): array
    {
        $start = $filters['start_date'] ?? null;
        $end = $filters['end_date'] ?? null;

        if ($start && $end) {
            try {
                $startDate = Carbon::createFromFormat('Y-m-d', $start)->startOfDay();
                $endDate = Carbon::createFromFormat('Y-m-d', $end)->endOfDay();
            } catch (\Exception) {
                throw new InvalidArgumentException('Periodo invalido para exportacao.');
            }

            if ($endDate->lessThan($startDate)) {
                throw new InvalidArgumentException('A data final precisa ser posterior a inicial.');
            }

            return [$startDate, $endDate];
        }

        $month = $filters['month'] ?? date('Y-m');
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            throw new InvalidArgumentException('Mes invalido. Use o formato YYYY-MM.');
        }

        [$year, $mon] = array_map('intval', explode('-', $month));
        $startDate = Carbon::create($year, $mon, 1)->startOfDay();
        $endDate = (clone $startDate)->endOfMonth()->endOfDay();

        return [$startDate, $endDate];
    }

    /**
     * @param array{categoria:array{isNull:bool,id:?int},tipo:?string,account:?int,include_transfers:bool} $criteria
     */
    private function fetchRows(int $userId, Carbon $start, Carbon $end, array $criteria): Collection
    {
        $query = DB::table('lancamentos as l')
            ->leftJoin('categorias as c', 'c.id', '=', 'l.categoria_id')
            ->leftJoin('contas as origem', 'origem.id', '=', 'l.conta_id')
            ->leftJoin('contas as destino', 'destino.id', '=', 'l.conta_id_destino')
            ->where('l.user_id', $userId)
            ->whereBetween('l.data', [
                $start->toDateString(),
                $end->toDateString(),
            ])
            ->orderBy('l.data')
            ->orderBy('l.id');

        if (!$criteria['include_transfers']) {
            $query->where('l.eh_transferencia', 0);
        }

        if ($criteria['tipo']) {
            $query->where('l.tipo', $criteria['tipo']);
        }

        if ($criteria['categoria']['isNull']) {
            $query->whereNull('l.categoria_id');
        } elseif ($criteria['categoria']['id']) {
            $query->where('l.categoria_id', $criteria['categoria']['id']);
        }

        if ($criteria['account']) {
            $accountId = $criteria['account'];
            $query->where(function ($w) use ($accountId) {
                $w->where('l.conta_id', $accountId)
                    ->orWhere('l.conta_id_destino', $accountId);
            });
        }

        return $query->selectRaw('
                l.id,
                l.data,
                l.tipo,
                l.valor,
                l.descricao,
                l.observacao,
                l.eh_transferencia,
                l.eh_saldo_inicial,
                l.categoria_id,
                l.conta_id,
                l.conta_id_destino,
                COALESCE(c.nome, "Sem categoria") as categoria_nome,
                COALESCE(origem.nome, origem.instituicao, "Sem conta") as conta_origem,
                COALESCE(destino.nome, destino.instituicao, "") as conta_destino
            ')
            ->get();
    }

    /**
     * @param array{categoria:array{isNull:bool,id:?int},tipo:?string,account:?int,include_transfers:bool} $criteria
     */
    private function buildReportData(Collection $rows, Carbon $start, Carbon $end, array $criteria): ReportData
    {
        $headers = [
            'Data',
            'Tipo',
            'Categoria',
            'Conta Origem',
            'Conta Destino',
            'Descricao',
            'Observacao',
            'Valor',
        ];

        $mappedRows = $rows->map(function ($row) {
            $date = Carbon::parse($row->data)->format('d/m/Y');
            $tipo = $row->tipo;
            $categoria = $row->categoria_nome ?: 'Sem categoria';
            $origem = $row->conta_origem ?: '-';
            $destino = $row->conta_destino ?: ($row->eh_transferencia ? '-' : '');
            $descricao = $row->descricao ?? '';
            $observacao = $row->observacao ?? '';
            $valor = $this->resolveSignedValue($row);

            return [
                $date,
                $tipo,
                $categoria,
                $origem,
                $destino,
                $descricao,
                $observacao,
                $valor,
            ];
        })->values()->all();

        $totals = $this->buildTotals($rows);
        $subtitle = $this->buildSubtitle($start, $end, $criteria, $rows);

        return new ReportData(
            title: 'Exportacao de lancamentos',
            headers: $headers,
            rows: $mappedRows,
            subtitle: $subtitle,
            totals: $totals
        );
    }

    private function buildTotals(Collection $rows): array
    {
        $receitas = 0.0;
        $despesas = 0.0;

        foreach ($rows as $row) {
            $tipo = strtolower((string)$row->tipo);
            $valor = (float)$row->valor;
            if ($tipo === LancamentoTipo::RECEITA->value) {
                $receitas += $valor;
            } elseif ($tipo === LancamentoTipo::DESPESA->value) {
                $despesas += $valor;
            }
        }

        return [
            'Total de Receitas' => $this->formatMoney($receitas),
            'Total de Despesas' => $this->formatMoney($despesas),
            'Saldo do Periodo' => $this->formatMoney($receitas - $despesas),
        ];
    }

    /**
     * @param array{categoria:array{isNull:bool,id:?int},tipo:?string,account:?int,include_transfers:bool} $criteria
     */
    private function buildSubtitle(Carbon $start, Carbon $end, array $criteria, Collection $rows): string
    {
        $parts = [];
        $parts[] = sprintf(
            'Periodo: %s a %s',
            $start->format('d/m/Y'),
            $end->format('d/m/Y')
        );

        if ($criteria['tipo']) {
            $parts[] = 'Tipo: ' . ucfirst($criteria['tipo']);
        }

        if ($criteria['categoria']['isNull']) {
            $parts[] = 'Categoria: Sem categoria';
        } elseif ($criteria['categoria']['id']) {
            $parts[] = 'Categoria ID ' . $criteria['categoria']['id'];
        }

        if ($criteria['account']) {
            $label = $this->inferAccountLabel($criteria['account'], $rows);
            $parts[] = 'Conta: ' . $label;
        }

        if ($criteria['include_transfers']) {
            $parts[] = 'Inclui transferencias';
        }

        return implode(' | ', $parts);
    }

    private function inferAccountLabel(int $accountId, Collection $rows): string
    {
        foreach ($rows as $row) {
            if ((int)$row->conta_id === $accountId) {
                return $row->conta_origem ?: ('Conta #' . $accountId);
            }
            if ((int)$row->conta_id_destino === $accountId && $row->conta_destino) {
                return $row->conta_destino;
            }
        }

        $label = DB::table('contas')
            ->selectRaw('COALESCE(nome, instituicao, "Conta") as label')
            ->where('id', $accountId)
            ->value('label');

        return $label ? (string)$label : 'Conta #' . $accountId;
    }

    private function buildFilename(Carbon $start, Carbon $end, string $extension): string
    {
        $sameMonth = $start->format('Y-m') === $end->format('Y-m');
        $suffix = $sameMonth
            ? $start->format('Y_m')
            : $start->format('Y_m') . '-' . $end->format('Y_m');

        return sprintf('lancamentos_%s.%s', $suffix, $extension);
    }

    private function formatMoney(float $value): string
    {
        return 'R$ ' . number_format($value, 2, ',', '.');
    }

    private function resolveSignedValue(object $row): float
    {
        $valor = (float)$row->valor;
        $tipo = strtolower((string)$row->tipo);

        if ($row->eh_transferencia) {
            return $valor;
        }

        if ($tipo === LancamentoTipo::DESPESA->value) {
            return -1 * $valor;
        }

        return $valor;
    }

    private function parseTipo(mixed $tipo): ?string
    {
        if (!$tipo) {
            return null;
        }

        $value = strtolower(trim((string)$tipo));
        try {
            return LancamentoTipo::from($value)->value;
        } catch (\ValueError) {
            throw new InvalidArgumentException('Tipo invalido para exportacao.');
        }
    }

    /**
     * @return array{isNull:bool,id:?int}
     */
    private function parseCategoria(string $raw): array
    {
        $raw = strtolower(trim($raw));

        if ($raw === '' || $raw === 'all') {
            return ['isNull' => false, 'id' => null];
        }

        if (in_array($raw, ['none', 'null', '0'], true)) {
            return ['isNull' => true, 'id' => null];
        }

        if (!ctype_digit($raw)) {
            throw new InvalidArgumentException('Categoria invalida para exportacao.');
        }

        $id = (int)$raw;
        if ($id <= 0) {
            throw new InvalidArgumentException('Categoria invalida para exportacao.');
        }

        return ['isNull' => false, 'id' => $id];
    }
}