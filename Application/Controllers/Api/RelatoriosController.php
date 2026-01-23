<?php

namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Lib\Auth;
use Application\Models\Usuario;
use Application\Builders\ReportExportBuilder;
use Application\Services\ExcelExportService;
use Application\Services\LogService;
use Application\Services\PdfExportService;
use Application\Services\ReportService;
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

    public function __construct()
    {
        parent::__construct();
        $this->reportService = new ReportService();
        $this->exportBuilder = new ReportExportBuilder();
        $this->pdfExport = new PdfExportService();
        $this->excelExport = new ExcelExportService();
    }

    public function index(): void
    {
        try {
            $this->validateAccess();

            $params = $this->buildReportParameters();
            $type = $this->resolveReportType();

            $result = $this->reportService->generateReport($type, $params);

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
            $user = Usuario::find(Auth::id());
            if (!$user || !$user->isPro()) {
                Response::error('Exportação de relatórios é um recurso exclusivo do plano PRO.', 403);
                return;
            }

            $params = $this->buildReportParameters();
            $type = $this->resolveReportType();
            $format = $this->resolveExportFormat();

            $report = $this->reportService->generateReport($type, $params);
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

            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = Carbon::create($year, $month, 1)->endOfMonth();

            // Buscar totais de lançamentos (sem filtrar por pago)
            $lancamentos = \Application\Models\Lancamento::where('user_id', $userId)
                ->whereBetween('data', [$startDate->toDateString(), $endDate->toDateString()])
                ->selectRaw('
                    SUM(CASE WHEN tipo = "receita" THEN valor ELSE 0 END) as total_receitas,
                    SUM(CASE WHEN tipo = "despesa" THEN valor ELSE 0 END) as total_despesas
                ')
                ->first();

            // Buscar lançamentos de cartão no período atual e próximos 3 meses
            // (para considerar parcelamentos que começam depois mas impactam este mês)
            $endDateExtended = (clone $endDate)->addMonths(3);

            $totalCartoes = \Application\Models\Lancamento::where('user_id', $userId)
                ->whereNotNull('cartao_credito_id')
                ->where('tipo', 'despesa')
                ->whereBetween('data', [$startDate->toDateString(), $endDateExtended->toDateString()])
                ->sum('valor');

            Response::success([
                'totalReceitas' => (float)($lancamentos->total_receitas ?? 0),
                'totalDespesas' => (float)($lancamentos->total_despesas ?? 0),
                'saldo' => (float)(($lancamentos->total_receitas ?? 0) - ($lancamentos->total_despesas ?? 0)),
                'totalCartoes' => (float)($totalCartoes ?? 0)
            ]);
        } catch (\Throwable $e) {
            error_log("Erro no summary: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            Response::error($e->getMessage(), 500);
        }
    }

    private function validateAccess(): void
    {
        $this->requireAuthApi();

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

        Response::error('Erro ao gerar relatório.', 500, [
            'exception' => $e->getMessage()
        ]);
    }

    /**
     * Gerar insights automáticos baseados nos dados financeiros do usuário
     */
    public function insights(): void
    {
        try {
            $this->validateAccess();

            $userId = Auth::id();
            $year = (int)($_GET['year'] ?? date('Y'));
            $month = (int)($_GET['month'] ?? date('m'));

            $currentStart = Carbon::create($year, $month, 1)->startOfMonth();
            $currentEnd = Carbon::create($year, $month, 1)->endOfMonth();
            $previousStart = (clone $currentStart)->subMonth()->startOfMonth();
            $previousEnd = (clone $currentStart)->subMonth()->endOfMonth();

            $insights = [];

            // Análise do mês atual vs anterior
            $currentData = \Application\Models\Lancamento::where('user_id', $userId)
                ->whereBetween('data', [$currentStart->toDateString(), $currentEnd->toDateString()])
                ->selectRaw('
                    SUM(CASE WHEN tipo = "receita" THEN valor ELSE 0 END) as receitas,
                    SUM(CASE WHEN tipo = "despesa" THEN valor ELSE 0 END) as despesas
                ')
                ->first();

            $previousData = \Application\Models\Lancamento::where('user_id', $userId)
                ->whereBetween('data', [$previousStart->toDateString(), $previousEnd->toDateString()])
                ->selectRaw('
                    SUM(CASE WHEN tipo = "receita" THEN valor ELSE 0 END) as receitas,
                    SUM(CASE WHEN tipo = "despesa" THEN valor ELSE 0 END) as despesas
                ')
                ->first();

            $currentReceitas = (float)($currentData->receitas ?? 0);
            $currentDespesas = (float)($currentData->despesas ?? 0);
            $previousReceitas = (float)($previousData->receitas ?? 0);
            $previousDespesas = (float)($previousData->despesas ?? 0);

            // Insight 1: Comparação de despesas
            if ($previousDespesas > 0) {
                $despesasVariation = (($currentDespesas - $previousDespesas) / $previousDespesas) * 100;
                if (abs($despesasVariation) > 10) {
                    $insights[] = [
                        'type' => $despesasVariation > 0 ? 'warning' : 'success',
                        'icon' => $despesasVariation > 0 ? 'arrow-trend-up' : 'arrow-trend-down',
                        'title' => $despesasVariation > 0 ? 'Despesas aumentaram' : 'Despesas reduziram',
                        'message' => sprintf(
                            'Suas despesas %s %.1f%% em relação ao mês anterior',
                            $despesasVariation > 0 ? 'aumentaram' : 'reduziram',
                            abs($despesasVariation)
                        )
                    ];
                }
            }

            // Insight 2: Análise de saldo
            $currentSaldo = $currentReceitas - $currentDespesas;
            if ($currentSaldo < 0) {
                $insights[] = [
                    'type' => 'danger',
                    'icon' => 'exclamation-triangle',
                    'title' => 'Saldo negativo',
                    'message' => 'Suas despesas estão maiores que suas receitas este mês. Considere revisar seus gastos.'
                ];
            } elseif ($currentReceitas > 0) {
                $taxaEconomia = ($currentSaldo / $currentReceitas) * 100;
                if ($taxaEconomia > 20) {
                    $insights[] = [
                        'type' => 'success',
                        'icon' => 'piggy-bank',
                        'title' => 'Ótima economia!',
                        'message' => sprintf('Você está economizando %.1f%% de sua renda este mês!', $taxaEconomia)
                    ];
                }
            }

            // Insight 3: Categoria com maior gasto
            $topCategoria = \Application\Models\Lancamento::where('lancamentos.user_id', $userId)
                ->where('lancamentos.tipo', 'despesa')
                ->whereBetween('lancamentos.data', [$currentStart->toDateString(), $currentEnd->toDateString()])
                ->join('categorias', 'lancamentos.categoria_id', '=', 'categorias.id')
                ->selectRaw('categorias.nome, SUM(lancamentos.valor) as total')
                ->groupBy('categorias.id', 'categorias.nome')
                ->orderByDesc('total')
                ->first();

            if ($topCategoria && $topCategoria->total > 0) {
                $percentual = $currentDespesas > 0 ? ($topCategoria->total / $currentDespesas) * 100 : 0;
                if ($percentual > 30) {
                    $insights[] = [
                        'type' => 'info',
                        'icon' => 'chart-pie',
                        'title' => 'Categoria em destaque',
                        'message' => sprintf(
                            '%s representa %.1f%% dos seus gastos (R$ %.2f)',
                            $topCategoria->nome,
                            $percentual,
                            $topCategoria->total
                        )
                    ];
                }
            }

            // Insight 4: Alerta de cartão próximo do limite
            $cartoesComLimite = \Application\Models\CartaoCredito::where('user_id', $userId)
                ->whereNotNull('limite_total')
                ->where('limite_total', '>', 0)
                ->get();

            $qtdCartoesAlerta = 0;
            foreach ($cartoesComLimite as $cartao) {
                $gasto = \Application\Models\Lancamento::where('user_id', $userId)
                    ->where('cartao_credito_id', $cartao->id)
                    ->whereBetween('data', [$currentStart->toDateString(), $currentEnd->toDateString()])
                    ->sum('valor');

                if ($cartao->limite_total > 0 && ($gasto / $cartao->limite_total) > 0.8) {
                    $qtdCartoesAlerta++;
                }
            }

            if ($qtdCartoesAlerta > 0) {
                $insights[] = [
                    'type' => 'warning',
                    'icon' => 'credit-card',
                    'title' => 'Atenção ao limite do cartão',
                    'message' => sprintf(
                        '%s %s próximo%s do limite',
                        $qtdCartoesAlerta,
                        $qtdCartoesAlerta > 1 ? 'cartões estão' : 'cartão está',
                        $qtdCartoesAlerta > 1 ? 's' : ''
                    )
                ];
            }

            // Se não houver insights específicos, adicionar mensagem positiva
            if (empty($insights)) {
                $insights[] = [
                    'type' => 'success',
                    'icon' => 'check-circle',
                    'title' => 'Tudo em ordem!',
                    'message' => 'Suas finanças estão equilibradas neste período.'
                ];
            }

            Response::success(['insights' => $insights]);
        } catch (\Throwable $e) {
            error_log("Erro no insights: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            Response::error($e->getMessage(), 500);
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
            $year = (int)($_GET['year'] ?? date('Y'));
            $month = (int)($_GET['month'] ?? date('m'));

            // Mês atual
            $currentStart = Carbon::create($year, $month, 1)->startOfMonth();
            $currentEnd = Carbon::create($year, $month, 1)->endOfMonth();

            // Mês anterior
            $previousMonthStart = (clone $currentStart)->subMonth()->startOfMonth();
            $previousMonthEnd = (clone $currentStart)->subMonth()->endOfMonth();

            // Ano atual
            $currentYearStart = Carbon::create($year, 1, 1)->startOfDay();
            $currentYearEnd = Carbon::create($year, 12, 31)->endOfDay();

            // Ano anterior
            $previousYearStart = Carbon::create($year - 1, 1, 1)->startOfDay();
            $previousYearEnd = Carbon::create($year - 1, 12, 31)->endOfDay();

            // Dados mês atual
            $currentMonth = \Application\Models\Lancamento::where('user_id', $userId)
                ->whereBetween('data', [$currentStart->toDateString(), $currentEnd->toDateString()])
                ->selectRaw('
                    SUM(CASE WHEN tipo = "receita" THEN valor ELSE 0 END) as receitas,
                    SUM(CASE WHEN tipo = "despesa" THEN valor ELSE 0 END) as despesas
                ')
                ->first();

            // Dados mês anterior
            $previousMonth = \Application\Models\Lancamento::where('user_id', $userId)
                ->whereBetween('data', [$previousMonthStart->toDateString(), $previousMonthEnd->toDateString()])
                ->selectRaw('
                    SUM(CASE WHEN tipo = "receita" THEN valor ELSE 0 END) as receitas,
                    SUM(CASE WHEN tipo = "despesa" THEN valor ELSE 0 END) as despesas
                ')
                ->first();

            // Dados ano atual
            $currentYear = \Application\Models\Lancamento::where('user_id', $userId)
                ->whereBetween('data', [$currentYearStart->toDateString(), $currentYearEnd->toDateString()])
                ->selectRaw('
                    SUM(CASE WHEN tipo = "receita" THEN valor ELSE 0 END) as receitas,
                    SUM(CASE WHEN tipo = "despesa" THEN valor ELSE 0 END) as despesas
                ')
                ->first();

            // Dados ano anterior
            $previousYear = \Application\Models\Lancamento::where('user_id', $userId)
                ->whereBetween('data', [$previousYearStart->toDateString(), $previousYearEnd->toDateString()])
                ->selectRaw('
                    SUM(CASE WHEN tipo = "receita" THEN valor ELSE 0 END) as receitas,
                    SUM(CASE WHEN tipo = "despesa" THEN valor ELSE 0 END) as despesas
                ')
                ->first();

            // Calcular variações
            $monthlyComparison = [
                'current' => [
                    'receitas' => (float)($currentMonth->receitas ?? 0),
                    'despesas' => (float)($currentMonth->despesas ?? 0),
                    'saldo' => (float)(($currentMonth->receitas ?? 0) - ($currentMonth->despesas ?? 0))
                ],
                'previous' => [
                    'receitas' => (float)($previousMonth->receitas ?? 0),
                    'despesas' => (float)($previousMonth->despesas ?? 0),
                    'saldo' => (float)(($previousMonth->receitas ?? 0) - ($previousMonth->despesas ?? 0))
                ],
                'variation' => [
                    'receitas' => $this->calculateVariation($previousMonth->receitas ?? 0, $currentMonth->receitas ?? 0),
                    'despesas' => $this->calculateVariation($previousMonth->despesas ?? 0, $currentMonth->despesas ?? 0),
                    'saldo' => $this->calculateVariation(
                        ($previousMonth->receitas ?? 0) - ($previousMonth->despesas ?? 0),
                        ($currentMonth->receitas ?? 0) - ($currentMonth->despesas ?? 0)
                    )
                ]
            ];

            $yearlyComparison = [
                'current' => [
                    'receitas' => (float)($currentYear->receitas ?? 0),
                    'despesas' => (float)($currentYear->despesas ?? 0),
                    'saldo' => (float)(($currentYear->receitas ?? 0) - ($currentYear->despesas ?? 0))
                ],
                'previous' => [
                    'receitas' => (float)($previousYear->receitas ?? 0),
                    'despesas' => (float)($previousYear->despesas ?? 0),
                    'saldo' => (float)(($previousYear->receitas ?? 0) - ($previousYear->despesas ?? 0))
                ],
                'variation' => [
                    'receitas' => $this->calculateVariation($previousYear->receitas ?? 0, $currentYear->receitas ?? 0),
                    'despesas' => $this->calculateVariation($previousYear->despesas ?? 0, $currentYear->despesas ?? 0),
                    'saldo' => $this->calculateVariation(
                        ($previousYear->receitas ?? 0) - ($previousYear->despesas ?? 0),
                        ($currentYear->receitas ?? 0) - ($currentYear->despesas ?? 0)
                    )
                ]
            ];

            Response::success([
                'monthly' => $monthlyComparison,
                'yearly' => $yearlyComparison
            ]);
        } catch (\Throwable $e) {
            error_log("Erro no comparatives: " . $e->getMessage());
            Response::error($e->getMessage(), 500);
        }
    }

    private function calculateVariation(float $previous, float $current): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100.0 : 0.0;
        }
        return round((($current - $previous) / $previous) * 100, 2);
    }

    /**
     * Retorna relatório detalhado de um cartão de crédito específico
     * GET /api/reports/card-details/:id
     */
    public function cardDetails(int $id = 0): void
    {
        error_log("🔍 cardDetails chamado com ID: $id");
        error_log("🔍 GET params: " . json_encode($_GET));

        try {
            $this->validateAccess();

            if ($id <= 0) {
                error_log("❌ ID inválido: $id");
                Response::error('ID do cartão inválido', 400);
                return;
            }

            $userId = Auth::id();
            $mes = $_GET['mes'] ?? date('m');
            $ano = $_GET['ano'] ?? date('Y');

            error_log("✅ Processando: userId=$userId, cardId=$id, mes=$mes, ano=$ano");

            // Validar formato de mês/ano
            if (!preg_match('/^\d{2}$/', $mes) || !preg_match('/^\d{4}$/', $ano)) {
                Response::error('Formato de mês/ano inválido', 400);
                return;
            }

            $data = $this->reportService->getCardDetailedReport($userId, $id, $mes, $ano);

            error_log("✅ Dados recebidos do service: " . json_encode(array_keys($data)));

            Response::success($data);
        } catch (\Exception $e) {
            error_log("❌ Exception: " . $e->getMessage());
            error_log("❌ Trace: " . $e->getTraceAsString());
            Response::error($e->getMessage(), 404);
        } catch (\Throwable $e) {
            error_log("❌ Throwable: " . $e->getMessage());
            error_log("❌ Trace: " . $e->getTraceAsString());
            Response::error('Erro interno ao gerar relatório detalhado', 500);
        }
    }
}
