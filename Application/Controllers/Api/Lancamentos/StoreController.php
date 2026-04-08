<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Lancamentos;

use Application\Controllers\ApiController;
use Application\Core\Response;
use Application\Services\Lancamento\LancamentoCreationService;

class StoreController extends ApiController
{
    private LancamentoCreationService $creationService;

    public function __construct(?LancamentoCreationService $creationService = null)
    {
        parent::__construct();
        $this->creationService = $this->resolveOrCreate($creationService, LancamentoCreationService::class);
    }

    public function __invoke(): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        $result = $this->creationService->createFromPayload($userId, $this->getRequestPayload());
        return $this->respondServiceResult(
            $result,
            successMessage: $result->message,
            successStatus: 201
        );
    }
}
