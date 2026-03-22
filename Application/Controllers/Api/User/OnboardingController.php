<?php

declare(strict_types=1);

namespace Application\Controllers\Api\User;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Services\User\OnboardingWorkflowService;
use Throwable;

class OnboardingController extends BaseController
{
    public function __construct(
        private readonly ?OnboardingWorkflowService $workflowService = null
    ) {
        parent::__construct();
    }

    public function status(): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        try {
            return $this->respondWorkflowResult($this->workflow()->getStatus($userId));
        } catch (Throwable $e) {
            return $this->failAndLogResponse($e, 'Erro ao verificar status do onboarding');
        }
    }

    public function complete(): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        try {
            $result = $this->workflow()->complete($userId);

            if (($result['just_completed'] ?? false) === true) {
                $_SESSION['onboarding_just_completed'] = true;
            }

            return $this->respondWorkflowResult($result);
        } catch (Throwable $e) {
            return $this->failAndLogResponse($e, 'Erro ao completar onboarding');
        }
    }

    public function skipTour(): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        try {
            return $this->respondWorkflowResult($this->workflow()->skipTour($userId));
        } catch (Throwable $e) {
            return $this->failAndLogResponse($e, 'Erro ao pular tour do onboarding');
        }
    }

    public function reset(): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        try {
            return $this->respondWorkflowResult($this->workflow()->reset($userId));
        } catch (Throwable $e) {
            return $this->failAndLogResponse($e, 'Erro ao resetar onboarding');
        }
    }

    public function checklist(): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        try {
            return $this->respondWorkflowResult($this->workflow()->getChecklist($userId));
        } catch (Throwable $e) {
            return $this->failAndLogResponse($e, 'Erro ao buscar checklist do onboarding');
        }
    }

    /**
     * POST /api/onboarding/goal
     */
    public function storeGoal(): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        try {
            $result = $this->workflow()->storeGoal(
                $userId,
                array_intersect_key($this->getRequestPayload(), array_flip(['goal']))
            );

            return $this->respondWorkflowResult($result);
        } catch (Throwable $e) {
            return $this->failAndLogResponse($e, 'Erro ao salvar objetivo');
        }
    }

    /**
     * POST /api/onboarding/conta/json
     */
    public function storeContaJson(): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        try {
            $result = $this->workflow()->storeContaJson(
                $userId,
                array_intersect_key($this->getRequestPayload(), array_flip([
                    'nome',
                    'instituicao_financeira_id',
                    'instituicao',
                    'saldo_inicial',
                ]))
            );

            return $this->respondWorkflowResult($result);
        } catch (Throwable $e) {
            return $this->failAndLogResponse($e, 'Erro ao criar conta');
        }
    }

    /**
     * POST /api/onboarding/lancamento/json
     */
    public function storeLancamentoJson(): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        try {
            $result = $this->workflow()->storeLancamentoJson(
                $userId,
                array_intersect_key($this->getRequestPayload(), array_flip([
                    'tipo',
                    'valor',
                    'categoria_id',
                    'descricao',
                    'conta_id',
                ]))
            );

            if ($result['success'] && ($result['just_completed'] ?? false)) {
                $_SESSION['onboarding_just_completed'] = true;
            }

            return $this->respondWorkflowResult($result);
        } catch (Throwable $e) {
            return $this->failAndLogResponse($e, 'Erro ao registrar lancamento');
        }
    }

    /**
     * @param array<string, mixed> $result
     */
    private function respondWorkflowResult(array $result): Response
    {
        if (!$result['success']) {
            return Response::errorResponse($result['message'], $result['status'] ?? 400);
        }

        return Response::successResponse($result['data'] ?? null, $result['message'] ?? 'Success');
    }

    private function workflow(): OnboardingWorkflowService
    {
        return $this->workflowService ?? new OnboardingWorkflowService();
    }
}
