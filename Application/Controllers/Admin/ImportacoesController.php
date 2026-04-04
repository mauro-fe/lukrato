<?php

declare(strict_types=1);

namespace Application\Controllers\Admin;

use Application\Controllers\WebController;
use Application\Core\Response;
use Application\Models\CartaoCredito;
use Application\Repositories\ContaRepository;
use Application\Services\Importacao\ImportHistoryService;
use Application\Services\Importacao\ImportProfileConfigService;
use Application\Services\Importacao\ImportSecurityPolicy;
use Application\Services\Plan\PlanLimitService;

class ImportacoesController extends WebController
{
    public function __construct(
        private readonly ContaRepository $contaRepository = new ContaRepository(),
        private readonly ImportProfileConfigService $profileConfigService = new ImportProfileConfigService(),
        private readonly ImportHistoryService $historyService = new ImportHistoryService(),
        private readonly PlanLimitService $planLimitService = new PlanLimitService(),
    ) {
        parent::__construct();
    }

    public function index(): Response
    {
        $userId = $this->requireUserId();

        $accounts = $this->loadActiveAccounts($userId);
        $cards = $this->loadActiveCards($userId);
        $importTarget = $this->normalizeImportTarget($this->getStringQuery('import_target', 'conta'));

        $selectedCardId = 0;
        if ($importTarget === 'cartao') {
            $selectedCardId = $this->resolveSelectedCardId($cards, $this->getIntQuery('cartao_id', 0));
            if ($selectedCardId <= 0 && $cards === []) {
                $importTarget = 'conta';
            }
        }

        if ($importTarget === 'cartao') {
            $selectedAccountId = $this->resolveAccountIdFromCard($cards, $selectedCardId);
        } else {
            $selectedAccountId = $this->resolveSelectedAccountId($accounts, $this->getIntQuery('conta_id', 0));
        }

        $requestedSourceType = strtolower(trim($this->getStringQuery('source_type', 'ofx')));
        if (!in_array($requestedSourceType, ['ofx', 'csv'], true)) {
            $requestedSourceType = 'ofx';
        }
        if ($importTarget === 'cartao') {
            $requestedSourceType = 'ofx';
        }

        try {
            $planLimits = $this->planLimitService->getLimitsSummary($userId);
        } catch (\Throwable) {
            $freeConfig = $this->planLimitService->getConfig()['limits']['free'] ?? [];
            $planLimits = [
                'plan' => 'free',
                'is_pro' => false,
                'importacoes' => [
                    'import_conta_ofx' => ['allowed' => true, 'limit' => $freeConfig['import_conta_ofx'] ?? 1, 'used' => 0, 'remaining' => $freeConfig['import_conta_ofx'] ?? 1],
                    'import_conta_csv' => ['allowed' => true, 'limit' => $freeConfig['import_conta_csv'] ?? 1, 'used' => 0, 'remaining' => $freeConfig['import_conta_csv'] ?? 1],
                    'import_cartao_ofx' => ['allowed' => true, 'limit' => $freeConfig['import_cartao_ofx'] ?? 1, 'used' => 0, 'remaining' => $freeConfig['import_cartao_ofx'] ?? 1],
                ],
                'upgrade_url' => '/assinatura',
            ];
        }

        try {
            $importQuota = $this->planLimitService->canUseImportacao($userId, $requestedSourceType, $importTarget);
        } catch (\Throwable) {
            $fallbackBucket = $importTarget === 'cartao'
                ? 'import_cartao_ofx'
                : ($requestedSourceType === 'csv' ? 'import_conta_csv' : 'import_conta_ofx');
            $importQuota = is_array($planLimits['importacoes'][$fallbackBucket] ?? null)
                ? $planLimits['importacoes'][$fallbackBucket]
                : ['allowed' => true, 'limit' => 1, 'used' => 0, 'remaining' => 1];
            $importQuota['bucket'] = $fallbackBucket;
            $importQuota['source_type'] = $requestedSourceType;
            $importQuota['import_target'] = $importTarget;
        }

        $profileConfig = null;
        if ($selectedAccountId > 0) {
            $profileConfig = $this->profileConfigService
                ->getForUserAndConta($userId, $selectedAccountId, $requestedSourceType)
                ->toArray();
            $profileConfig['source_type'] = $requestedSourceType;
        }

        $latestHistoryFilters = [
            'conta_id' => $selectedAccountId,
            'import_target' => $importTarget,
        ];
        $latestHistoryItems = $this->historyService->listForUser($userId, $latestHistoryFilters, 5);

        return $this->renderAdminResponse(
            'admin/importacoes/index',
            [
                'pageTitle' => 'Importações',
                'subTitle' => 'Importe arquivos financeiros com preview e confirmação real',
                'supportedFormats' => ['OFX', 'CSV'],
                'importTarget' => $importTarget,
                'accounts' => $accounts,
                'cards' => $cards,
                'selectedAccountId' => $selectedAccountId,
                'selectedCardId' => $selectedCardId,
                'profileConfig' => $profileConfig,
                'latestHistoryItems' => $latestHistoryItems,
                'planLimits' => $planLimits,
                'importQuota' => $importQuota,
                'previewEndpoint' => BASE_URL . 'api/importacoes/preview',
                'confirmEndpoint' => BASE_URL . 'api/importacoes/confirm',
                'configEndpoint' => BASE_URL . 'api/importacoes/configuracoes',
                'historyEndpoint' => BASE_URL . 'api/importacoes/historico',
                'jobStatusEndpointBase' => BASE_URL . 'api/importacoes/jobs',
                'confirmAsyncDefault' => ImportSecurityPolicy::shouldQueueConfirmByDefault(),
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

    /**
     * @return array<int, array{id:int,nome:string,bandeira:string,conta_id:int}>
     */
    private function loadActiveCards(int $userId): array
    {
        $cards = [];
        try {
            $rows = CartaoCredito::query()
                ->where('user_id', $userId)
                ->where('ativo', true)
                ->where('arquivado', false)
                ->orderBy('nome_cartao')
                ->get(['id', 'nome_cartao', 'bandeira', 'conta_id']);
        } catch (\Throwable) {
            return [];
        }

        foreach ($rows as $row) {
            $cards[] = [
                'id' => (int) ($row->id ?? 0),
                'nome' => (string) ($row->nome_cartao ?? 'Cartão'),
                'bandeira' => (string) ($row->bandeira ?? ''),
                'conta_id' => (int) ($row->conta_id ?? 0),
            ];
        }

        return $cards;
    }

    /**
     * @param array<int, array{id:int, nome:string, instituicao:string}> $accounts
     */
    private function resolveSelectedAccountId(array $accounts, int $requestedAccountId): int
    {
        $accountIds = array_column($accounts, 'id');
        if ($requestedAccountId > 0 && in_array($requestedAccountId, $accountIds, true)) {
            return $requestedAccountId;
        }

        return isset($accountIds[0]) ? (int) $accountIds[0] : 0;
    }

    /**
     * @param array<int, array{id:int,nome:string,bandeira:string,conta_id:int}> $cards
     */
    private function resolveSelectedCardId(array $cards, int $requestedCardId): int
    {
        $cardIds = array_column($cards, 'id');
        if ($requestedCardId > 0 && in_array($requestedCardId, $cardIds, true)) {
            return $requestedCardId;
        }

        return isset($cardIds[0]) ? (int) $cardIds[0] : 0;
    }

    /**
     * @param array<int, array{id:int,nome:string,bandeira:string,conta_id:int}> $cards
     */
    private function resolveAccountIdFromCard(array $cards, int $selectedCardId): int
    {
        foreach ($cards as $card) {
            if ((int) ($card['id'] ?? 0) !== $selectedCardId) {
                continue;
            }

            return (int) ($card['conta_id'] ?? 0);
        }

        return 0;
    }

    private function normalizeImportTarget(string $importTarget): string
    {
        $normalized = strtolower(trim($importTarget));

        return in_array($normalized, ['conta', 'cartao'], true) ? $normalized : 'conta';
    }
}
