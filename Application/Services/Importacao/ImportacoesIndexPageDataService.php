<?php

declare(strict_types=1);

namespace Application\Services\Importacao;

use Application\Models\CartaoCredito;
use Application\Repositories\ContaRepository;
use Application\Services\Plan\PlanContext;
use Application\Services\Plan\PlanLimitService;

class ImportacoesIndexPageDataService
{
    private readonly ContaRepository $contaRepository;
    private readonly ImportProfileConfigService $profileConfigService;
    private readonly ImportHistoryService $historyService;
    private readonly PlanLimitService $planLimitService;

    public function __construct(
        ?ContaRepository $contaRepository = null,
        ?ImportProfileConfigService $profileConfigService = null,
        ?ImportHistoryService $historyService = null,
        ?PlanLimitService $planLimitService = null,
    ) {
        $this->contaRepository = \Application\Container\ApplicationContainer::resolveOrNew($contaRepository, ContaRepository::class);
        $this->profileConfigService = \Application\Container\ApplicationContainer::resolveOrNew($profileConfigService, ImportProfileConfigService::class);
        $this->historyService = \Application\Container\ApplicationContainer::resolveOrNew($historyService, ImportHistoryService::class);
        $this->planLimitService = \Application\Container\ApplicationContainer::resolveOrNew($planLimitService, PlanLimitService::class);
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public function buildForUser(int $userId, array $query = []): array
    {
        $accounts = $this->loadActiveAccounts($userId);
        $cards = $this->loadActiveCards($userId);
        $importTarget = $this->normalizeImportTarget((string) ($query['import_target'] ?? 'conta'));

        $selectedCardId = 0;
        if ($importTarget === 'cartao') {
            $selectedCardId = $this->resolveSelectedCardId($cards, (int) ($query['cartao_id'] ?? 0));
            if ($selectedCardId <= 0 && $cards === []) {
                $importTarget = 'conta';
            }
        }

        if ($importTarget === 'cartao') {
            $selectedAccountId = $this->resolveAccountIdFromCard($cards, $selectedCardId);
        } else {
            $selectedAccountId = $this->resolveSelectedAccountId($accounts, (int) ($query['conta_id'] ?? 0));
        }

        $sourceType = $this->normalizeSourceType((string) ($query['source_type'] ?? 'ofx'));

        try {
            $planLimits = $this->planLimitService->getLimitsSummary($userId);
        } catch (\Throwable) {
            $freeConfig = $this->planLimitService->getConfig()['limits']['free'] ?? [];
            $planLimits = [
                ...PlanContext::summaryForTier('free'),
                'importacoes' => [
                    'import_conta_ofx' => ['allowed' => true, 'limit' => $freeConfig['import_conta_ofx'] ?? 1, 'used' => 0, 'remaining' => $freeConfig['import_conta_ofx'] ?? 1],
                    'import_conta_csv' => ['allowed' => true, 'limit' => $freeConfig['import_conta_csv'] ?? 1, 'used' => 0, 'remaining' => $freeConfig['import_conta_csv'] ?? 1],
                    'import_cartao_ofx' => ['allowed' => true, 'limit' => $freeConfig['import_cartao_ofx'] ?? 1, 'used' => 0, 'remaining' => $freeConfig['import_cartao_ofx'] ?? 1],
                ],
                'upgrade_url' => '/assinatura',
            ];
        }

        try {
            $importQuota = $this->planLimitService->canUseImportacao($userId, $sourceType, $importTarget);
        } catch (\Throwable) {
            $fallbackBucket = $importTarget === 'cartao'
                ? 'import_cartao_ofx'
                : ($sourceType === 'csv' ? 'import_conta_csv' : 'import_conta_ofx');
            $importQuota = is_array($planLimits['importacoes'][$fallbackBucket] ?? null)
                ? $planLimits['importacoes'][$fallbackBucket]
                : ['allowed' => true, 'limit' => 1, 'used' => 0, 'remaining' => 1];
            $importQuota['bucket'] = $fallbackBucket;
            $importQuota['source_type'] = $sourceType;
            $importQuota['import_target'] = $importTarget;
        }

        $profileConfig = null;
        if ($selectedAccountId > 0) {
            $profileConfig = $this->profileConfigService
                ->getForUserAndConta($userId, $selectedAccountId, $sourceType)
                ->toArray();
            $profileConfig['source_type'] = $sourceType;
        }

        $latestHistoryItems = $this->historyService->listForUser($userId, [
            'conta_id' => $selectedAccountId,
            'import_target' => $importTarget,
        ], 5);

        return [
            'supportedFormats' => ['OFX', 'CSV'],
            'importTarget' => $importTarget,
            'sourceType' => $sourceType,
            'accounts' => $accounts,
            'cards' => $cards,
            'selectedAccountId' => $selectedAccountId,
            'selectedCardId' => $selectedCardId,
            'profileConfig' => $profileConfig,
            'latestHistoryItems' => $latestHistoryItems,
            'planLimits' => $planLimits,
            'importQuota' => $importQuota,
            'configPageBaseUrl' => rtrim(BASE_URL, '/') . '/importacoes/configuracoes',
            'confirmAsyncDefault' => ImportSecurityPolicy::shouldQueueConfirmByDefault(),
        ];
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
            if ((int) $card['id'] !== $selectedCardId) {
                continue;
            }

            return (int) $card['conta_id'];
        }

        return 0;
    }

    private function normalizeImportTarget(string $importTarget): string
    {
        $normalized = strtolower(trim($importTarget));

        return in_array($normalized, ['conta', 'cartao'], true) ? $normalized : 'conta';
    }

    private function normalizeSourceType(string $sourceType): string
    {
        $normalized = strtolower(trim($sourceType));

        return in_array($normalized, ['ofx', 'csv'], true) ? $normalized : 'ofx';
    }
}
