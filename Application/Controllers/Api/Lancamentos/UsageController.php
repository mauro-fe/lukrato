<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Lancamentos;

use Application\Controllers\ApiController;
use Application\Core\Response;
use Application\Services\Lancamento\LancamentoLimitService;

class UsageController extends ApiController
{
    private LancamentoLimitService $limitService;

    public function __construct(?LancamentoLimitService $limitService = null)
    {
        parent::__construct();
        $this->limitService = $this->resolveOrCreate($limitService, LancamentoLimitService::class);
    }

    public function __invoke(): Response
    {
        $userId = $this->requireApiUserIdAndReleaseSessionOrFail();

        $month = $this->getStringQuery('month', date('Y-m'));
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            return Response::validationErrorResponse(['month' => 'Formato inválido (YYYY-MM)']);
        }

        $usage = $this->limitService->usage($userId, $month);

        return Response::successResponse([
            'usage' => $usage,
            'ui_message' => $this->limitService->getWarningMessage($usage),
            'upgrade_cta' => ($usage['should_warn'] ?? false) ? $this->limitService->getUpgradeCta() : null,
        ]);
    }
}
