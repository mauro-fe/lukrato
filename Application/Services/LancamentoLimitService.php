<?php

namespace Application\Services;

use Application\Models\Lancamento;
use Application\Models\Usuario;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Serviço responsável por gerenciar limites de lançamentos por plano
 */
class LancamentoLimitService
{
    private array $config;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../Config/Billing.php';
    }

    /**
     * Obtém o limite de lançamentos do plano gratuito
     */
    public function getFreeLimit(): int
    {
        return (int) ($this->config['limits']['free']['lancamentos_per_month'] ?? 50);
    }

    /**
     * Obtém o threshold para exibir aviso de limite
     */
    public function getWarningAt(): int
    {
        return (int) ($this->config['limits']['free']['warning_at'] ?? 40);
    }

    /**
     * Verifica se o usuário possui plano Pro ativo
     */
    public function isPro(int $userId): bool
    {
        try {
            /** @var Usuario|null $user */
            $user = Usuario::find($userId);
            if (!$user) {
                return false;
            }

            $assinatura = $user->assinaturas()
                ->where('status', 'active')
                ->orderByDesc('created_at')
                ->first();

            if (!$assinatura) {
                return false;
            }

            $plano = $assinatura->plano;
            if (!$plano) {
                return false;
            }

            $code = strtolower((string) $plano->code);

            // Plano "free" não conta como Pro
            return $code !== 'free';
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Conta quantos lançamentos o usuário criou no mês especificado
     */
    public function countUsedInMonth(int $userId, string $ym): int
    {
        $from = $ym . '-01';
        $to   = date('Y-m-t', strtotime($from));

        return (int) Lancamento::where('user_id', $userId)
            ->whereBetween('data', [$from, $to])
            ->where('eh_saldo_inicial', 0)
            ->where('eh_transferencia', 0)
            ->count();
    }

    /**
     * Retorna informações de uso do mês atual para o usuário
     */
    public function usage(int $userId, string $ym): array
    {
        $isPro = $this->isPro($userId);
        $limit = $this->getFreeLimit();
        $warn  = $this->getWarningAt();
        $used  = $this->countUsedInMonth($userId, $ym);
        $remaining = $isPro ? null : max(0, $limit - $used);

        return [
            'month'       => $ym,
            'plan'        => $isPro ? 'pro' : 'free',
            'limit'       => $isPro ? null : $limit,
            'used'        => $used,
            'remaining'   => $remaining,
            'warning_at'  => $warn,
            'should_warn' => (!$isPro && $used >= $warn && $used < $limit),
            'blocked'     => (!$isPro && $used >= $limit),
            'percentage'  => $isPro ? null : (int) (($used / $limit) * 100),
        ];
    }

    /**
     * Valida se o usuário pode criar um lançamento no mês especificado
     * 
     * @throws \DomainException quando o limite for atingido
     * @return array Informações de uso atualizadas
     */
    public function assertCanCreate(int $userId, string $dateYmd): array
    {
        $ym = substr($dateYmd, 0, 7); // YYYY-MM
        $usage = $this->usage($userId, $ym);

        if ($usage['blocked'] ?? false) {
            $message = $this->getBlockedMessage($usage);
            throw new \DomainException($message);
        }

        return $usage;
    }

    /**
     * Obtém a mensagem de bloqueio personalizada
     */
    private function getBlockedMessage(array $usage): string
    {
        $template = $this->config['messages']['limit_reached'] ?? 
                   'Você atingiu o limite de {limit} lançamentos deste mês no plano gratuito.';

        return $this->interpolateMessage($template, $usage);
    }

    /**
     * Gera mensagem de aviso apropriada baseada no uso atual
     */
    public function getWarningMessage(array $usage): ?string
    {
        if (!($usage['should_warn'] ?? false)) {
            return null;
        }

        $percentage = $usage['percentage'] ?? 0;
        $criticalThreshold = $this->config['limits']['free']['warning_critical_at'] ?? 45;
        
        // Determina qual template usar baseado na severidade
        if ($percentage >= 90 || $usage['used'] >= $criticalThreshold) {
            $template = $this->config['messages']['warning_critical'] ?? 
                       '🔴 Atenção crítica! Você já usou {used} de {limit} lançamentos ({percentage}%).';
        } else {
            $template = $this->config['messages']['warning_normal'] ?? 
                       '⚠️ Atenção: Você já usou {used} de {limit} lançamentos ({percentage}%).';
        }

        return $this->interpolateMessage($template, $usage);
    }

    /**
     * Substitui variáveis no template de mensagem
     */
    private function interpolateMessage(string $template, array $usage): string
    {
        $replacements = [
            '{used}'       => $usage['used'] ?? 0,
            '{limit}'      => $usage['limit'] ?? $this->getFreeLimit(),
            '{remaining}'  => $usage['remaining'] ?? 0,
            '{percentage}' => $usage['percentage'] ?? 0,
        ];

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $template
        );
    }

    /**
     * Retorna a CTA (Call-to-Action) para upgrade
     */
    public function getUpgradeCta(): string
    {
        return $this->config['messages']['upgrade_cta'] ?? 
               'Assine o Lukrato Pro e tenha lançamentos ilimitados!';
    }
}
