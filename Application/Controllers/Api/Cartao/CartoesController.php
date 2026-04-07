<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Cartao;

use Application\Controllers\ApiController;
use Application\Core\Response;
use Application\Enums\LogCategory;
use Application\Services\Cartao\CartaoApiWorkflowService;
use Application\Services\Cartao\CartaoCreditoService;
use Application\Services\Cartao\CartaoFaturaService;
use Application\Services\Demo\DemoPreviewService;
use Application\Services\Infrastructure\LogService;
use Application\Services\Plan\PlanLimitService;

class CartoesController extends ApiController
{
    private CartaoApiWorkflowService $workflowService;
    private DemoPreviewService $demoPreviewService;

    public function __construct(
        ?CartaoCreditoService $service = null,
        ?CartaoFaturaService $faturaService = null,
        ?PlanLimitService $planLimitService = null,
        ?CartaoApiWorkflowService $workflowService = null,
        ?DemoPreviewService $demoPreviewService = null
    ) {
        parent::__construct();

        $resolveCartaoService = function () use (&$service): CartaoCreditoService {
            $service = $this->resolveOrCreate(
                $service,
                CartaoCreditoService::class,
                static fn(): CartaoCreditoService => new CartaoCreditoService()
            );

            return $service;
        };

        $resolveFaturaService = function () use (&$faturaService): CartaoFaturaService {
            $faturaService = $this->resolveOrCreate(
                $faturaService,
                CartaoFaturaService::class,
                static fn(): CartaoFaturaService => new CartaoFaturaService()
            );

            return $faturaService;
        };

        $resolvePlanLimitService = function () use (&$planLimitService): PlanLimitService {
            $planLimitService = $this->resolveOrCreate(
                $planLimitService,
                PlanLimitService::class,
                static fn(): PlanLimitService => new PlanLimitService()
            );

            return $planLimitService;
        };

        $this->workflowService = $this->resolveOrCreate(
            $workflowService,
            CartaoApiWorkflowService::class,
            function () use ($resolveCartaoService, $resolveFaturaService, $resolvePlanLimitService): CartaoApiWorkflowService {
                return new CartaoApiWorkflowService(
                    $resolveCartaoService(),
                    $resolveFaturaService(),
                    $resolvePlanLimitService()
                );
            }
        );
        $this->demoPreviewService = $this->resolveOrCreate(
            $demoPreviewService,
            DemoPreviewService::class,
            static fn(): DemoPreviewService => new DemoPreviewService()
        );
    }

    public function index(): Response
    {
        $userId = $this->requireApiUserIdAndReleaseSessionOrFail();

        if (
            $this->getIntQuery('preview', 0) === 1
            && $this->demoPreviewService->shouldUsePreview($userId)
        ) {
            return Response::successResponse($this->demoPreviewService->cartoes());
        }

        return Response::successResponse($this->workflowService->listCards(
            $userId,
            $this->resolveOptionalIntQuery('conta_id'),
            $this->resolveBooleanQuery('only_active', true),
            $this->resolveBooleanQuery('archived', false)
        ));
    }

    public function show(int $id): Response
    {
        $userId = $this->userId();
        $cartao = $this->workflowService->showCard($id, $userId);

        if (!$cartao) {
            return Response::errorResponse('Cartão não encontrado', 404);
        }

        return Response::successResponse($cartao);
    }

    public function store(): Response
    {
        $userId = $this->userId();
        $result = $this->workflowService->createCard($userId, $this->getRequestPayload());

        return $this->respondApiWorkflowResult(
            $result,
            preserveSuccessMeta: true,
            useWorkflowFailureOnFailure: false
        );
    }

    public function update(int $id): Response
    {
        $userId = $this->userId();
        $result = $this->workflowService->updateCard($id, $userId, $this->getRequestPayload());

        return $this->respondApiWorkflowResult(
            $result,
            useWorkflowFailureOnFailure: false
        );
    }

    public function deactivate(int $id): Response
    {
        return $this->handleCardActionResult($this->workflowService->deactivateCard($id, $this->userId()));
    }

    public function reactivate(int $id): Response
    {
        return $this->handleCardActionResult($this->workflowService->reactivateCard($id, $this->userId()));
    }

    public function archive(int $id): Response
    {
        return $this->handleCardActionResult($this->workflowService->archiveCard($id, $this->userId()));
    }

    public function restore(int $id): Response
    {
        return $this->handleCardActionResult($this->workflowService->restoreCard($id, $this->userId()));
    }

    public function delete(int $id): Response
    {
        $userId = $this->userId();
        $result = $this->workflowService->deleteCard($id, $userId, $this->getRequestPayload());

        $requiresConfirmation = (bool) ($result['requires_confirmation'] ?? false);

        return $this->respondWorkflowResult(
            $result,
            $requiresConfirmation ? 422 : 404,
            'Erro ao excluir cartao',
            [
                'status' => $requiresConfirmation ? 'confirm_delete' : 'error',
                'requires_confirmation' => $requiresConfirmation,
                'total_lancamentos' => (int) ($result['total_lancamentos'] ?? 0),
            ],
            useWorkflowFailureOnFailure: false
        );
    }

    public function destroy(int $id): Response
    {
        return $this->archive($id);
    }

    public function updateLimit(int $id): Response
    {
        $result = $this->workflowService->refreshLimit($id, $this->userId());

        return $this->respondWorkflowResult(
            $result,
            404,
            'Cartao nao encontrado',
            null,
            [
                'limite_disponivel' => $result['limite_disponivel'] ?? null,
                'limite_utilizado' => $result['limite_utilizado'] ?? null,
                'percentual_uso' => $result['percentual_uso'] ?? null,
            ],
            useWorkflowFailureOnFailure: false
        );
    }

    public function summary(): Response
    {
        $userId = $this->userId();

        if ($this->demoPreviewService->shouldUsePreview($userId)) {
            return Response::successResponse($this->demoPreviewService->cartoesResumo());
        }

        return Response::successResponse($this->workflowService->getSummary($userId));
    }

    public function fatura(int $id): Response
    {
        $userId = $this->userId();
        $mes = $this->resolveQueryMonth();
        $ano = $this->resolveQueryYear();

        if ($mes < 1 || $mes > 12) {
            return Response::errorResponse('Mês inválido', 400);
        }

        return $this->successOrNotFound(
            fn(): mixed => $this->workflowService->getInvoice($id, $mes, $ano, $userId),
            'Fatura não encontrada.'
        );
    }

    public function pagarFatura(int $id): Response
    {
        $userId = $this->userId();

        return $this->successOrDomainError(
            fn(): mixed => $this->workflowService->payInvoice($id, $userId, $this->getRequestPayload()),
            'Não foi possivel pagar a fatura.',
            400
        );
    }

    public function pagarParcelas(int $id): Response
    {
        $userId = $this->userId();
        $payload = $this->getRequestPayload();

        if (empty($payload['parcela_ids'] ?? [])) {
            return Response::errorResponse('Nenhuma parcela selecionada', 400);
        }

        return $this->successOrDomainError(
            fn(): mixed => $this->workflowService->payInstallments($id, $userId, $payload),
            'Não foi possivel pagar as parcelas.',
            400
        );
    }

    public function faturasPendentes(int $id): Response
    {
        $userId = $this->userId();

        return $this->successOrNotFound(
            fn(): mixed => ['meses' => $this->workflowService->getPendingInvoices($id, $userId)],
            'Cartao não encontrado.'
        );
    }

    public function faturasHistorico(int $id): Response
    {
        $userId = $this->userId();
        $limite = $this->getIntQuery('limite', 12);

        return $this->successOrNotFound(
            fn(): mixed => $this->workflowService->getInvoiceHistory($id, $userId, $limite),
            'Cartao não encontrado.'
        );
    }

    public function parcelamentosResumo(int $id): Response
    {
        $userId = $this->userId();

        if (!$this->workflowService->showCard($id, $userId)) {
            return Response::errorResponse('Cartão não encontrado', 404);
        }

        $mes = $this->resolveQueryMonth();
        $ano = $this->resolveQueryYear();

        return Response::successResponse($this->workflowService->getInstallmentsSummary($id, $mes, $ano, $userId));
    }

    public function alertas(): Response
    {
        $userId = $this->userId();

        try {
            return Response::successResponse($this->workflowService->getAlerts($userId));
        } catch (\Exception $e) {
            $errorMeta = $this->internalErrorMeta($e, 'Erro ao carregar alertas.', [
                'action' => 'alertas',
                'user_id' => $userId,
            ], LogCategory::CARTAO);

            return Response::successResponse([
                'total' => 0,
                'alertas' => [],
                'por_tipo' => [
                    'vencimentos' => 0,
                    'limites_baixos' => 0,
                ],
                'error' => 'Erro ao carregar alertas.',
                'error_id' => $errorMeta['error_id'],
                'request_id' => $errorMeta['request_id'],
            ]);
        }
    }

    public function validarIntegridade(): Response
    {
        $userId = $this->userId();
        $corrigir = $this->getQuery('corrigir') === 'true';

        return $this->successOrInternalError(
            fn(): mixed => $this->workflowService->validateIntegrity($userId, $corrigir),
            'Erro ao validar integridade do cartao.'
        );
    }

    public function statusFatura(int $id): Response
    {
        $userId = $this->userId();
        $mes = $this->resolveRequiredIntQuery('mes');
        $ano = $this->resolveRequiredIntQuery('ano');

        if (!$mes || !$ano) {
            return Response::errorResponse('Mês e ano são obrigatórios', 400);
        }

        return $this->successOrInternalError(
            fn(): mixed => $this->workflowService->getInvoiceStatus($id, $mes, $ano, $userId) ?? ['pago' => false],
            'Erro ao carregar status da fatura.'
        );
    }

    public function desfazerPagamentoFatura(int $id): Response
    {
        $userId = $this->userId();
        $payload = $this->getRequestPayload();
        $mes = isset($payload['mes']) ? (int) $payload['mes'] : null;
        $ano = isset($payload['ano']) ? (int) $payload['ano'] : null;

        if (!$mes || !$ano) {
            return Response::errorResponse('Mês e ano são obrigatórios', 400);
        }

        return $this->successOrDomainError(
            fn(): mixed => $this->workflowService->undoInvoicePayment($id, $mes, $ano, $userId),
            'Não foi possivel desfazer o pagamento da fatura.',
            400,
            function (\Exception $e) use ($id, $mes, $ano, $userId): void {
                LogService::captureException($e, LogCategory::FATURA, [
                    'action' => 'desfazer_pagamento_fatura',
                    'cartao_id' => $id,
                    'mes' => $mes,
                    'ano' => $ano,
                    'user_id' => $userId,
                ]);
            }
        );
    }

    public function desfazerPagamentoParcela(int $id): Response
    {
        $userId = $this->userId();

        return $this->successOrDomainError(
            fn(): mixed => $this->workflowService->undoInstallmentPayment($id, $userId),
            'Não foi possivel desfazer o pagamento da parcela.',
            400,
            function (\Exception $e) use ($id, $userId): void {
                LogService::captureException($e, LogCategory::FATURA, [
                    'action' => 'desfazer_pagamento_parcela',
                    'parcela_id' => $id,
                    'user_id' => $userId,
                ]);
            }
        );
    }

    public function recorrencias(): Response
    {
        $userId = $this->userId();

        return $this->successOrInternalError(
            fn(): mixed => $this->workflowService->listRecurring($userId),
            'Erro ao listar recorrencias do cartao.',
            500,
            [],
            function (\Exception $e) use ($userId): void {
                LogService::captureException($e, LogCategory::CARTAO, [
                    'action' => 'listar_recorrencias',
                    'user_id' => $userId,
                ]);
            }
        );
    }

    public function recorrenciasCartao(int $id): Response
    {
        $userId = $this->userId();

        return $this->successOrInternalError(
            fn(): mixed => $this->workflowService->listRecurring($userId, $id),
            'Erro ao listar recorrencias do cartao.',
            500,
            ['cartao_id' => $id],
            function (\Exception $e) use ($id, $userId): void {
                LogService::captureException($e, LogCategory::CARTAO, [
                    'action' => 'listar_recorrencias_cartao',
                    'cartao_id' => $id,
                    'user_id' => $userId,
                ]);
            }
        );
    }

    public function cancelarRecorrencia(int $id): Response
    {
        $userId = $this->userId();

        return $this->executeOrInternalError(
            function () use ($id, $userId): Response {
                $resultado = $this->workflowService->cancelRecurring($id, $userId);
                return $this->respondWorkflowResult(
                    $resultado,
                    400,
                    'Erro ao cancelar recorrencia',
                    null,
                    null,
                    false
                );
            },
            'Erro ao cancelar recorrencia do cartao.',
            500,
            ['item_pai_id' => $id],
            function (\Exception $e) use ($id, $userId): void {
                LogService::captureException($e, LogCategory::CARTAO, [
                    'action' => 'cancelar_recorrencia',
                    'item_pai_id' => $id,
                    'user_id' => $userId,
                ]);
            }
        );
    }

    /**
     * @param callable():mixed $resolver
     * @param callable(\Exception):Response $onExceptionResponse
     * @param callable(\Exception):void|null $onException
     */
    private function resolveOrCatch(
        callable $resolver,
        callable $onExceptionResponse,
        ?callable $onException = null
    ): Response {
        try {
            return Response::successResponse($resolver());
        } catch (\Exception $e) {
            if ($onException !== null) {
                $onException($e);
            }

            return $onExceptionResponse($e);
        }
    }

    /**
     * @param callable():Response $operation
     * @param callable(\Exception):Response $onExceptionResponse
     * @param callable(\Exception):void|null $onException
     */
    private function executeOrCatch(
        callable $operation,
        callable $onExceptionResponse,
        ?callable $onException = null
    ): Response {
        try {
            return $operation();
        } catch (\Exception $e) {
            if ($onException !== null) {
                $onException($e);
            }

            return $onExceptionResponse($e);
        }
    }

    /**
     * @param callable():mixed $resolver
     */
    private function successOrNotFound(callable $resolver, string $fallbackMessage): Response
    {
        return $this->resolveOrCatch(
            $resolver,
            fn(\Exception $e): Response => $this->notFoundFromThrowable($e, $fallbackMessage)
        );
    }

    /**
     * @param callable():mixed $resolver
     * @param callable(\Exception):void|null $onException
     */
    private function successOrDomainError(
        callable $resolver,
        string $fallbackMessage,
        int $status = 400,
        ?callable $onException = null
    ): Response {
        return $this->resolveOrCatch(
            $resolver,
            fn(\Exception $e): Response => $this->domainErrorResponse($e, $fallbackMessage, $status),
            $onException
        );
    }

    /**
     * @param callable():mixed $resolver
     * @param callable(\Exception):void|null $onException
     */
    private function successOrInternalError(
        callable $resolver,
        string $fallbackMessage,
        int $status = 500,
        array $context = [],
        ?callable $onException = null
    ): Response {
        return $this->resolveOrCatch(
            $resolver,
            fn(\Exception $e): Response => $this->internalErrorResponse($e, $fallbackMessage, $status, $context),
            $onException
        );
    }

    /**
     * @param callable():Response $operation
     * @param callable(\Exception):void|null $onException
     */
    private function executeOrInternalError(
        callable $operation,
        string $fallbackMessage,
        int $status = 500,
        array $context = [],
        ?callable $onException = null
    ): Response {
        return $this->executeOrCatch(
            $operation,
            fn(\Exception $e): Response => $this->internalErrorResponse($e, $fallbackMessage, $status, $context),
            $onException
        );
    }

    private function handleCardActionResult(array $resultado): Response
    {
        return $this->respondWorkflowResult($resultado, 404, 'Cartao nao encontrado', null, null, false);
    }

    private function resolveBooleanQuery(string $key, bool $default): bool
    {
        return (int) $this->getQuery($key, $default ? 1 : 0) === 1;
    }

    private function resolveOptionalIntQuery(string $key): ?int
    {
        $value = $this->getQuery($key);

        return $value !== null ? (int) $value : null;
    }

    private function resolveRequiredIntQuery(string $key): ?int
    {
        $value = $this->getQuery($key);

        return $value !== null ? (int) $value : null;
    }

    private function resolveQueryMonth(): int
    {
        return $this->getIntQuery('mes', (int) date('n'));
    }

    private function resolveQueryYear(): int
    {
        return $this->getIntQuery('ano', (int) date('Y'));
    }

    private function userId(): int
    {
        return $this->requireApiUserIdOrFail();
    }

    /**
     * @param array<string, mixed> $result
     * @param mixed $data
     * @return array<string, mixed>
     */
    private function toWorkflowResult(array $result, mixed $data = null): array
    {
        return [
            'success' => (bool) ($result['success'] ?? false),
            'data' => $data ?? $result,
        ];
    }

    /**
     * @param array<string, mixed> $result
     * @param array<string, mixed>|null $errors
     * @param mixed $data
     */
    private function respondWorkflowResult(
        array $result,
        int $failureStatus,
        string $fallbackFailureMessage,
        ?array $errors = null,
        mixed $data = null,
        bool $useWorkflowFailureOnFailure = false
    ): Response {
        $workflowResult = $this->toWorkflowResult($result, $data);
        $this->applyWorkflowFailure($workflowResult, $result, $failureStatus, $fallbackFailureMessage, $errors);

        return $this->respondApiWorkflowResult(
            $workflowResult,
            useWorkflowFailureOnFailure: $useWorkflowFailureOnFailure
        );
    }

    /**
     * @param array<string, mixed> $workflowResult
     * @param array<string, mixed> $sourceResult
     * @param array<string, mixed>|null $errors
     */
    private function applyWorkflowFailure(
        array &$workflowResult,
        array $sourceResult,
        int $status,
        string $fallbackMessage,
        ?array $errors = null
    ): void {
        if ($workflowResult['success'] ?? false) {
            return;
        }

        $workflowResult['status'] = $status;
        $workflowResult['message'] = (string) ($sourceResult['message'] ?? $fallbackMessage);

        if ($errors !== null) {
            $workflowResult['errors'] = $errors;
        }
    }
}
