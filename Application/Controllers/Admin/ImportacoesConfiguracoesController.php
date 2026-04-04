<?php

declare(strict_types=1);

namespace Application\Controllers\Admin;

use Application\Controllers\WebController;
use Application\Core\Response;
use Application\Repositories\ContaRepository;
use Application\Services\Importacao\ImportProfileConfigService;

class ImportacoesConfiguracoesController extends WebController
{
    public function __construct(
        private readonly ContaRepository $contaRepository = new ContaRepository(),
        private readonly ImportProfileConfigService $profileConfigService = new ImportProfileConfigService(),
    ) {
        parent::__construct();
    }

    public function index(): Response
    {
        $userId = $this->requireUserId();

        $accounts = $this->loadActiveAccounts($userId);
        $selectedAccountId = $this->resolveSelectedAccountId($accounts, $this->getIntQuery('conta_id', 0));

        $profileConfig = null;
        if ($selectedAccountId > 0) {
            $profileConfig = $this->profileConfigService
                ->getForUserAndConta($userId, $selectedAccountId, 'ofx')
                ->toArray();
        }

        return $this->renderAdminResponse(
            'admin/importacoes/configuracoes/index',
            [
                'pageTitle' => 'Configuracoes de Importacao',
                'subTitle' => 'Defina perfil por conta para OFX agora e CSV na mesma base',
                'accounts' => $accounts,
                'selectedAccountId' => $selectedAccountId,
                'profileConfig' => $profileConfig,
                'configLoadEndpoint' => BASE_URL . 'api/importacoes/configuracoes',
                'configSaveEndpoint' => BASE_URL . 'api/importacoes/configuracoes',
                'csvTemplateAutoEndpoint' => BASE_URL . 'api/importacoes/modelos/csv?mode=auto',
                'csvTemplateManualEndpoint' => BASE_URL . 'api/importacoes/modelos/csv?mode=manual',
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
