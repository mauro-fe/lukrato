<?php

namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Lib\Auth;
use Application\Builders\ReportExportBuilder;
use Application\Services\ExcelExportService;
use Application\Services\LogService;
use Application\Services\PdfExportService;
use Application\Services\ReportService;
use Application\DTO\ReportParameters;
use Carbon\Carbon;
use ValueError;
use InvalidArgumentException;

// --- Enums para Constantes (PHP 8.1+) ---

enum ReportType: string
{
    case DESPESAS_POR_CATEGORIA = 'despesas_por_categoria';
    case DESPESAS_ANUAIS_POR_CATEGORIA = 'despesas_anuais_por_categoria';
    case RECEITAS_ANUAIS_POR_CATEGORIA = 'receitas_anuais_por_categoria';
    case RECEITAS_POR_CATEGORIA = 'receitas_por_categoria';
    case SALDO_MENSAL = 'saldo_mensal';
    case RECEITAS_DESPESAS_DIARIO = 'receitas_despesas_diario';
    case EVOLUCAO_12M = 'evolucao_12m';
    case RECEITAS_DESPESAS_POR_CONTA = 'receitas_despesas_por_conta';
    case RESUMO_ANUAL = 'resumo_anual';

    public static function fromShorthand(string $shorthand): self
    {
        $map = [
            'rec'       => self::RECEITAS_POR_CATEGORIA,
            'des'       => self::DESPESAS_POR_CATEGORIA,
            'des_cat'   => self::DESPESAS_POR_CATEGORIA,
            'des_anual' => self::DESPESAS_ANUAIS_POR_CATEGORIA,
            'cat_anual' => self::DESPESAS_ANUAIS_POR_CATEGORIA,
            'anual_cat' => self::DESPESAS_ANUAIS_POR_CATEGORIA,
            'categorias_anuais' => self::DESPESAS_ANUAIS_POR_CATEGORIA,
            'rec_anual' => self::RECEITAS_ANUAIS_POR_CATEGORIA,
            'receitas_anuais' => self::RECEITAS_ANUAIS_POR_CATEGORIA,
            'receitas_anuais_categorias' => self::RECEITAS_ANUAIS_POR_CATEGORIA,
            'saldo'     => self::SALDO_MENSAL,
            'rd'        => self::RECEITAS_DESPESAS_DIARIO,
            'recdes'    => self::RECEITAS_DESPESAS_DIARIO,
            'evo'       => self::EVOLUCAO_12M,
            'conta'     => self::RECEITAS_DESPESAS_POR_CONTA,
            'por_conta' => self::RECEITAS_DESPESAS_POR_CONTA,
            'resumo'    => self::RESUMO_ANUAL,
            'anual'     => self::RESUMO_ANUAL,
        ];

        $normalized = strtolower(trim($shorthand));
        
        if (isset($map[$normalized])) {
            return $map[$normalized];
        }

        try {
            return self::from($normalized);
        } catch (ValueError) {
            throw new InvalidArgumentException("Tipo de relatório '{$shorthand}' inválido.");
        }
    }
}

enum LancamentoTipo: string
{
    case DESPESA = 'despesa';
    case RECEITA = 'receita';
}

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

    // --- Validação de Acesso ---

    private function validateAccess(): void
    {
        $this->requireAuth();

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

    // --- Construção de Parâmetros ---

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

    // --- Helpers de Request ---

    private function getQueryParam(string $key): ?string
    {
        $value = $this->request->get($key);
        
        return $value !== null ? (string)$value : null;
    }

    // --- Resposta ---

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

    // --- Tratamento de Erros ---

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
}
