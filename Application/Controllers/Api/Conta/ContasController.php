<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Conta;

use Application\Controllers\ApiController;
use Application\Core\Response;
use Application\Services\Conta\ContaApiWorkflowService;
use Application\Services\Demo\DemoPreviewService;
use Throwable;

class ContasController extends ApiController
{
    private ContaApiWorkflowService $workflowService;
    private DemoPreviewService $demoPreviewService;

    public function __construct(
        ?ContaApiWorkflowService $workflowService = null,
        ?DemoPreviewService $demoPreviewService = null
    ) {
        parent::__construct();

        $this->workflowService = $this->resolveOrCreate($workflowService, ContaApiWorkflowService::class);
        $this->demoPreviewService = $this->resolveOrCreate($demoPreviewService, DemoPreviewService::class);
    }

    /**
     * GET /api/contas
     * Listar contas do usuário.
     */
    public function index(): Response
    {
        $userId = $this->requireApiUserIdAndReleaseSessionOrFail();

        try {
            $previewRequested = $this->getIntQuery('preview', 0) === 1;

            if ($previewRequested && $this->demoPreviewService->shouldUsePreview($userId)) {
                $month = $this->getQuery('month');

                return Response::successResponse(
                    $this->demoPreviewService->contas(is_string($month) ? $month : null)
                );
            }

            return $this->respondApiWorkflowResult(
                $this->workflowService->listAccounts($userId, [
                    'archived' => $this->getIntQuery('archived', 0),
                    'only_active' => $this->getQuery('only_active'),
                    'with_balances' => $this->getIntQuery('with_balances', 0),
                    'month' => $this->getQuery('month'),
                ]),
                preserveSuccessMeta: true,
                useWorkflowFailureOnFailure: false
            );
        } catch (Throwable $e) {
            return $this->internalErrorResponse($e, 'Erro ao carregar contas.');
        }
    }

    /**
     * POST /api/v2/contas
     * Criar nova conta.
     */
    public function store(): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        return $this->respondApiWorkflowResult(
            $this->workflowService->createAccount($userId, $this->getRequestPayload()),
            preserveSuccessMeta: true,
            useWorkflowFailureOnFailure: false
        );
    }

    /**
     * PUT /api/v2/contas/{id}
     * Atualizar conta.
     */
    public function update(int $id): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        return $this->respondApiWorkflowResult(
            $this->workflowService->updateAccount($id, $userId, $this->getRequestPayload()),
            preserveSuccessMeta: true,
            useWorkflowFailureOnFailure: false
        );
    }

    /**
     * POST /api/v2/contas/{id}/archive
     * Arquivar conta.
     */
    public function archive(int $id): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        return $this->respondApiWorkflowResult(
            $this->workflowService->archiveAccount($id, $userId),
            preserveSuccessMeta: true,
            useWorkflowFailureOnFailure: false
        );
    }

    /**
     * POST /api/v2/contas/{id}/restore
     * Restaurar conta.
     */
    public function restore(int $id): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        return $this->respondApiWorkflowResult(
            $this->workflowService->restoreAccount($id, $userId),
            preserveSuccessMeta: true,
            useWorkflowFailureOnFailure: false
        );
    }

    /**
     * DELETE /api/v2/contas/{id}
     * Excluir conta.
     */
    public function destroy(int $id): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        return $this->respondApiWorkflowResult(
            $this->workflowService->deleteAccount(
                $id,
                $userId,
                $this->getRequestPayload(),
                ['force' => $this->getIntQuery('force', 0)]
            ),
            preserveSuccessMeta: true,
            useWorkflowFailureOnFailure: false
        );
    }

    /**
     * POST /api/accounts/{id}/delete
     * Exclusao permanente de conta (hard delete).
     */
    public function hardDelete(int $id): Response
    {
        return $this->destroy($id);
    }

    /**
     * GET /api/contas/instituicoes
     * Listar instituicoes financeiras disponiveis.
     */
    public function instituicoes(): Response
    {
        try {
            $tipo = $this->getQuery('tipo');
            $tipo = is_string($tipo) ? trim($tipo) : null;

            return $this->respondApiWorkflowResult(
                $this->workflowService->listInstituicoes($tipo),
                preserveSuccessMeta: true,
                useWorkflowFailureOnFailure: false
            );
        } catch (Throwable $e) {
            return $this->internalErrorResponse($e, 'Erro ao carregar instituicoes.');
        }
    }

    /**
     * POST /api/instituicoes
     * Criar nova instituicao financeira personalizada.
     */
    public function createInstituicao(): Response
    {
        try {
            return $this->respondApiWorkflowResult(
                $this->workflowService->createInstituicao($this->getJson()),
                preserveSuccessMeta: true,
                useWorkflowFailureOnFailure: false
            );
        } catch (Throwable $e) {
            return $this->internalErrorResponse($e, 'Erro ao criar instituição.');
        }
    }
}
