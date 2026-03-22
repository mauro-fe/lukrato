<?php

declare(strict_types=1);

namespace Application\Services\Report;

use Application\Builders\ReportExportBuilder;
use Application\DTO\ReportParameters;
use Application\Enums\ReportType;
use Application\Models\FaturaCartaoItem;
use Application\Models\Lancamento;
use Application\Models\Usuario;
use Application\Services\Financeiro\ComparativesService;
use Application\Services\Financeiro\InsightsService;
use Carbon\Carbon;
use InvalidArgumentException;

class ReportApiWorkflowService
{
    public function __construct(
        private readonly ReportService $reportService = new ReportService(),
        private readonly ReportExportBuilder $exportBuilder = new ReportExportBuilder(),
        private readonly PdfExportService $pdfExport = new PdfExportService(),
        private readonly ExcelExportService $excelExport = new ExcelExportService(),
        private readonly InsightsService $insightsService = new InsightsService(),
        private readonly ComparativesService $comparativesService = new ComparativesService()
    ) {
    }

    /**
     * @param array<string, string|null> $query
     * @return array{result:array<string, mixed>, type:ReportType, params:ReportParameters}
     */
    public function generateReport(int $userId, Usuario $user, array $query): array
    {
        $params = $this->buildReportParameters($userId, $query);
        $type = $this->resolveReportType($query);
        $result = $this->reportService->generateReport(
            $type,
            $params,
            $this->shouldIncludeDetails($type, $user)
        );

        return [
            'result' => $result,
            'type' => $type,
            'params' => $params,
        ];
    }

    /**
     * @param array<string, string|null> $query
     * @return array{content:string, filename:string, mime:string}
     */
    public function exportReport(int $userId, Usuario $user, array $query): array
    {
        $generated = $this->generateReport($userId, $user, $query);
        $format = $this->resolveExportFormat($query);
        $reportData = $this->exportBuilder->build(
            $generated['type'],
            $generated['params'],
            $generated['result']
        );

        if ($format === 'excel') {
            $content = $this->excelExport->export($reportData);

            return [
                'content' => $content,
                'filename' => $this->buildExportFilename($generated['type'], $generated['params'], 'xlsx'),
                'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ];
        }

        $content = $this->pdfExport->export($reportData);

        return [
            'content' => $content,
            'filename' => $this->buildExportFilename($generated['type'], $generated['params'], 'pdf'),
            'mime' => 'application/pdf',
        ];
    }

    /**
     * @param array<string, string|null> $query
     * @return array<string, float>
     */
    public function buildSummary(int $userId, array $query): array
    {
        [$year, $month] = $this->resolveYearMonth($query);

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        $prevStart = (clone $startDate)->subMonth()->startOfMonth();
        $prevEnd = (clone $startDate)->subMonth()->endOfMonth();

        $current = $this->querySummaryLancamentos($userId, $startDate, $endDate);
        $previous = $this->querySummaryLancamentos($userId, $prevStart, $prevEnd);

        $currentCards = $this->querySummaryCards($userId, $month, $year);
        $previousCards = $this->querySummaryCards($userId, $prevStart->month, $prevStart->year);

        return [
            'totalReceitas' => (float) ($current->total_receitas ?? 0),
            'totalDespesas' => (float) ($current->total_despesas ?? 0),
            'saldo' => (float) (($current->total_receitas ?? 0) - ($current->total_despesas ?? 0)),
            'totalCartoes' => (float) $currentCards,
            'prevReceitas' => (float) ($previous->total_receitas ?? 0),
            'prevDespesas' => (float) ($previous->total_despesas ?? 0),
            'prevSaldo' => (float) (($previous->total_receitas ?? 0) - ($previous->total_despesas ?? 0)),
            'prevCartoes' => (float) $previousCards,
        ];
    }

    /**
     * @param array<string, string|null> $query
     * @return array{insights:array<int, array<string, mixed>>, totalCount:int, isTeaser:bool}
     */
    public function buildInsightsTeaser(int $userId, Usuario $user, array $query): array
    {
        [$year, $month] = $this->resolveYearMonth($query);
        $allInsights = $this->insightsService->generate($userId, $year, $month);
        $teaser = array_slice($allInsights, 0, 3);

        return [
            'insights' => InsightsService::toArrayList($teaser),
            'totalCount' => count($allInsights),
            'isTeaser' => !$user->isPro(),
        ];
    }

    /**
     * @param array<string, string|null> $query
     * @return array{insights:array<int, array<string, mixed>>}
     */
    public function buildInsights(int $userId, array $query): array
    {
        [$year, $month] = $this->resolveYearMonth($query);
        $insights = $this->insightsService->generate($userId, $year, $month);

        return [
            'insights' => InsightsService::toArrayList($insights),
        ];
    }

    /**
     * @param array<string, string|null> $query
     * @return array<string, mixed>
     */
    public function buildComparatives(int $userId, array $query): array
    {
        [$year, $month] = $this->resolveYearMonth($query);

        return $this->comparativesService->generate(
            $userId,
            $year,
            $month,
            $this->resolveAccountId($query)
        );
    }

    /**
     * @param array<string, string|null> $query
     * @return array<string, mixed>
     */
    public function buildCardDetails(int $userId, int $cardId, array $query): array
    {
        if ($cardId <= 0) {
            throw new InvalidArgumentException('ID do cartão inválido');
        }

        $mes = $query['mes'] ?? date('m');
        $ano = $query['ano'] ?? date('Y');

        if (!preg_match('/^\d{2}$/', $mes ?? '') || !preg_match('/^\d{4}$/', $ano ?? '')) {
            throw new InvalidArgumentException('Formato de mês/ano inválido');
        }

        $this->validateDateParams((int) $ano, (int) $mes);

        return $this->reportService->getCardDetailedReport($userId, $cardId, $mes, $ano);
    }

    /**
     * @param array<string, string|null> $query
     */
    public function resolveYearMonth(array $query): array
    {
        $year = isset($query['year']) ? (int) $query['year'] : (int) date('Y');
        $month = isset($query['month']) ? (int) $query['month'] : (int) date('m');

        $this->validateDateParams($year, $month);

        return [$year, $month];
    }

    /**
     * @param array<string, string|null> $query
     */
    private function buildReportParameters(int $userId, array $query): ReportParameters
    {
        [$start, $end] = $this->resolvePeriod($query);

        return new ReportParameters(
            start: $start,
            end: $end,
            accountId: $this->resolveAccountId($query),
            userId: $userId,
            includeTransfers: $this->shouldIncludeTransfers($query)
        );
    }

    /**
     * @param array<string, string|null> $query
     * @return array{0:Carbon,1:Carbon}
     */
    private function resolvePeriod(array $query): array
    {
        $monthParam = $query['month'] ?? null;
        $yearParam = $query['year'] ?? null;

        if ($this->isYearMonthFormat($monthParam)) {
            return $this->parsePeriodFromYearMonth((string) $monthParam);
        }

        $year = $yearParam !== null ? (int) $yearParam : (int) date('Y');
        $month = $monthParam !== null ? (int) $monthParam : (int) date('n');

        $this->validateDateParams($year, $month);

        return $this->createPeriod($year, $month);
    }

    private function isYearMonthFormat(?string $value): bool
    {
        return $value !== null && preg_match('/^\d{4}-\d{2}$/', $value) === 1;
    }

    /**
     * @return array{0:Carbon,1:Carbon}
     */
    private function parsePeriodFromYearMonth(string $monthParam): array
    {
        preg_match('/^(\d{4})-(\d{2})$/', $monthParam, $matches);

        $year = (int) $matches[1];
        $month = (int) $matches[2];

        $this->validateDateParams($year, $month);

        return $this->createPeriod($year, $month);
    }

    /**
     * @return array{0:Carbon,1:Carbon}
     */
    private function createPeriod(int $year, int $month): array
    {
        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end = (clone $start)->endOfMonth()->endOfDay();

        return [$start, $end];
    }

    private function validateDateParams(int $year, int $month): void
    {
        if ($year < 2000 || $year > 2100 || $month < 1 || $month > 12) {
            throw new InvalidArgumentException('Parâmetros de data inválidos.');
        }
    }

    /**
     * @param array<string, string|null> $query
     */
    private function resolveAccountId(array $query): ?int
    {
        $accountId = $query['account_id'] ?? null;

        if ($accountId === null || preg_match('/^\d+$/', $accountId) !== 1) {
            return null;
        }

        return (int) $accountId;
    }

    /**
     * @param array<string, string|null> $query
     */
    private function shouldIncludeTransfers(array $query): bool
    {
        return ($query['include_transfers'] ?? null) === '1';
    }

    /**
     * @param array<string, string|null> $query
     */
    private function resolveReportType(array $query): ReportType
    {
        $type = $query['type'] ?? ReportType::DESPESAS_POR_CATEGORIA->value;

        return ReportType::fromShorthand($type);
    }

    /**
     * @param array<string, string|null> $query
     */
    private function resolveExportFormat(array $query): string
    {
        $format = strtolower($query['format'] ?? 'pdf');

        return $format === 'excel' ? 'excel' : 'pdf';
    }

    private function shouldIncludeDetails(ReportType $type, Usuario $user): bool
    {
        return in_array($type, [
            ReportType::DESPESAS_POR_CATEGORIA,
            ReportType::RECEITAS_POR_CATEGORIA,
            ReportType::DESPESAS_ANUAIS_POR_CATEGORIA,
            ReportType::RECEITAS_ANUAIS_POR_CATEGORIA,
        ], true) && $user->isPro();
    }

    private function buildExportFilename(ReportType $type, ReportParameters $params, string $extension): string
    {
        $period = $params->isSingleMonth()
            ? $params->start->format('Y_m')
            : $params->start->format('Y_m') . '-' . $params->end->format('Y_m');

        return sprintf('%s_%s.%s', $type->value, $period, $extension);
    }

    private function querySummaryLancamentos(int $userId, Carbon $start, Carbon $end): object
    {
        return Lancamento::where('user_id', $userId)
            ->whereBetween('data', [$start->toDateString(), $end->toDateString()])
            ->where('eh_transferencia', 0)
            ->where('pago', 1)
            ->where('afeta_caixa', 1)
            ->selectRaw('
                SUM(CASE WHEN tipo = "receita" THEN valor ELSE 0 END) as total_receitas,
                SUM(CASE WHEN tipo = "despesa" THEN valor ELSE 0 END) as total_despesas
            ')
            ->first();
    }

    private function querySummaryCards(int $userId, int $month, int $year): float
    {
        return (float) FaturaCartaoItem::where('user_id', $userId)
            ->where('mes_referencia', $month)
            ->where('ano_referencia', $year)
            ->whereHas('cartaoCredito', static function ($query): void {
                $query->where('ativo', 1);
            })
            ->sum('valor');
    }
}
