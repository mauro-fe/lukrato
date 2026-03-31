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

        $service ??= new CartaoCreditoService();
        $faturaService ??= new CartaoFaturaService();
        $planLimitService ??= new PlanLimitService();

        $this->workflowService = $workflowService
            ?? new CartaoApiWorkflowService($service, $faturaService, $planLimitService);
        $this->demoPreviewService = $demoPreviewService ?? new DemoPreviewService();
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
        $userId = $this->requireApiUserIdOrFail();
        $cartao = $this->workflowService->showCard($id, $userId);

        if (!$cartao) {
            return Response::errorResponse('Cartão não encontrado', 404);
        }

        return Response::successResponse($cartao);
    }

    public function store(): Response
    {
        $userId = $this->requireApiUserIdOrFail();
        $result = $this->workflowService->createCard($userId, $this->getRequestPayload());

        return $this->respondApiWorkflowResult(
            $result,
            preserveSuccessMeta: true,
            useWorkflowFailureOnFailure: false
        );
    }

    public function update(int $id): Response
    {
        $userId = $this->requireApiUserIdOrFail();
        $result = $this->workflowService->updateCard($id, $userId, $this->getRequestPayload());

        return $this->respondApiWorkflowResult(
            $result,
            useWorkflowFailureOnFailure: false
        );
    }

    public function deactivate(int $id): Response
    {
        return $this->handleCardActionResult($this->workflowService->deactivateCard($id, $this->requireApiUserIdOrFail()));
    }

    public function reactivate(int $id): Response
    {
        return $this->handleCardActionResult($this->workflowService->reactivateCard($id, $this->requireApiUserIdOrFail()));
    }

    public function archive(int $id): Response
    {
        return $this->handleCardActionResult($this->workflowService->archiveCard($id, $this->requireApiUserIdOrFail()));
    }

    public function restore(int $id): Response
    {
        return $this->handleCardActionResult($this->workflowService->restoreCard($id, $this->requireApiUserIdOrFail()));
    }

    public function delete(int $id): Response
    {
        $userId = $this->requireApiUserIdOrFail();
        $result = $this->workflowService->deleteCard($id, $userId, $this->getRequestPayload());

        $workflowResult = [
            'success' => (bool) ($result['success'] ?? false),
            'data' => $result,
        ];

        if (!$workflowResult['success']) {
            $requiresConfirmation = (bool) ($result['requires_confirmation'] ?? false);
            $workflowResult['status'] = $requiresConfirmation ? 422 : 404;
            $workflowResult['message'] = (string) ($result['message'] ?? 'Erro ao excluir cartao');
            $workflowResult['errors'] = [
                'status' => $requiresConfirmation ? 'confirm_delete' : 'error',
                'requires_confirmation' => $requiresConfirmation,
                'total_lancamentos' => (int) ($result['total_lancamentos'] ?? 0),
            ];
        }

        return $this->respondApiWorkflowResult(
            $workflowResult,
            useWorkflowFailureOnFailure: false
        );
    }

    public function destroy(int $id): Response
    {
        return $this->archive($id);
    }

    public function updateLimit(int $id): Response
    {
        $result = $this->workflowService->refreshLimit($id, $this->requireApiUserIdOrFail());

        $workflowResult = [
            'success' => (bool) ($result['success'] ?? false),
            'data' => [
                'limite_disponivel' => $result['limite_disponivel'] ?? null,
                'limite_utilizado' => $result['limite_utilizado'] ?? null,
                'percentual_uso' => $result['percentual_uso'] ?? null,
            ],
        ];

        if (!$workflowResult['success']) {
            $workflowResult['status'] = 404;
            $workflowResult['message'] = (string) ($result['message'] ?? 'Cartao nao encontrado');
        }

        return $this->respondApiWorkflowResult(
            $workflowResult,
            useWorkflowFailureOnFailure: false
        );
    }

    public function summary(): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        if ($this->demoPreviewService->shouldUsePreview($userId)) {
            return Response::successResponse($this->demoPreviewService->cartoesResumo());
        }

        return Response::successResponse($this->workflowService->getSummary($userId));
    }

    public function fatura(int $id): Response
    {
        $userId = $this->requireApiUserIdOrFail();
        $mes = $this->resolveQueryMonth();
        $ano = $this->resolveQueryYear();

        if ($mes < 1 || $mes > 12) {
            return Response::errorResponse('Mês inválido', 400);
        }

        try {
            return Response::successResponse($this->workflowService->getInvoice($id, $mes, $ano, $userId));
        } catch (\Exception $e) {
            return $this->notFoundFromThrowable($e, 'Fatura não encontrada.');
        }
    }

    public function pagarFatura(int $id): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        try {
            return Response::successResponse($this->workflowService->payInvoice($id, $userId, $this->getRequestPayload()));
        } catch (\Exception $e) {
            return $this->domainErrorResponse($e, 'Não foi possivel pagar a fatura.', 400);
        }
    }

    public function pagarParcelas(int $id): Response
    {
        $userId = $this->requireApiUserIdOrFail();
        $payload = $this->getRequestPayload();

        if (empty($payload['parcela_ids'] ?? [])) {
            return Response::errorResponse('Nenhuma parcela selecionada', 400);
        }

        try {
            return Response::successResponse($this->workflowService->payInstallments($id, $userId, $payload));
        } catch (\Exception $e) {
            return $this->domainErrorResponse($e, 'Não foi possivel pagar as parcelas.', 400);
        }
    }

    public function faturasPendentes(int $id): Response
    {
        try {
            $meses = $this->workflowService->getPendingInvoices($id, $this->requireApiUserIdOrFail());

            return Response::successResponse(['meses' => $meses]);
        } catch (\Exception $e) {
            return $this->notFoundFromThrowable($e, 'Cartao não encontrado.');
        }
    }

    public function faturasHistorico(int $id): Response
    {
        $userId = $this->requireApiUserIdOrFail();
        $limite = $this->getIntQuery('limite', 12);

        try {
            return Response::successResponse($this->workflowService->getInvoiceHistory($id, $userId, $limite));
        } catch (\Exception $e) {
            return $this->notFoundFromThrowable($e, 'Cartao não encontrado.');
        }
    }

    public function parcelamentosResumo(int $id): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        if (!$this->workflowService->showCard($id, $userId)) {
            return Response::errorResponse('Cartão não encontrado', 404);
        }

        $mes = $this->resolveQueryMonth();
        $ano = $this->resolveQueryYear();

        return Response::successResponse($this->workflowService->getInstallmentsSummary($id, $mes, $ano, $userId));
    }

    public function alertas(): Response
    {
        $userId = $this->requireApiUserIdOrFail();

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
        $userId = $this->requireApiUserIdOrFail();
        $corrigir = $this->getQuery('corrigir') === 'true';

        try {
            return Response::successResponse($this->workflowService->validateIntegrity($userId, $corrigir));
        } catch (\Exception $e) {
            return $this->internalErrorResponse($e, 'Erro ao validar integridade do cartao.');
        }
    }

    public function statusFatura(int $id): Response
    {
        $userId = $this->requireApiUserIdOrFail();
        $mes = $this->resolveRequiredIntQuery('mes');
        $ano = $this->resolveRequiredIntQuery('ano');

        if (!$mes || !$ano) {
            return Response::errorResponse('Mês e ano são obrigatórios', 400);
        }

        try {
            $status = $this->workflowService->getInvoiceStatus($id, $mes, $ano, $userId);

            return Response::successResponse($status ?? ['pago' => false]);
        } catch (\Exception $e) {
            return $this->internalErrorResponse($e, 'Erro ao carregar status da fatura.');
        }
    }

    public function desfazerPagamentoFatura(int $id): Response
    {
        $userId = $this->requireApiUserIdOrFail();
        $payload = $this->getRequestPayload();
        $mes = isset($payload['mes']) ? (int) $payload['mes'] : null;
        $ano = isset($payload['ano']) ? (int) $payload['ano'] : null;

        if (!$mes || !$ano) {
            return Response::errorResponse('Mês e ano são obrigatórios', 400);
        }

        try {
            return Response::successResponse($this->workflowService->undoInvoicePayment($id, $mes, $ano, $userId));
        } catch (\Exception $e) {
            LogService::captureException($e, LogCategory::FATURA, [
                'action' => 'desfazer_pagamento_fatura',
                'cartao_id' => $id,
                'mes' => $mes,
                'ano' => $ano,
                'user_id' => $userId,
            ]);

            return $this->domainErrorResponse($e, 'Não foi possivel desfazer o pagamento da fatura.', 400);
        }
    }

    public function desfazerPagamentoParcela(int $id): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        try {
            return Response::successResponse($this->workflowService->undoInstallmentPayment($id, $userId));
        } catch (\Exception $e) {
            LogService::captureException($e, LogCategory::FATURA, [
                'action' => 'desfazer_pagamento_parcela',
                'parcela_id' => $id,
                'user_id' => $userId,
            ]);

            return $this->domainErrorResponse($e, 'Não foi possivel desfazer o pagamento da parcela.', 400);
        }
    }

    public function recorrencias(): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        try {
            return Response::successResponse($this->workflowService->listRecurring($userId));
        } catch (\Exception $e) {
            LogService::captureException($e, LogCategory::CARTAO, [
                'action' => 'listar_recorrencias',
                'user_id' => $userId,
            ]);

            return $this->internalErrorResponse($e, 'Erro ao listar recorrencias do cartao.');
        }
    }

    public function recorrenciasCartao(int $id): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        try {
            return Response::successResponse($this->workflowService->listRecurring($userId, $id));
        } catch (\Exception $e) {
            LogService::captureException($e, LogCategory::CARTAO, [
                'action' => 'listar_recorrencias_cartao',
                'cartao_id' => $id,
                'user_id' => $userId,
            ]);

            return $this->internalErrorResponse($e, 'Erro ao listar recorrencias do cartao.', 500, [
                'cartao_id' => $id,
            ]);
        }
    }

    public function cancelarRecorrencia(int $id): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        try {
            $resultado = $this->workflowService->cancelRecurring($id, $userId);

            $workflowResult = [
                'success' => (bool) ($resultado['success'] ?? false),
                'data' => $resultado,
            ];

            if (!$workflowResult['success']) {
                $workflowResult['status'] = 400;
                $workflowResult['message'] = (string) ($resultado['message'] ?? 'Erro ao cancelar recorrencia');
            }

            return $this->respondApiWorkflowResult(
                $workflowResult,
                useWorkflowFailureOnFailure: false
            );
        } catch (\Exception $e) {
            LogService::captureException($e, LogCategory::CARTAO, [
                'action' => 'cancelar_recorrencia',
                'item_pai_id' => $id,
                'user_id' => $userId,
            ]);

            return $this->internalErrorResponse($e, 'Erro ao cancelar recorrencia do cartao.', 500, [
                'item_pai_id' => $id,
            ]);
        }
    }

    private function handleCardActionResult(array $resultado): Response
    {
        $workflowResult = [
            'success' => (bool) ($resultado['success'] ?? false),
            'data' => $resultado,
        ];

        if (!$workflowResult['success']) {
            $workflowResult['status'] = 404;
            $workflowResult['message'] = (string) ($resultado['message'] ?? 'Cartao nao encontrado');
        }

        return $this->respondApiWorkflowResult(
            $workflowResult,
            useWorkflowFailureOnFailure: false
        );
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
}
