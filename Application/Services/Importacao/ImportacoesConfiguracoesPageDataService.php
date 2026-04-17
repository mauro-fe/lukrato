<?php

declare(strict_types=1);

namespace Application\Services\Importacao;

use Application\Repositories\ContaRepository;

class ImportacoesConfiguracoesPageDataService
{
    private readonly ContaRepository $contaRepository;
    private readonly ImportProfileConfigService $profileConfigService;

    public function __construct(
        ?ContaRepository $contaRepository = null,
        ?ImportProfileConfigService $profileConfigService = null,
    ) {
        $this->contaRepository = \Application\Container\ApplicationContainer::resolveOrNew($contaRepository, ContaRepository::class);
        $this->profileConfigService = \Application\Container\ApplicationContainer::resolveOrNew($profileConfigService, ImportProfileConfigService::class);
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public function buildForUser(int $userId, array $query = []): array
    {
        $accounts = $this->loadActiveAccounts($userId);
        $selectedAccountId = $this->resolveSelectedAccountId($accounts, (int) ($query['conta_id'] ?? 0));

        $profileConfig = null;
        if ($selectedAccountId > 0) {
            $profileConfig = $this->profileConfigService
                ->getForUserAndConta($userId, $selectedAccountId, 'ofx')
                ->toArray();
        }

        return [
            'accounts' => $accounts,
            'selectedAccountId' => $selectedAccountId,
            'profileConfig' => $profileConfig,
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
}
