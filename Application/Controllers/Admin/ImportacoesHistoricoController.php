<?php

declare(strict_types=1);

namespace Application\Controllers\Admin;

use Application\Controllers\WebController;
use Application\Core\Response;
use Application\Repositories\ContaRepository;
use Application\Services\Importacao\ImportHistoryService;

class ImportacoesHistoricoController extends WebController
{
    private readonly ContaRepository $contaRepository;
    private readonly ImportHistoryService $historyService;

    public function __construct(
        ?ContaRepository $contaRepository = null,
        ?ImportHistoryService $historyService = null,
    ) {
        parent::__construct();
        $this->contaRepository = $this->resolveOrCreate($contaRepository, ContaRepository::class);
        $this->historyService = $this->resolveOrCreate($historyService, ImportHistoryService::class);
    }

    public function index(): Response
    {
        $userId = $this->requireUserId();

        $accounts = $this->loadActiveAccounts($userId);
        $accountIds = array_column($accounts, 'id');

        $selectedAccountId = $this->getIntQuery('conta_id', 0);
        if ($selectedAccountId > 0 && !in_array($selectedAccountId, $accountIds, true)) {
            $selectedAccountId = 0;
        }

        $selectedSourceType = strtolower(trim($this->getStringQuery('source_type', '')));
        if (!in_array($selectedSourceType, ['ofx', 'csv'], true)) {
            $selectedSourceType = '';
        }

        $selectedImportTarget = strtolower(trim($this->getStringQuery('import_target', '')));
        if (!in_array($selectedImportTarget, ['conta', 'cartao'], true)) {
            $selectedImportTarget = '';
        }

        $selectedStatus = strtolower(trim($this->getStringQuery('status', '')));
        $allowedStatuses = [
            'processing',
            'processed',
            'processed_with_duplicates',
            'processed_duplicates_only',
            'processed_with_errors',
            'failed',
        ];
        if ($selectedStatus !== '' && !in_array($selectedStatus, $allowedStatuses, true)) {
            $selectedStatus = '';
        }

        $historyItems = $this->historyService->listForUser($userId, [
            'conta_id' => $selectedAccountId,
            'source_type' => $selectedSourceType,
            'status' => $selectedStatus,
            'import_target' => $selectedImportTarget,
        ], 120);

        return $this->renderAdminResponse(
            'admin/importacoes/historico/index',
            [
                'pageTitle' => 'Historico de Importacoes',
                'subTitle' => 'Acompanhe lotes importados, status e resultado por conta',
                'accounts' => $accounts,
                'selectedAccountId' => $selectedAccountId,
                'selectedSourceType' => $selectedSourceType,
                'selectedImportTarget' => $selectedImportTarget,
                'selectedStatus' => $selectedStatus,
                'statusOptions' => $allowedStatuses,
                'historyItems' => $historyItems,
                'historyEndpoint' => BASE_URL . 'api/importacoes/historico',
            ]
        );
    }

    /**
     * @return array<int, array{id:int, nome:string, instituicao:string}>
     */
    private function loadActiveAccounts(int $userId): array
    {
        $accounts = [];
        foreach ($this->contaRepository->findActive($userId) as $account) {
            $accounts[] = [
                'id' => (int) ($account->id ?? 0),
                'nome' => (string) ($account->nome ?? ''),
                'instituicao' => (string) ($account->instituicao ?? ''),
            ];
        }

        return $accounts;
    }
}
