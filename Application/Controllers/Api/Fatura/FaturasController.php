<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Fatura;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Enums\LogCategory;
use Application\Services\Fatura\FaturaApiWorkflowService;
use Application\Services\Fatura\FaturaService;
use Application\Services\Infrastructure\LogService;
use InvalidArgumentException;
use Throwable;

class FaturasController extends BaseController
{
    private FaturaApiWorkflowService $workflowService;

    public function __construct(
        ?FaturaService $faturaService = null,
        ?FaturaApiWorkflowService $workflowService = null
    ) {
        parent::__construct();

        $faturaService ??= new FaturaService();
        $this->workflowService = $workflowService ?? new FaturaApiWorkflowService($faturaService);
    }

    public function index(): Response
    {
        $userId = $this->requireApiUserIdAndReleaseSessionOrFail();

        try {
            return $this->respondWorkflowResult($this->workflowService->listInvoices($userId, [
                'cartao_id' => $this->getQuery('cartao_id'),
                'status' => $this->getQuery('status'),
                'mes' => $this->getQuery('mes'),
                'ano' => $this->getQuery('ano'),
            ]));
        } catch (InvalidArgumentException $e) {
            return $this->domainErrorResponse($e, 'Dados invalidos para listar faturas.', 400);
        } catch (Throwable $e) {
            $this->logError('Erro ao listar faturas', $e);
            return Response::errorResponse('Erro ao listar faturas', 500);
        }
    }

    public function show(int $id): Response
    {
        $userId = $this->requireApiUserIdAndReleaseSessionOrFail();

        try {
            return $this->respondWorkflowResult($this->workflowService->showInvoice($id, $userId));
        } catch (InvalidArgumentException $e) {
            return $this->domainErrorResponse($e, 'Não foi possivel buscar a fatura.', 400);
        } catch (Throwable $e) {
            $this->logError("Erro ao buscar fatura {$id}", $e);
            return Response::errorResponse('Erro ao buscar fatura', 500);
        }
    }

    public function store(): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        try {
            return $this->respondWorkflowResult($this->workflowService->createInvoice(
                $userId,
                $this->getJsonPayloadOrNull()
            ));
        } catch (InvalidArgumentException $e) {
            return $this->domainErrorResponse($e, 'Dados invalidos para criar fatura.', 400);
        } catch (Throwable $e) {
            $this->logError('Erro ao criar fatura', $e);
            return Response::errorResponse('Erro ao criar fatura. Tente novamente.', 500);
        }
    }

    public function destroy(int $id): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        try {
            return $this->respondWorkflowResult($this->workflowService->deleteInvoice($id, $userId));
        } catch (InvalidArgumentException $e) {
            return $this->domainErrorResponse($e, 'Não foi possivel cancelar a fatura.', 400);
        } catch (Throwable $e) {
            $this->logError("Erro ao cancelar fatura {$id}", $e);
            return Response::errorResponse('Erro ao cancelar fatura', 500);
        }
    }

    public function updateItem(int $faturaId, int $itemId): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        try {
            return $this->respondWorkflowResult($this->workflowService->updateInvoiceItem(
                $faturaId,
                $itemId,
                $userId,
                $this->getJsonPayloadOrNull()
            ));
        } catch (InvalidArgumentException $e) {
            LogService::error('Erro de validaÃ§Ã£o ao atualizar item da fatura', [
                'item_id' => $itemId,
                'fatura_id' => $faturaId,
                'error' => $e->getMessage(),
            ]);

            return $this->domainErrorResponse($e, 'Não foi possivel atualizar o item da fatura.', 400);
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
            return $this->respondWorkflowResult($this->workflowService->toggleInvoiceItemPaid(
                $faturaId,
                $itemId,
                $userId,
                $this->getJsonPayloadOrNull()
            ));
        } catch (InvalidArgumentException $e) {
            LogService::error('Erro de validaÃ§Ã£o ao atualizar item da fatura', [
                'item_id' => $itemId,
                'fatura_id' => $faturaId,
                'error' => $e->getMessage(),
            ]);

            return $this->domainErrorResponse($e, 'Não foi possivel atualizar o item da fatura.', 400);
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
            return $this->respondWorkflowResult($this->workflowService->deleteInvoiceItem($faturaId, $itemId, $userId));
        } catch (InvalidArgumentException $e) {
            LogService::error('Erro de validaÃ§Ã£o ao excluir item da fatura', [
                'item_id' => $itemId,
                'fatura_id' => $faturaId,
                'error' => $e->getMessage(),
            ]);

            return $this->domainErrorResponse($e, 'Não foi possivel excluir o item da fatura.', 400);
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

            return $this->respondWorkflowResult($this->workflowService->deleteInstallment($faturaId, $itemId, $userId));
        } catch (InvalidArgumentException $e) {
            return $this->domainErrorResponse($e, 'Não foi possivel excluir o parcelamento.', 400);
        } catch (Throwable $e) {
            $this->logError("Erro ao excluir parcelamento do item {$itemId}", $e);
            return Response::errorResponse('Erro ao excluir parcelamento', 500);
        }
    }

    /**
     * @param array<string, mixed> $result
     */
    private function respondWorkflowResult(array $result): Response
    {
        if (!$result['success']) {
            $errors = $result['errors'] ?? null;
            if ($errors === []) {
                $errors = null;
            }

            return $this->workflowFailureResponse(
                [
                    'success' => false,
                    'status' => $result['status'] ?? 400,
                    'message' => $result['message'] ?? 'Erro ao processar fatura.',
                    'errors' => $errors,
                    'error_id' => $result['error_id'] ?? null,
                    'request_id' => $result['request_id'] ?? null,
                ],
                'Erro ao processar fatura.',
                LogCategory::GENERAL,
                ['controller' => 'faturas']
            );
        }

        return Response::successResponse(
            $result['data'] ?? null,
            $result['message'] ?? 'Success',
            $result['status'] ?? 200
        );
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
