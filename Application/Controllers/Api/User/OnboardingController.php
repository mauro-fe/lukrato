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

    public function storeConta(): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        try {
            $result = $this->workflow()->storeConta($userId, $this->request->only([
                'nome',
                'instituicao_financeira_id',
                'saldo_inicial',
            ]));

            if (!$result['success'] && isset($result['error_message'])) {
                $this->setError($result['error_message']);
            }

            return $this->buildRedirectResponse($result['redirect'] ?? 'onboarding');
        } catch (Throwable $e) {
            $this->setError($this->internalErrorMessage(
                $e,
                'Erro inesperado ao criar conta. Tente novamente.',
                ['action' => 'onboarding_store_conta', 'user_id' => $userId]
            ));

            return $this->buildRedirectResponse('onboarding');
        }
    }

    public function storeLancamento(): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        try {
            $result = $this->workflow()->storeLancamento($userId, $this->request->only([
                'tipo',
                'valor',
                'categoria_id',
                'descricao',
                'conta_id',
            ]));

            if (!$result['success'] && isset($result['error_message'])) {
                $this->setError($result['error_message']);
            }

            if (($result['just_completed'] ?? false) === true) {
                $_SESSION['onboarding_just_completed'] = true;
            }

            return $this->buildRedirectResponse($result['redirect'] ?? 'onboarding');
        } catch (Throwable $e) {
            $this->setError($this->internalErrorMessage(
                $e,
                'Erro inesperado ao salvar lancamento. Tente novamente.',
                ['action' => 'onboarding_store_lancamento', 'user_id' => $userId]
            ));

            return $this->buildRedirectResponse('onboarding');
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
