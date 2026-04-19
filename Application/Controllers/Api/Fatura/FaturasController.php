<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Fatura;

use Application\Controllers\ApiController;
use Application\Core\Response;
use Application\Enums\LogCategory;
use Application\Services\Fatura\FaturaApiWorkflowService;
use Application\Services\Infrastructure\LogService;
use InvalidArgumentException;
use Throwable;

class FaturasController extends ApiController
{
    private FaturaApiWorkflowService $workflowService;

    public function __construct(?FaturaApiWorkflowService $workflowService = null)
    {
        parent::__construct();

        $this->workflowService = $this->resolveOrCreate($workflowService, FaturaApiWorkflowService::class);
    }

    public function index(): Response
    {
        $userId = $this->requireApiUserIdAndReleaseSessionOrFail();

        try {
            return $this->respondApiWorkflowResult(
                $this->workflowService->listInvoices($userId, [
                    'cartao_id' => $this->getQuery('cartao_id'),
                    'status' => $this->getQuery('status'),
                    'mes' => $this->getQuery('mes'),
                    'ano' => $this->getQuery('ano'),
                ]),
                'Erro ao processar fatura.',
                LogCategory::GENERAL,
                ['controller' => 'faturas'],
                preserveSuccessMeta: true
            );
        } catch (InvalidArgumentException $e) {
            return $this->domainErrorResponse($e, 'Dados inválidos para listar faturas.', 400);
        } catch (Throwable $e) {
            $this->logError('Erro ao listar faturas', $e);
            return Response::errorResponse('Erro ao listar faturas', 500);
        }
    }

    public function show(int $id): Response
    {
        $userId = $this->requireApiUserIdAndReleaseSessionOrFail();

        try {
            return $this->respondApiWorkflowResult(
                $this->workflowService->showInvoice($id, $userId),
                'Erro ao processar fatura.',
                LogCategory::GENERAL,
                ['controller' => 'faturas'],
                preserveSuccessMeta: true
            );
        } catch (InvalidArgumentException $e) {
            return $this->domainErrorResponse($e, 'Não foi possível buscar a fatura.', 400);
        } catch (Throwable $e) {
            $this->logError("Erro ao buscar fatura {$id}", $e);
            return Response::errorResponse('Erro ao buscar fatura', 500);
        }
    }

    public function store(): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        try {
            return $this->respondApiWorkflowResult(
                $this->workflowService->createInvoice(
                    $userId,
                    $this->getJsonPayloadOrNull()
                ),
                'Erro ao processar fatura.',
                LogCategory::GENERAL,
                ['controller' => 'faturas'],
                preserveSuccessMeta: true
            );
        } catch (InvalidArgumentException $e) {
            return $this->domainErrorResponse($e, 'Dados inválidos para criar fatura.', 400);
        } catch (Throwable $e) {
            $this->logError('Erro ao criar fatura', $e);
            return Response::errorResponse('Erro ao criar fatura. Tente novamente.', 500);
        }
    }

    public function destroy(int $id): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        try {
            return $this->respondApiWorkflowResult(
                $this->workflowService->deleteInvoice($id, $userId),
                'Erro ao processar fatura.',
                LogCategory::GENERAL,
                ['controller' => 'faturas'],
                preserveSuccessMeta: true
            );
        } catch (InvalidArgumentException $e) {
            return $this->domainErrorResponse($e, 'Não foi possível cancelar a fatura.', 400);
        } catch (Throwable $e) {
            $this->logError("Erro ao cancelar fatura {$id}", $e);
            return Response::errorResponse('Erro ao cancelar fatura', 500);
        }
    }

    public function updateItem(int $faturaId, int $itemId): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        try {
            return $this->respondApiWorkflowResult(
                $this->workflowService->updateInvoiceItem(
                    $faturaId,
                    $itemId,
                    $userId,
                    $this->getJsonPayloadOrNull()
                ),
                'Erro ao processar fatura.',
                LogCategory::GENERAL,
                ['controller' => 'faturas'],
                preserveSuccessMeta: true
            );
        } catch (InvalidArgumentException $e) {
            LogService::error('Erro de validação ao atualizar item da fatura', [
                'item_id' => $itemId,
                'fatura_id' => $faturaId,
                'error' => $e->getMessage(),
            ]);

            return $this->domainErrorResponse($e, 'Não foi possível atualizar o item da fatura.', 400);
        } catch (Throwable $e) {
            $this->logError("Erro ao atualizar item {$itemId} da fatura {$faturaId}", $e);
            LogService::error('Erro geral ao atualizar item da fatura', [
                'item_id' => $itemId,
                'fatura_id' => $faturaId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->internalErrorResponse($e, 'Erro ao atualizar item da fatura.');
        }
    }

    public function toggleItemPago(int $faturaId, int $itemId): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        try {
            return $this->respondApiWorkflowResult(
                $this->workflowService->toggleInvoiceItemPaid(
                    $faturaId,
                    $itemId,
                    $userId,
                    $this->getJsonPayloadOrNull()
                ),
                'Erro ao processar fatura.',
                LogCategory::GENERAL,
                ['controller' => 'faturas'],
                preserveSuccessMeta: true
            );
        } catch (InvalidArgumentException $e) {
            LogService::error('Erro de validação ao atualizar item da fatura', [
                'item_id' => $itemId,
                'fatura_id' => $faturaId,
                'error' => $e->getMessage(),
            ]);

            return $this->domainErrorResponse($e, 'Não foi possível atualizar o item da fatura.', 400);
        } catch (Throwable $e) {
            $this->logError("Erro ao atualizar item {$itemId} da fatura {$faturaId}", $e);
            LogService::error('Erro geral ao atualizar item da fatura', [
                'item_id' => $itemId,
                'fatura_id' => $faturaId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->internalErrorResponse($e, 'Erro ao atualizar item da fatura.');
        }
    }

    public function destroyItem(int $faturaId, int $itemId): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        try {
            return $this->respondApiWorkflowResult(
                $this->workflowService->deleteInvoiceItem($faturaId, $itemId, $userId),
                'Erro ao processar fatura.',
                LogCategory::GENERAL,
                ['controller' => 'faturas'],
                preserveSuccessMeta: true
            );
        } catch (InvalidArgumentException $e) {
            LogService::error('Erro de validação ao excluir item da fatura', [
                'item_id' => $itemId,
                'fatura_id' => $faturaId,
                'error' => $e->getMessage(),
            ]);

            return $this->domainErrorResponse($e, 'Não foi possível excluir o item da fatura.', 400);
        } catch (Throwable $e) {
            $this->logError("Erro ao excluir item {$itemId} da fatura {$faturaId}", $e);
            return $this->internalErrorResponse($e, 'Erro ao excluir item da fatura.');
        }
    }

    public function deleteParcelamento(int $faturaId, int $itemId): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        try {
            LogService::info('deleteParcelamento chamado', [
                'fatura_id' => $faturaId,
                'item_id' => $itemId,
                'usuario_id' => $userId,
            ]);

            return $this->respondApiWorkflowResult(
                $this->workflowService->deleteInstallment($faturaId, $itemId, $userId),
                'Erro ao processar fatura.',
                LogCategory::GENERAL,
                ['controller' => 'faturas'],
                preserveSuccessMeta: true
            );
        } catch (InvalidArgumentException $e) {
            return $this->domainErrorResponse($e, 'Não foi possível excluir o parcelamento.', 400);
        } catch (Throwable $e) {
            $this->logError("Erro ao excluir parcelamento do item {$itemId}", $e);
            return Response::errorResponse('Erro ao excluir parcelamento', 500);
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getJsonPayloadOrNull(): ?array
    {
        $payload = $this->getJson();

        return is_array($payload) && $payload !== [] ? $payload : null;
    }

    private function logError(string $message, Throwable $e): void
    {
        LogService::error($message, [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);
    }
}
