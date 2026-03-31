<?php

declare(strict_types=1);

namespace Application\UseCases\Perfil;

use Application\Lib\Auth;
use Application\Services\User\PerfilApiWorkflowService;

class DeleteAccountUseCase
{
    public function __construct(
        private readonly PerfilApiWorkflowService $workflowService
    ) {
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

