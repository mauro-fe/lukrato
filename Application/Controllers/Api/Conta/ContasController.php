<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Conta;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Services\Conta\ContaApiWorkflowService;
use Application\Services\Conta\ContaService;
use Application\Services\Demo\DemoPreviewService;
use Application\Services\Plan\PlanLimitService;
use Throwable;

class ContasController extends BaseController
{
    private ContaApiWorkflowService $workflowService;
    private DemoPreviewService $demoPreviewService;

    public function __construct(
        ?ContaService $service = null,
        ?PlanLimitService $planLimitService = null,
        ?ContaApiWorkflowService $workflowService = null,
        ?DemoPreviewService $demoPreviewService = null
    ) {
        parent::__construct();

        $service ??= new ContaService();
        $planLimitService ??= new PlanLimitService();
        $this->workflowService = $workflowService ?? new ContaApiWorkflowService($service, $planLimitService);
        $this->demoPreviewService = $demoPreviewService ?? new DemoPreviewService();
    }

    /**
     * GET /api/contas
     * Listar contas do usuario.
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

            return $this->respondWorkflowResult($this->workflowService->listAccounts($userId, [
                'archived' => $this->getIntQuery('archived', 0),
                'only_active' => $this->getQuery('only_active'),
                'with_balances' => $this->getIntQuery('with_balances', 0),
                'month' => $this->getQuery('month'),
            ]));
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

        return $this->respondWorkflowResult($this->workflowService->createAccount($userId, $this->getRequestPayload()));
    }

    /**
     * PUT /api/v2/contas/{id}
     * Atualizar conta.
     */
    public function update(int $id): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        return $this->respondWorkflowResult($this->workflowService->updateAccount($id, $userId, $this->getRequestPayload()));
    }

    /**
     * POST /api/v2/contas/{id}/archive
     * Arquivar conta.
     */
    public function archive(int $id): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        return $this->respondWorkflowResult($this->workflowService->archiveAccount($id, $userId));
    }

    /**
     * POST /api/v2/contas/{id}/restore
     * Restaurar conta.
     */
    public function restore(int $id): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        return $this->respondWorkflowResult($this->workflowService->restoreAccount($id, $userId));
    }

    /**
     * DELETE /api/v2/contas/{id}
     * Excluir conta.
     */
    public function destroy(int $id): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        return $this->respondWorkflowResult($this->workflowService->deleteAccount(
            $id,
            $userId,
            $this->getRequestPayload(),
            ['force' => $this->getIntQuery('force', 0)]
        ));
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

            return $this->respondWorkflowResult($this->workflowService->listInstituicoes($tipo));
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
            return $this->respondWorkflowResult($this->workflowService->createInstituicao($this->getJson()));
        } catch (Throwable $e) {
            return $this->internalErrorResponse($e, 'Erro ao criar instituicao.');
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

            return Response::errorResponse(
                $result['message'],
                $result['status'] ?? 400,
                $errors
            );
        }

        return Response::successResponse(
            $result['data'] ?? null,
            $result['message'] ?? 'Success',
            $result['status'] ?? 200
        );
    }
}
