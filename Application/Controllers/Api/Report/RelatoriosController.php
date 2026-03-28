<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Report;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Models\Usuario;
use Application\Services\Infrastructure\LogService;
use Application\Services\Report\ReportApiWorkflowService;
use InvalidArgumentException;
use Throwable;

class RelatoriosController extends BaseController
{
    private ?Usuario $currentUser = null;

    public function __construct(
        ?\Application\Services\Report\ReportService $reportService = null,
        ?\Application\Builders\ReportExportBuilder $exportBuilder = null,
        ?\Application\Services\Report\PdfExportService $pdfExport = null,
        ?\Application\Services\Report\ExcelExportService $excelExport = null,
        ?\Application\Services\Financeiro\InsightsService $insightsService = null,
        ?\Application\Services\Financeiro\ComparativesService $comparativesService = null,
        private ?ReportApiWorkflowService $workflowService = null
    ) {
        parent::__construct();

        $reportService ??= new \Application\Services\Report\ReportService();
        $exportBuilder ??= new \Application\Builders\ReportExportBuilder();
        $pdfExport ??= new \Application\Services\Report\PdfExportService();
        $excelExport ??= new \Application\Services\Report\ExcelExportService();
        $insightsService ??= new \Application\Services\Financeiro\InsightsService();
        $comparativesService ??= new \Application\Services\Financeiro\ComparativesService();

        $this->workflowService ??= new ReportApiWorkflowService(
            $reportService,
            $exportBuilder,
            $pdfExport,
            $excelExport,
            $insightsService,
            $comparativesService,
            new \Application\Services\Gamification\GamificationService()
        );
    }

    public function index(): Response
    {
        $accessResponse = $this->validateAccessOrResponse();
        if ($accessResponse !== null) {
            return $accessResponse;
        }

        try {
            $generated = $this->workflowService->generateReport(
                (int) $this->userId,
                $this->getCurrentUser(),
                $this->collectQueryParams()
            );

            return $this->sendSuccessResponse(
                $generated['result'],
                $generated['type'],
                $generated['params']
            );
        } catch (InvalidArgumentException $e) {
            return $this->handleValidationError($e);
        } catch (Throwable $e) {
            return $this->handleUnexpectedError($e);
        }
    }

    public function export(): Response
    {
        $accessResponse = $this->validateAccessOrResponse();
        if ($accessResponse !== null) {
            return $accessResponse;
        }

        try {
            $user = $this->getCurrentUser();
            if (!$user || !$user->isPro()) {
                return Response::errorResponse('Exportação de relatórios é um recurso exclusivo do plano PRO.', 403);
            }

            $export = $this->workflowService->exportReport(
                (int) $this->userId,
                $user,
                $this->collectQueryParams()
            );

            return $this->sendBinaryResponse($export['content'], $export['filename'], $export['mime']);
        } catch (InvalidArgumentException $e) {
            return $this->handleValidationError($e);
        } catch (Throwable $e) {
            return $this->handleUnexpectedError($e);
        }
    }

    public function summary(): Response
    {
        $user = $this->requireApiUserAndReleaseSessionOrFail();

        try {
            $summary = $this->workflowService->buildSummary((int) $user->id, $this->collectQueryParams());

            return Response::successResponse($summary);
        } catch (InvalidArgumentException $e) {
            return $this->handleValidationError($e);
        } catch (Throwable $e) {
            LogService::error('Erro ao gerar resumo.', [
                'error' => $e->getMessage(),
                'user_id' => $this->userId,
            ]);

            return Response::errorResponse('Erro ao gerar resumo.', 500);
        }
    }

    public function insightsTeaser(): Response
    {
        $user = $this->requireApiUserAndReleaseSessionOrFail();

        try {
            $result = $this->workflowService->buildInsightsTeaser(
                (int) $user->id,
                $user,
                $this->collectQueryParams()
            );

            return Response::successResponse($result);
        } catch (InvalidArgumentException $e) {
            return $this->handleValidationError($e);
        } catch (Throwable $e) {
            LogService::error('Erro ao gerar insights teaser.', [
                'error' => $e->getMessage(),
                'user_id' => $this->userId,
            ]);

            return Response::errorResponse('Erro ao gerar insights.', 500);
        }
    }

    public function insights(): Response
    {
        $accessResponse = $this->validateAccessOrResponse();
        if ($accessResponse !== null) {
            return $accessResponse;
        }

        try {
            $result = $this->workflowService->buildInsights((int) $this->userId, $this->collectQueryParams());

            return Response::successResponse($result);
        } catch (InvalidArgumentException $e) {
            return $this->handleValidationError($e);
        } catch (Throwable $e) {
            LogService::error('Erro ao gerar insights.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $this->userId,
            ]);

            return Response::errorResponse('Erro ao gerar insights.', 500);
        }
    }

    public function comparatives(): Response
    {
        $accessResponse = $this->validateAccessOrResponse();
        if ($accessResponse !== null) {
            return $accessResponse;
        }

        try {
            $data = $this->workflowService->buildComparatives((int) $this->userId, $this->collectQueryParams());

            return Response::successResponse($data);
        } catch (InvalidArgumentException $e) {
            return $this->handleValidationError($e);
        } catch (Throwable $e) {
            LogService::error('Erro ao gerar comparativos.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $this->userId,
            ]);

            return Response::errorResponse('Erro ao gerar comparativos.', 500);
        }
    }

    public function cardDetails(int $id = 0): Response
    {
        $accessResponse = $this->validateAccessOrResponse();
        if ($accessResponse !== null) {
            return $accessResponse;
        }

        try {
            $data = $this->workflowService->buildCardDetails((int) $this->userId, $id, $this->collectQueryParams());

            return Response::successResponse($data);
        } catch (InvalidArgumentException $e) {
            return $this->domainErrorResponse($e, 'Parametros invalidos para o relatorio detalhado.', 400);
        } catch (\Exception $e) {
            $statusCode = str_contains($e->getMessage(), 'não encontrado')
                || str_contains($e->getMessage(), 'nao encontrado')
                ? 404
                : 500;

            LogService::warning('Erro ao buscar detalhes do cartão.', [
                'error' => $e->getMessage(),
                'card_id' => $id,
                'user_id' => $this->userId,
            ]);

            if ($statusCode === 404) {
                return $this->notFoundFromThrowable($e, 'Cartao nao encontrado.');
            }

            return Response::errorResponse('Erro ao gerar relatorio detalhado.', $statusCode);
        } catch (Throwable $e) {
            LogService::error('Erro inesperado ao gerar relatório de cartão.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'card_id' => $id,
                'user_id' => $this->userId,
            ]);

            return Response::errorResponse('Erro interno ao gerar relatório detalhado', 500);
        }
    }

    private function validateAccessOrResponse(): ?Response
    {
        $user = $this->requireApiUserAndReleaseSessionOrFail();
        $this->currentUser = $user;

        if (!$this->userCanAccessReports($user)) {
            return Response::forbiddenResponse('Relatórios são exclusivos do plano Pro.');
        }

        return null;
    }

    private function userCanAccessReports(Usuario $user): bool
    {
        return !method_exists($user, 'podeAcessar') || $user->podeAcessar('reports');
    }

    private function getCurrentUser(): ?Usuario
    {
        return $this->currentUser ??= (($this->userId !== null) ? Usuario::find($this->userId) : null);
    }

    /**
     * @return array<string, string|null>
     */
    private function collectQueryParams(): array
    {
        return [
            'month' => $this->nullableQuery('month'),
            'year' => $this->nullableQuery('year'),
            'account_id' => $this->nullableQuery('account_id'),
            'include_transfers' => $this->nullableQuery('include_transfers'),
            'type' => $this->nullableQuery('type'),
            'format' => $this->nullableQuery('format'),
            'mes' => $this->nullableQuery('mes'),
            'ano' => $this->nullableQuery('ano'),
        ];
    }

    private function nullableQuery(string $key): ?string
    {
        $value = $this->getQuery($key);

        return $value === null ? null : (string) $value;
    }

    private function sendSuccessResponse(array $result, \Application\Enums\ReportType $type, \Application\DTO\ReportParameters $params): Response
    {
        return Response::successResponse(array_merge($result, [
            'type' => $type->value,
            'start' => $params->start->toDateString(),
            'end' => $params->end->toDateString(),
        ]));
    }

    private function sendBinaryResponse(string $content, string $fileName, string $mime): Response
    {
        return (new Response())
            ->setStatusCode(200)
            ->header('Content-Type', $mime)
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"')
            ->header('Content-Length', (string) mb_strlen($content, '8bit'))
            ->setContent($content)
            ->clearOutputBuffer();
    }

    private function handleValidationError(InvalidArgumentException $e): Response
    {
        LogService::warning('Falha de validação no relatório.', [
            'error' => $e->getMessage(),
            'user_id' => $this->userId ?? null,
        ]);

        return $this->domainErrorResponse($e, 'Parametros invalidos para o relatorio.', 422);
    }

    private function handleUnexpectedError(Throwable $e): Response
    {
        LogService::error('Erro inesperado ao gerar relatório.', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'user_id' => $this->userId ?? null,
        ]);

        return Response::errorResponse('Erro ao gerar relatório.', 500);
    }
}
