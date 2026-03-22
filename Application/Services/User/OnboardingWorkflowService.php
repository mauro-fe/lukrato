<?php

declare(strict_types=1);

namespace Application\Services\User;

use Application\DTO\CreateContaDTO;
use Application\DTO\ServiceResultDTO;
use Application\Models\Usuario;
use Application\Services\Conta\ContaService;
use Application\Services\Lancamento\LancamentoCreationService;

class OnboardingWorkflowService
{
    private ContaService $contaService;
    private LancamentoCreationService $lancamentoCreationService;
    private OnboardingProgressService $progressService;

    public function __construct(
        ?ContaService $contaService = null,
        ?LancamentoCreationService $lancamentoCreationService = null,
        ?OnboardingProgressService $progressService = null
    ) {
        $this->contaService = $contaService ?? new ContaService();
        $this->lancamentoCreationService = $lancamentoCreationService ?? new LancamentoCreationService();
        $this->progressService = $progressService ?? new OnboardingProgressService();
    }

    /**
     * @return array<string, mixed>
     */
    public function getStatus(int $userId): array
    {
        $user = $this->findUser($userId);
        if (!$user) {
            return $this->failure('Usuário não encontrado', 404);
        }

        $progress = $this->progressService->getProgress($userId);

        return [
            'success' => true,
            'data' => [
                'tem_conta' => (bool) $progress->has_conta,
                'tem_lancamento' => (bool) $progress->has_lancamento,
                'onboarding_completo' => $user->onboarding_completed_at !== null
                    || $progress->onboarding_completed_at !== null,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function storeConta(int $userId, array $payload): array
    {
        $name = trim((string) ($payload['nome'] ?? ''));
        $institutionId = isset($payload['instituicao_financeira_id'])
            ? (int) $payload['instituicao_financeira_id']
            : 0;
        $initialBalance = $this->normalizeCurrencyToFloat($payload['saldo_inicial'] ?? '0,00');

        if ($name === '') {
            return $this->webFailure('O nome da conta é obrigatório.');
        }

        if ($institutionId <= 0) {
            return $this->webFailure('A instituição financeira é obrigatória.');
        }

        $dto = new CreateContaDTO(
            userId: $userId,
            nome: $name,
            instituicaoFinanceiraId: $institutionId,
            saldoInicial: $initialBalance,
        );

        $result = $this->contaService->criarConta($dto);
        if (!$result['success']) {
            return $this->webFailure((string) ($result['message'] ?? 'Erro ao criar conta.'));
        }

        return [
            'success' => true,
            'redirect' => 'onboarding',
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function storeLancamento(int $userId, array $payload): array
    {
        $result = $this->lancamentoCreationService->createFromPayload(
            $userId,
            $this->buildOnboardingLancamentoPayload($payload)
        );

        if ($result->isError()) {
            return $this->webFailure($this->resolveLancamentoFailureMessage($result));
        }

        $user = $this->findUser($userId);
        if ($user) {
            $this->markUserOnboardingComplete($user, 'complete');
        }

        $this->progressService->markCompleted($userId);

        return [
            'success' => true,
            'redirect' => 'dashboard',
            'just_completed' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function complete(int $userId): array
    {
        $user = $this->findUser($userId);
        if (!$user) {
            return $this->failure('Usuário não encontrado', 404);
        }

        $progress = $this->progressService->getProgress($userId);
        if (!$progress->has_conta) {
            $progress = $this->progressService->syncFromDatabase($userId);
        }

        if (!$progress->has_conta) {
            return $this->failure('Você precisa criar pelo menos uma conta antes de continuar.', 422);
        }

        $this->markUserOnboardingComplete($user, 'skipped');
        $this->progressService->markCompleted($userId);

        return [
            'success' => true,
            'data' => [
                'message' => 'Onboarding concluído!',
                'redirect' => BASE_URL . 'dashboard',
            ],
            'just_completed' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function skipTour(int $userId): array
    {
        $user = $this->findUser($userId);
        if (!$user) {
            return $this->failure('Usuário não encontrado', 404);
        }

        $user->skipOnboardingTour();

        return [
            'success' => true,
            'data' => ['message' => 'Tour pulado com sucesso.'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function reset(int $userId): array
    {
        $user = $this->findUser($userId);
        if (!$user) {
            return $this->failure('Usuário não encontrado', 404);
        }

        $user->onboarding_completed_at = null;
        $user->onboarding_mode = null;
        $user->onboarding_tour_skipped_at = null;
        $user->save();

        $this->progressService->reset($userId);

        return [
            'success' => true,
            'data' => ['message' => 'Onboarding resetado com sucesso.'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getChecklist(int $userId): array
    {
        $progress = $this->progressService->getProgress($userId);
        $metrics = $this->progressService->getChecklistMetrics($userId);

        $items = [
            [
                'key' => 'primeira_transacao',
                'label' => 'Adicionar primeira transação',
                'description' => 'Registre seu primeiro lançamento',
                'icon' => 'plus-circle',
                'color' => '#10b981',
                'href' => 'lancamentos?tipo=receita',
                'done' => (bool) $progress->has_lancamento,
                'points' => 25,
                'priority' => 1,
            ],
            [
                'key' => 'segunda_transacao',
                'label' => 'Adicionar segunda transação',
                'description' => 'Construa o hábito',
                'icon' => 'plus-circle',
                'color' => '#3b82f6',
                'href' => 'lancamentos',
                'done' => $metrics['entries_count'] >= 2,
                'points' => 25,
                'priority' => 2,
            ],
            [
                'key' => 'explorar_health_score',
                'label' => 'Explorar Health Score',
                'description' => 'Entenda sua saúde financeira',
                'icon' => 'heart-handshake',
                'color' => '#ec4899',
                'href' => 'dashboard#health-score',
                'done' => false,
                'points' => 10,
                'priority' => 3,
            ],
            [
                'key' => 'categoria_customizada',
                'label' => 'Criar categoria customizada',
                'description' => 'Personalize suas categorias',
                'icon' => 'tags',
                'color' => '#8b5cf6',
                'href' => 'categorias',
                'done' => $metrics['categories_count'] > 0,
                'points' => 20,
                'priority' => 4,
            ],
            [
                'key' => 'meta',
                'label' => 'Definir meta mensal',
                'description' => 'Estabeleça um objetivo de poupança',
                'icon' => 'target',
                'color' => '#06b6d4',
                'href' => 'financas',
                'done' => $metrics['has_meta'],
                'points' => 15,
                'priority' => 5,
            ],
            [
                'key' => 'conta_conectada',
                'label' => 'Adicionar segunda conta/cartão',
                'description' => 'Tenha visão completa do seu dinheiro',
                'icon' => 'wallet',
                'color' => '#f59e0b',
                'href' => 'contas',
                'done' => $metrics['accounts_count'] >= 2,
                'points' => 50,
                'priority' => 6,
            ],
            [
                'key' => 'orcamento',
                'label' => 'Configurar orçamentos',
                'description' => 'Controle seus gastos por categoria',
                'icon' => 'pie-chart',
                'color' => '#f97316',
                'href' => 'financas',
                'done' => $metrics['has_budget'],
                'points' => 20,
                'priority' => 7,
            ],
            [
                'key' => 'convidar_amigo',
                'label' => 'Convidar amigo (Referral)',
                'description' => 'Compartilhe o Lukrato e ganhe bônus',
                'icon' => 'share-2',
                'color' => '#f43f5e',
                'href' => 'perfil?tab=referral',
                'done' => false,
                'points' => 100,
                'priority' => 8,
            ],
        ];

        usort($items, static fn(array $a, array $b): int => $a['priority'] <=> $b['priority']);

        $doneCount = count(array_filter($items, static fn(array $item): bool => (bool) $item['done']));

        return [
            'success' => true,
            'data' => [
                'items' => $items,
                'done_count' => $doneCount,
                'total' => count($items),
                'all_complete' => $doneCount === count($items),
                'total_points' => array_sum(array_map(static fn(array $item): int => $item['done'] ? $item['points'] : 0, $items)),
                'potential_points' => array_sum(array_column($items, 'points')),
            ],
        ];
    }

    protected function findUser(int $userId): ?Usuario
    {
        return Usuario::find($userId);
    }

    /**
     * @return array<string, mixed>
     */
    private function failure(string $message, int $status = 400): array
    {
        return [
            'success' => false,
            'status' => $status,
            'message' => $message,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function webFailure(string $message): array
    {
        return [
            'success' => false,
            'redirect' => 'onboarding',
            'error_message' => $message,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function buildOnboardingLancamentoPayload(array $payload): array
    {
        return [
            'tipo' => trim((string) ($payload['tipo'] ?? 'despesa')),
            'data' => date('Y-m-d'),
            'valor' => $payload['valor'] ?? '',
            'descricao' => trim((string) ($payload['descricao'] ?? '')),
            'categoria_id' => $payload['categoria_id'] ?? null,
            'conta_id' => $payload['conta_id'] ?? null,
            'pago' => true,
        ];
    }

    private function resolveLancamentoFailureMessage(ServiceResultDTO $result): string
    {
        $errors = $result->data['errors'] ?? null;
        if (is_array($errors) && $errors !== []) {
            $firstError = reset($errors);
            if (is_string($firstError) && trim($firstError) !== '') {
                return $firstError;
            }
        }

        return $result->message !== '' ? $result->message : 'Erro ao salvar lançamento.';
    }

    private function markUserOnboardingComplete(Usuario $user, string $mode): void
    {
        $user->onboarding_completed_at = now();
        $user->onboarding_mode = $mode;
        $user->save();
    }

    private function normalizeCurrencyToFloat(mixed $value): float
    {
        $normalized = str_replace(['R$', ' ', '.'], '', (string) $value);
        $normalized = str_replace(',', '.', $normalized);

        return round(abs((float) $normalized), 2);
    }
}
