<?php

namespace Application\Controllers\Api\Report;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Lib\Auth;
use Application\Models\Usuario;
use Application\Builders\ReportExportBuilder;
use Application\Services\Financeiro\ComparativesService;
use Application\Services\Report\ExcelExportService;
use Application\Services\Financeiro\InsightsService;
use Application\Services\Infrastructure\LogService;
use Application\Services\Report\PdfExportService;
use Application\Services\Report\ReportService;
use Application\DTO\ReportParameters;
use Application\Enums\ReportType;
use Carbon\Carbon;
use InvalidArgumentException;

class RelatoriosController extends BaseController
{
    private ReportService $reportService;
    private ReportExportBuilder $exportBuilder;
    private PdfExportService $pdfExport;
    private ExcelExportService $excelExport;
    private InsightsService $insightsService;
    private ComparativesService $comparativesService;
    private ?Usuario $currentUser = null;

    public function __construct()
    {
        parent::__construct();
        $this->reportService = new ReportService();
        $this->exportBuilder = new ReportExportBuilder();
        $this->pdfExport = new PdfExportService();
        $this->excelExport = new ExcelExportService();
        $this->insightsService = new InsightsService();
        $this->comparativesService = new ComparativesService();
    }

    public function index(): void
    {
        try {
            $this->validateAccess();

            $params = $this->buildReportParameters();
            $type = $this->resolveReportType();

            // PRO users get subcategory details for category reports
            $includeDetails = $this->shouldIncludeDetails($type);

            $result = $this->reportService->generateReport($type, $params, $includeDetails);

            $this->sendSuccessResponse($result, $type, $params);
        } catch (InvalidArgumentException $e) {
            $this->handleValidationError($e);
        } catch (\Throwable $e) {
            $this->handleUnexpectedError($e);
        }
    }

    public function export(): void
    {
        try {
            $this->validateAccess();

            // Verificar se usuário é PRO
            $user = $this->getCurrentUser();
            if (!$user || !$user->isPro()) {
                Response::error('Exportação de relatórios é um recurso exclusivo do plano PRO.', 403);
                return;
            }

            $params = $this->buildReportParameters();
            $type = $this->resolveReportType();
            $format = $this->resolveExportFormat();

            $includeDetails = $this->shouldIncludeDetails($type);
            $report = $this->reportService->generateReport($type, $params, $includeDetails);
            $reportData = $this->exportBuilder->build($type, $params, $report);

            if ($format === 'excel') {
                $content = $this->excelExport->export($reportData);
                $extension = 'xlsx';
                $mime = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
            } else {
                $content = $this->pdfExport->export($reportData);
                $extension = 'pdf';
                $mime = 'application/pdf';
            }

            $filename = $this->buildExportFilename($type, $params, $extension);
            $this->sendBinaryResponse($content, $filename, $mime);
        } catch (InvalidArgumentException $e) {
            $this->handleValidationError($e);
        } catch (\Throwable $e) {
            $this->handleUnexpectedError($e);
        }
    }

    public function summary(): void
    {
        try {
            $this->requireAuthApi();

            $userId = Auth::id();
            $year = (int)($_GET['year'] ?? date('Y'));
            $month = (int)($_GET['month'] ?? date('m'));

            $this->validateDateParams($year, $month);

            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = Carbon::create($year, $month, 1)->endOfMonth();

            // Período anterior para comparação de tendências
            $prevStart = (clone $startDate)->subMonth()->startOfMonth();
            $prevEnd = (clone $startDate)->subMonth()->endOfMonth();

            $lancamentoQuery = fn(Carbon $start, Carbon $end) =>
            \Application\Models\Lancamento::where('user_id', $userId)
                ->whereBetween('data', [$start->toDateString(), $end->toDateString()])
                ->where('eh_transferencia', 0)
                ->where('pago', 1)
                ->where('afeta_caixa', 1)
                ->selectRaw('
                        SUM(CASE WHEN tipo = "receita" THEN valor ELSE 0 END) as total_receitas,
                        SUM(CASE WHEN tipo = "despesa" THEN valor ELSE 0 END) as total_despesas
                    ')
                ->first();

            $cartaoQuery = fn(int $m, int $y) =>
            \Application\Models\FaturaCartaoItem::where('user_id', $userId)
                ->where('mes_referencia', $m)
                ->where('ano_referencia', $y)
                ->whereHas('cartaoCredito', function ($q) {
                    $q->where('ativo', 1);
                })
                ->sum('valor');

            // Mês atual
            $lancamentos = $lancamentoQuery($startDate, $endDate);
            $totalCartoes = $cartaoQuery($month, $year);

            // Mês anterior (para trend badges)
            $prevLancamentos = $lancamentoQuery($prevStart, $prevEnd);
            $prevCartoes = $cartaoQuery($prevStart->month, $prevStart->year);

            Response::success([
                'totalReceitas' => (float)($lancamentos->total_receitas ?? 0),
                'totalDespesas' => (float)($lancamentos->total_despesas ?? 0),
                'saldo' => (float)(($lancamentos->total_receitas ?? 0) - ($lancamentos->total_despesas ?? 0)),
                'totalCartoes' => (float)($totalCartoes ?? 0),
                'prevReceitas' => (float)($prevLancamentos->total_receitas ?? 0),
                'prevDespesas' => (float)($prevLancamentos->total_despesas ?? 0),
                'prevSaldo' => (float)(($prevLancamentos->total_receitas ?? 0) - ($prevLancamentos->total_despesas ?? 0)),
                'prevCartoes' => (float)($prevCartoes ?? 0),
            ]);
        } catch (InvalidArgumentException $e) {
            $this->handleValidationError($e);
        } catch (\Throwable $e) {
            LogService::error('Erro ao gerar resumo.', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);
            Response::error('Erro ao gerar resumo.', 500);
        }
    }

    private function validateAccess(): void
    {
        $this->requireAuthApi();

        // Release session lock so concurrent AJAX requests don't block
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $user = Auth::user();

        if (!$user || !$this->userCanAccessReports($user)) {
            Response::forbidden('Relatórios são exclusivos do plano Pro.');
            exit;
        }
    }

    private function userCanAccessReports($user): bool
    {
        return !method_exists($user, 'podeAcessar') || $user->podeAcessar('reports');
    }

    /**
     * Retorna o usuário autenticado com cache por request.
     */
    private function getCurrentUser(): ?Usuario
    {
        return $this->currentUser ??= Usuario::find(Auth::id());
    }

    /**
     * Determina se deve incluir detalhes de subcategorias (PRO only, apenas para relatórios de categoria).
     */
    private function shouldIncludeDetails(ReportType $type): bool
    {
        $categoryTypes = [
            ReportType::DESPESAS_POR_CATEGORIA,
            ReportType::RECEITAS_POR_CATEGORIA,
            ReportType::DESPESAS_ANUAIS_POR_CATEGORIA,
            ReportType::RECEITAS_ANUAIS_POR_CATEGORIA,
        ];

        if (!in_array($type, $categoryTypes, true)) {
            return false;
        }

        $user = $this->getCurrentUser();
        return $user && $user->isPro();
    }

    private function buildReportParameters(): ReportParameters
    {
        [$start, $end] = $this->resolvePeriod();

        return new ReportParameters(
            start: $start,
            end: $end,
            accountId: $this->resolveAccountId(),
            userId: $this->userId,
            includeTransfers: $this->shouldIncludeTransfers()
        );
    }

    private function resolvePeriod(): array
    {
        $monthParam = $this->getQueryParam('month');
        $yearParam = $this->getQueryParam('year');

        if ($this->isYearMonthFormat($monthParam)) {
            return $this->parsePeriodFromYearMonth($monthParam);
        }

        return $this->buildPeriodFromSeparateParams($monthParam, $yearParam);
    }

    private function isYearMonthFormat(?string $value): bool
    {
        return $value !== null && preg_match('/^\d{4}-\d{2}$/', $value);
    }

    private function parsePeriodFromYearMonth(string $monthParam): array
    {
        preg_match('/^(\d{4})-(\d{2})$/', $monthParam, $matches);

        $year = (int)$matches[1];
        $month = (int)$matches[2];

        $this->validateDateParams($year, $month);

        return $this->createPeriod($year, $month);
    }

    private function buildPeriodFromSeparateParams(?string $monthParam, ?string $yearParam): array
    {
        $year = $yearParam !== null ? (int)$yearParam : (int)date('Y');
        $month = $monthParam !== null ? (int)$monthParam : (int)date('n');

        $this->validateDateParams($year, $month);

        return $this->createPeriod($year, $month);
    }

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

    private function resolveAccountId(): ?int
    {
        $accountId = $this->getQueryParam('account_id');

        if ($accountId === null) {
            return null;
        }

        if (!preg_match('/^\d+$/', $accountId)) {
            return null;
        }

        return (int)$accountId;
    }

    private function shouldIncludeTransfers(): bool
    {
        return $this->getQueryParam('include_transfers') === '1';
    }

    private function resolveReportType(): ReportType
    {
        $type = $this->getQueryParam('type') ?? ReportType::DESPESAS_POR_CATEGORIA->value;

        return ReportType::fromShorthand($type);
    }

    private function resolveExportFormat(): string
    {
        $format = strtolower($this->getQueryParam('format') ?? 'pdf');
        return $format === 'excel' ? 'excel' : 'pdf';
    }


    private function getQueryParam(string $key): ?string
    {
        $value = $_GET[$key] ?? null;

        return $value !== null ? (string)$value : null;
    }


    private function sendSuccessResponse(array $result, ReportType $type, ReportParameters $params): void
    {
        Response::success(array_merge($result, [
            'type' => $type->value,
            'start' => $params->start->toDateString(),
            'end' => $params->end->toDateString(),
        ]));
    }

    private function sendBinaryResponse(string $content, string $fileName, string $mime): void
    {
        if (ob_get_length() > 0) {
            ob_end_clean();
        }

        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Length: ' . (string) mb_strlen($content, '8bit'));
        echo $content;
        exit;
    }

    private function buildExportFilename(ReportType $type, ReportParameters $params, string $extension): string
    {
        $period = $params->isSingleMonth()
            ? $params->start->format('Y_m')
            : $params->start->format('Y_m') . '-' . $params->end->format('Y_m');

        return sprintf('%s_%s.%s', $type->value, $period, $extension);
    }

    private function handleValidationError(InvalidArgumentException $e): void
    {
        LogService::warning('Falha de validação no relatório.', [
            'error' => $e->getMessage(),
            'user_id' => $this->userId ?? null
        ]);

        Response::validationError(['params' => $e->getMessage()]);
    }

    private function handleUnexpectedError(\Throwable $e): void
    {
        LogService::error('Erro inesperado ao gerar relatório.', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'user_id' => $this->userId ?? null
        ]);

        Response::error('Erro ao gerar relatório.', 500);
    }

    /**
     * Teaser de insights (max 3) — acessível para todos os usuários autenticados.
     * Retorna uma amostra limitada de insights para conversão PLG.
     */
    public function insightsTeaser(): void
    {
        try {
            $this->requireAuthApi();

            $userId = Auth::id();
            $year   = (int)($_GET['year'] ?? date('Y'));
            $month  = (int)($_GET['month'] ?? date('m'));

            $this->validateDateParams($year, $month);

            $allInsights = $this->insightsService->generate($userId, $year, $month);
            $teaser = array_slice($allInsights, 0, 3);

            $user = $this->getCurrentUser();

            Response::success([
                'insights'   => InsightsService::toArrayList($teaser),
                'totalCount' => count($allInsights),
                'isTeaser'   => !($user && $user->isPro()),
            ]);
        } catch (InvalidArgumentException $e) {
            $this->handleValidationError($e);
        } catch (\Throwable $e) {
            LogService::error('Erro ao gerar insights teaser.', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);
            Response::error('Erro ao gerar insights.', 500);
        }
    }

    /**
     * Gerar insights automáticos baseados nos dados financeiros do usuário
     */
    public function insights(): void
    {
        try {
            $this->validateAccess();

            $userId = Auth::id();
            $year   = (int)($_GET['year'] ?? date('Y'));
            $month  = (int)($_GET['month'] ?? date('m'));

            $this->validateDateParams($year, $month);

            $insights = $this->insightsService->generate($userId, $year, $month);

            Response::success(['insights' => InsightsService::toArrayList($insights)]);
        } catch (InvalidArgumentException $e) {
            $this->handleValidationError($e);
        } catch (\Throwable $e) {
            LogService::error('Erro ao gerar insights.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id()
            ]);
            Response::error('Erro ao gerar insights.', 500);
        }
    }

    /**
     * Comparativos (mês atual vs anterior, ano atual vs anterior)
     */
    public function comparatives(): void
    {
        try {
            $this->validateAccess();

            $userId = Auth::id();
            $year   = (int)($_GET['year'] ?? date('Y'));
            $month  = (int)($_GET['month'] ?? date('m'));
            $accountId = $this->resolveAccountId();

            $this->validateDateParams($year, $month);

            $data = $this->comparativesService->generate($userId, $year, $month, $accountId);

            Response::success($data);
        } catch (InvalidArgumentException $e) {
            $this->handleValidationError($e);
        } catch (\Throwable $e) {
            LogService::error('Erro ao gerar comparativos.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id()
            ]);
            Response::error('Erro ao gerar comparativos.', 500);
        }
    }

    /**
     * Retorna relatório detalhado de um cartão de crédito específico
     * GET /api/reports/card-details/:id
     */
    public function cardDetails(int $id = 0): void
    {
        try {
            $this->validateAccess();

            if ($id <= 0) {
                Response::error('ID do cartão inválido', 400);
                return;
            }

            $userId = Auth::id();
            $mes = $_GET['mes'] ?? date('m');
            $ano = $_GET['ano'] ?? date('Y');

            // Validar formato de mês/ano
            if (!preg_match('/^\d{2}$/', $mes) || !preg_match('/^\d{4}$/', $ano)) {
                Response::error('Formato de mês/ano inválido', 400);
                return;
            }

            // Validar range de mês/ano
            $this->validateDateParams((int)$ano, (int)$mes);

            $data = $this->reportService->getCardDetailedReport($userId, $id, $mes, $ano);

            Response::success($data);
        } catch (\Exception $e) {
            // Cartão não encontrado ou erro de domínio
            $statusCode = str_contains($e->getMessage(), 'não encontrado') ? 404 : 500;
            LogService::warning('Erro ao buscar detalhes do cartão.', [
                'error' => $e->getMessage(),
                'card_id' => $id,
                'user_id' => Auth::id()
            ]);
            Response::error(
                $statusCode === 404 ? $e->getMessage() : 'Erro ao gerar relatório detalhado.',
                $statusCode
            );
        } catch (\Throwable $e) {
            LogService::error('Erro inesperado ao gerar relatório de cartão.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'card_id' => $id,
                'user_id' => Auth::id()
            ]);
            Response::error('Erro interno ao gerar relatório detalhado', 500);
        }
    }
}
