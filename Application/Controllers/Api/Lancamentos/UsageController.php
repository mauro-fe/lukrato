<?php

namespace Application\Controllers\Api\Lancamentos;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Lib\Auth;
use Application\Services\Lancamento\LancamentoLimitService;

class UsageController extends BaseController
{
    private LancamentoLimitService $limitService;

    public function __construct()
    {
        parent::__construct();
        $this->limitService = new LancamentoLimitService();
    }

    public function __invoke(): void
    {
        $userId = Auth::id();
        if (!$userId) {
            Response::error('Nao autenticado', 401);
            return;
        }

        $month = $_GET['month'] ?? date('Y-m');
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            Response::validationError(['month' => 'Formato invalido (YYYY-MM)']);
            return;
        }

        $usage = $this->limitService->usage($userId, $month);

        Response::success([
            'usage'       => $usage,
            'ui_message'  => $this->limitService->getWarningMessage($usage),
            'upgrade_cta' => ($usage['should_warn'] ?? false) ? $this->limitService->getUpgradeCta() : null,
        ]);
    }
}
