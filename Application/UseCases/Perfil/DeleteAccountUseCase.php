<?php

declare(strict_types=1);

namespace Application\UseCases\Perfil;

use Application\Container\ApplicationContainer;
use Application\Lib\Auth;
use Application\Services\User\PerfilApiWorkflowService;

class DeleteAccountUseCase
{
    private readonly PerfilApiWorkflowService $workflowService;

    public function __construct(
        ?PerfilApiWorkflowService $workflowService = null
    ) {
        $this->workflowService = ApplicationContainer::resolveOrNew($workflowService, PerfilApiWorkflowService::class);
    }

    /**
     * @return array{message:string}
     */
    public function execute(int $userId): array
    {
        $this->workflowService->deleteAccount($userId);
        Auth::logout();

        return [
            'message' => 'Conta excluída com sucesso',
        ];
    }
}
