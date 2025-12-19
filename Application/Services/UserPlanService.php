<?php

declare(strict_types=1);

namespace Application\Services;

use Application\Models\Usuario;

/**
 * Serviço para gerenciar plano e limites de usuários.
 */
class UserPlanService
{
    private const FREE_LANCAMENTOS_LIMIT = 50;
    private const FREE_LANCAMENTOS_WARNING = 40;

    /**
     * Verifica se o usuário tem plano Pro.
     */
    public function isProUser(int $userId): bool
    {
        try {
            /** @var Usuario|null $user */
            $user = Usuario::find($userId);
            if (!$user) {
                return false;
            }

            // Busca assinatura ativa
            $assinatura = $user->assinaturas()
                ->where('status', 'active')
                ->orderByDesc('created_at')
                ->first();

            if (!$assinatura) {
                return false;
            }

            // Carrega o plano
            $plano = $assinatura->plano;
            if (!$plano) {
                return false;
            }

            // Se o plano NÃO for free, é PRO
            $code = strtolower((string)$plano->code);

            return $code !== 'free';
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Obtém o limite de lançamentos para usuários free.
     */
    public function getFreeLancamentosLimit(): int
    {
        return self::FREE_LANCAMENTOS_LIMIT;
    }

    /**
     * Obtém o limite de aviso para usuários free.
     */
    public function getFreeLancamentosWarningAt(): int
    {
        return self::FREE_LANCAMENTOS_WARNING;
    }

    /**
     * Constrói metadados de uso para o mês.
     */
    public function buildUsageMeta(int $userId, string $month, int $usedCount): array
    {
        $isPro = $this->isProUser($userId);
        $limit = $this->getFreeLancamentosLimit();
        $warn = $this->getFreeLancamentosWarningAt();

        return [
            'month' => $month,
            'plan' => $isPro ? 'pro' : 'free',
            'limit' => $isPro ? null : $limit,
            'used' => $usedCount,
            'remaining' => $isPro ? null : max(0, $limit - $usedCount),
            'warning_at' => $warn,
            'should_warn' => (!$isPro && $usedCount >= $warn && $usedCount < $limit),
            'blocked' => (!$isPro && $usedCount >= $limit),
        ];
    }

    /**
     * Gera mensagem de UI para o usuário baseado no uso.
     */
    public function getUsageMessage(array $usage): ?string
    {
        if (!($usage['should_warn'] ?? false)) {
            return null;
        }

        return "⚠️ Atenção: você já usou {$usage['used']} de 50 lançamentos do plano gratuito. " .
               "Faltam {$usage['remaining']} este mês.";
    }
}
