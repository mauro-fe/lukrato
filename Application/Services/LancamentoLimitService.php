<?php

namespace Application\Services;

use Application\Models\Lancamento;
use Application\Models\Usuario;
use Illuminate\Database\Capsule\Manager as DB;

class LancamentoLimitService
{
    public function getFreeLimit(): int
    {
        return 50;
    }

    public function getWarningAt(): int
    {
        return 40;
    }

    public function isPro(int $userId): bool
    {
        try {
            /** @var Usuario|null $user */
            $user = Usuario::find($userId);
            if (!$user) return false;

            $assinatura = $user->assinaturas()
                ->where('status', 'active')
                ->orderByDesc('created_at')
                ->first();

            if (!$assinatura) return false;

            $plano = $assinatura->plano;
            if (!$plano) return false;

            $code = strtolower((string)$plano->code);

            // Se existir plano "free" no banco, ele não conta como Pro
            return $code !== 'free';
        } catch (\Throwable) {
            return false;
        }
    }

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

    public function usage(int $userId, string $ym): array
    {
        $isPro = $this->isPro($userId);

        $limit = $this->getFreeLimit();
        $warn  = $this->getWarningAt();
        $used  = $this->countUsedInMonth($userId, $ym);

        return [
            'month'       => $ym,
            'plan'        => $isPro ? 'pro' : 'free',
            'limit'       => $isPro ? null : $limit,
            'used'        => $used,
            'remaining'   => $isPro ? null : max(0, $limit - $used),
            'warning_at'  => $warn,
            'should_warn' => (!$isPro && $used >= $warn && $used < $limit),
            'blocked'     => (!$isPro && $used >= $limit),
        ];
    }

    /**
     * Valida se pode criar um lançamento naquele mês.
     * Retorna usage atualizado (pra você mandar pro front).
     *
     * @throws \DomainException quando estiver bloqueado
     */
    public function assertCanCreate(int $userId, string $dateYmd): array
    {
        $ym = substr($dateYmd, 0, 7); // YYYY-MM
        $usage = $this->usage($userId, $ym);

        if (($usage['blocked'] ?? false) === true) {
            throw new \DomainException(
                'Você atingiu o limite de 50 lançamentos deste mês no plano gratuito. Ative o Lukrato Pro para continuar.'
            );
        }

        return $usage;
    }
}
