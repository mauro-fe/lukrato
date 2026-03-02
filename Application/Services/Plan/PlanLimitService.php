<?php

declare(strict_types=1);

namespace Application\Services\Plan;

use Application\Models\Usuario;
use Application\Models\Conta;
use Application\Models\CartaoCredito;
use Application\Models\Categoria;
use Application\Models\Meta;

/**
 * Serviço para gerenciar limites de recursos por plano.
 * Valida se usuário pode criar novos recursos baseado no plano.
 */
class PlanLimitService
{
    private array $config;
    private ?bool $isPro = null;
    private ?int $userId = null;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../../Config/Billing.php';
    }

    /**
     * Retorna a configuração de billing (para fallbacks)
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Verifica se o usuário possui plano Pro ativo.
     * Delega para Usuario::isPro() que trata corretamente:
     * - Período de carência (3 dias após vencimento)
     * - Assinaturas canceladas com período pago restante
     * - Códigos de plano 'free' e 'gratuito'
     */
    public function isPro(int $userId): bool
    {
        // Cache para evitar consultas repetidas
        if ($this->userId === $userId && $this->isPro !== null) {
            return $this->isPro;
        }

        $this->userId = $userId;

        try {
            /** @var Usuario|null $user */
            $user = Usuario::find($userId);
            if (!$user) {
                $this->isPro = false;
                return false;
            }

            $this->isPro = $user->isPro();
            return $this->isPro;
        } catch (\Throwable) {
            $this->isPro = false;
            return false;
        }
    }

    /**
     * Obtém um limite específico do plano
     */
    public function getLimit(int $userId, string $limitKey): ?int
    {
        $plan = $this->isPro($userId) ? 'pro' : 'free';
        return $this->config['limits'][$plan][$limitKey] ?? null;
    }

    /**
     * Obtém mensagem de erro para um limite específico
     */
    public function getLimitMessage(string $messageKey, array $params = []): string
    {
        $template = $this->config['messages'][$messageKey] ?? 'Limite atingido.';

        foreach ($params as $key => $value) {
            $template = str_replace('{' . $key . '}', (string) $value, $template);
        }

        return $template;
    }

    // ============================================================
    // VALIDAÇÃO DE CONTAS BANCÁRIAS
    // ============================================================

    /**
     * Conta quantas contas bancárias ativas o usuário possui
     */
    public function countContas(int $userId): int
    {
        return Conta::where('user_id', $userId)
            ->where('ativo', 1)
            ->count();
    }

    /**
     * Verifica se o usuário pode criar uma nova conta bancária
     */
    public function canCreateConta(int $userId): array
    {
        if ($this->isPro($userId)) {
            return ['allowed' => true, 'limit' => null, 'used' => null];
        }

        $limit = $this->getLimit($userId, 'max_contas');
        $used = $this->countContas($userId);

        if ($limit !== null && $used >= $limit) {
            return [
                'allowed' => false,
                'limit' => $limit,
                'used' => $used,
                'remaining' => 0,
                'message' => $this->getLimitMessage('contas_limit', ['limit' => $limit]),
                'upgrade_url' => '/assinatura',
            ];
        }

        return [
            'allowed' => true,
            'limit' => $limit,
            'used' => $used,
            'remaining' => $limit - $used,
        ];
    }

    /**
     * Valida e lança exceção se não puder criar conta
     * 
     * @throws \DomainException
     */
    public function assertCanCreateConta(int $userId): void
    {
        $result = $this->canCreateConta($userId);
        if (!$result['allowed']) {
            throw new \DomainException($result['message']);
        }
    }

    // ============================================================
    // VALIDAÇÃO DE CARTÕES DE CRÉDITO
    // ============================================================

    /**
     * Conta quantos cartões o usuário possui (não arquivados)
     */
    public function countCartoes(int $userId): int
    {
        return CartaoCredito::where('user_id', $userId)
            ->where('arquivado', 0)
            ->count();
    }

    /**
     * Verifica se o usuário pode criar um novo cartão
     */
    public function canCreateCartao(int $userId): array
    {
        if ($this->isPro($userId)) {
            return ['allowed' => true, 'limit' => null, 'used' => null];
        }

        $limit = $this->getLimit($userId, 'max_cartoes');
        $used = $this->countCartoes($userId);

        if ($limit !== null && $used >= $limit) {
            return [
                'allowed' => false,
                'limit' => $limit,
                'used' => $used,
                'remaining' => 0,
                'message' => $this->getLimitMessage('cartoes_limit', ['limit' => $limit]),
                'upgrade_url' => '/assinatura',
            ];
        }

        return [
            'allowed' => true,
            'limit' => $limit,
            'used' => $used,
            'remaining' => $limit - $used,
        ];
    }

    /**
     * Valida e lança exceção se não puder criar cartão
     * 
     * @throws \DomainException
     */
    public function assertCanCreateCartao(int $userId): void
    {
        $result = $this->canCreateCartao($userId);
        if (!$result['allowed']) {
            throw new \DomainException($result['message']);
        }
    }

    // ============================================================
    // VALIDAÇÃO DE CATEGORIAS PERSONALIZADAS
    // ============================================================

    /**
     * Conta quantas categorias personalizadas o usuário criou
     * (total de categorias raiz - 19 categorias padrão criadas no registro)
     */
    public function countCategoriasCustom(int $userId): int
    {
        $total = Categoria::where('user_id', $userId)
            ->whereNull('parent_id')
            ->count();
        $defaultCount = 19; // 12 despesas + 7 receitas criadas automaticamente

        return max(0, $total - $defaultCount);
    }

    /**
     * Verifica se o usuário pode criar uma nova categoria
     */
    public function canCreateCategoria(int $userId): array
    {
        if ($this->isPro($userId)) {
            return ['allowed' => true, 'limit' => null, 'used' => null];
        }

        $limit = $this->getLimit($userId, 'max_categorias_custom');
        $used = $this->countCategoriasCustom($userId);

        if ($limit !== null && $used >= $limit) {
            return [
                'allowed' => false,
                'limit' => $limit,
                'used' => $used,
                'remaining' => 0,
                'message' => $this->getLimitMessage('categorias_limit', ['limit' => $limit]),
                'upgrade_url' => '/assinatura',
            ];
        }

        return [
            'allowed' => true,
            'limit' => $limit,
            'used' => $used,
            'remaining' => $limit - $used,
        ];
    }

    /**
     * Valida e lança exceção se não puder criar categoria
     * 
     * @throws \DomainException
     */
    public function assertCanCreateCategoria(int $userId): void
    {
        $result = $this->canCreateCategoria($userId);
        if (!$result['allowed']) {
            throw new \DomainException($result['message']);
        }
    }

    // ============================================================
    // VALIDAÇÃO DE SUBCATEGORIAS PERSONALIZADAS
    // ============================================================

    /**
     * Conta quantas subcategorias personalizadas o usuário criou
     */
    public function countSubcategoriasCustom(int $userId): int
    {
        return Categoria::where('user_id', $userId)
            ->whereNotNull('parent_id')
            ->count();
    }

    /**
     * Verifica se o usuário pode criar uma nova subcategoria
     */
    public function canCreateSubcategoria(int $userId): array
    {
        if ($this->isPro($userId)) {
            return ['allowed' => true, 'limit' => null, 'used' => null];
        }

        $limit = $this->getLimit($userId, 'max_subcategorias_custom');
        $used = $this->countSubcategoriasCustom($userId);

        if ($limit !== null && $used >= $limit) {
            return [
                'allowed' => false,
                'limit' => $limit,
                'used' => $used,
                'remaining' => 0,
                'message' => $this->getLimitMessage('subcategorias_limit', ['limit' => $limit]),
                'upgrade_url' => '/assinatura',
            ];
        }

        return [
            'allowed' => true,
            'limit' => $limit,
            'used' => $used,
            'remaining' => $limit - $used,
        ];
    }

    /**
     * Valida e lança exceção se não puder criar subcategoria
     * 
     * @throws \DomainException
     */
    public function assertCanCreateSubcategoria(int $userId): void
    {
        $result = $this->canCreateSubcategoria($userId);
        if (!$result['allowed']) {
            throw new \DomainException($result['message']);
        }
    }

    // ============================================================
    // VALIDAÇÃO DE METAS FINANCEIRAS
    // ============================================================

    /**
     * Conta quantas metas ativas o usuário possui
     */
    public function countMetas(int $userId): int
    {
        return Meta::where('user_id', $userId)
            ->whereNotIn('status', ['cancelada', 'concluida'])
            ->count();
    }

    /**
     * Verifica se o usuário pode criar uma nova meta
     */
    public function canCreateMeta(int $userId): array
    {
        if ($this->isPro($userId)) {
            return ['allowed' => true, 'limit' => null, 'used' => null];
        }

        $limit = $this->getLimit($userId, 'max_metas');
        $used = $this->countMetas($userId);

        if ($limit !== null && $used >= $limit) {
            return [
                'allowed' => false,
                'limit' => $limit,
                'used' => $used,
                'remaining' => 0,
                'message' => $this->getLimitMessage('metas_limit', ['limit' => $limit]),
                'upgrade_url' => '/assinatura',
            ];
        }

        return [
            'allowed' => true,
            'limit' => $limit,
            'used' => $used,
            'remaining' => $limit - $used,
        ];
    }

    /**
     * Valida e lança exceção se não puder criar meta
     * 
     * @throws \DomainException
     */
    public function assertCanCreateMeta(int $userId): void
    {
        $result = $this->canCreateMeta($userId);
        if (!$result['allowed']) {
            throw new \DomainException($result['message']);
        }
    }

    // ============================================================
    // RESTRIÇÃO DE HISTÓRICO
    // ============================================================

    /**
     * Obtém a data mínima que o usuário pode consultar
     */
    public function getMinHistoryDate(int $userId): ?string
    {
        if ($this->isPro($userId)) {
            return null; // Sem restrição
        }

        $mesesLimite = $this->getLimit($userId, 'historico_meses');
        if ($mesesLimite === null) {
            return null;
        }

        return date('Y-m-01', strtotime("-{$mesesLimite} months"));
    }

    /**
     * Verifica se a data está dentro do período permitido
     */
    public function isDateAllowed(int $userId, string $date): bool
    {
        $minDate = $this->getMinHistoryDate($userId);
        if ($minDate === null) {
            return true;
        }

        return $date >= $minDate;
    }

    /**
     * Retorna informação sobre restrição de histórico
     */
    public function getHistoryRestriction(int $userId): array
    {
        if ($this->isPro($userId)) {
            return ['restricted' => false, 'months_limit' => null, 'min_date' => null];
        }

        $limit = $this->getLimit($userId, 'historico_meses');
        $minDate = $this->getMinHistoryDate($userId);

        return [
            'restricted' => $limit !== null,
            'months_limit' => $limit,
            'min_date' => $minDate,
            'message' => $this->getLimitMessage('historico_limit', ['limit' => $limit]),
            'upgrade_url' => '/assinatura',
        ];
    }

    // ============================================================
    // VALIDAÇÃO DE ORÇAMENTOS POR CATEGORIA
    // ============================================================

    /**
     * Conta quantos orçamentos ativos o usuário possui (categorias com orçamento)
     */
    public function countOrcamentos(int $userId): int
    {
        return \Application\Models\OrcamentoCategoria::where('user_id', $userId)
            ->where('mes', (int) date('m'))
            ->where('ano', (int) date('Y'))
            ->count();
    }

    /**
     * Verifica se o usuário pode criar um novo orçamento
     */
    public function canCreateOrcamento(int $userId): array
    {
        if ($this->isPro($userId)) {
            return ['allowed' => true, 'limit' => null, 'used' => null];
        }

        $limit = $this->getLimit($userId, 'max_orcamentos');
        $used = $this->countOrcamentos($userId);

        if ($limit !== null && $used >= $limit) {
            return [
                'allowed' => false,
                'limit' => $limit,
                'used' => $used,
                'remaining' => 0,
                'message' => $this->getLimitMessage('orcamentos_limit', ['limit' => $limit]),
                'upgrade_url' => '/assinatura',
            ];
        }

        return [
            'allowed' => true,
            'limit' => $limit,
            'used' => $used,
            'remaining' => $limit - $used,
        ];
    }

    /**
     * Valida e lança exceção se não puder criar orçamento
     * 
     * @throws \DomainException
     */
    public function assertCanCreateOrcamento(int $userId): void
    {
        $result = $this->canCreateOrcamento($userId);
        if (!$result['allowed']) {
            throw new \DomainException($result['message']);
        }
    }

    // ============================================================
    // VERIFICAÇÃO DE FEATURES
    // ============================================================

    /**
     * Verifica se uma feature está disponível para o usuário
     */
    public function hasFeature(int $userId, string $featureName): bool
    {
        $plan = $this->isPro($userId) ? 'pro' : 'free';
        return (bool) ($this->config['features'][$plan][$featureName] ?? false);
    }

    /**
     * Retorna todas as features do usuário
     */
    public function getFeatures(int $userId): array
    {
        $plan = $this->isPro($userId) ? 'pro' : 'free';
        return $this->config['features'][$plan] ?? [];
    }

    // ============================================================
    // RESUMO GERAL DE LIMITES
    // ============================================================

    /**
     * Retorna um resumo completo dos limites do usuário
     */
    public function getLimitsSummary(int $userId): array
    {
        $isPro = $this->isPro($userId);

        return [
            'plan' => $isPro ? 'pro' : 'free',
            'is_pro' => $isPro,
            'contas' => $this->canCreateConta($userId),
            'cartoes' => $this->canCreateCartao($userId),
            'categorias' => $this->canCreateCategoria($userId),
            'metas' => $this->canCreateMeta($userId),
            'orcamentos' => $this->canCreateOrcamento($userId),
            'historico' => $this->getHistoryRestriction($userId),
            'features' => $this->getFeatures($userId),
            'upgrade_url' => '/assinatura',
            'upgrade_cta' => $this->config['messages']['upgrade_cta'] ?? 'Faça upgrade!',
        ];
    }
}
