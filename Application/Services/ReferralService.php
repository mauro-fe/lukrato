<?php

namespace Application\Services;

use Application\Models\Usuario;
use Application\Models\Indicacao;
use Application\Models\AssinaturaUsuario;
use Application\Models\Plano;
use Illuminate\Support\Str;

class ReferralService
{
    /**
     * Dias de PRO que quem indica ganha
     */
    const REFERRER_REWARD_DAYS = 15;

    /**
     * Dias de PRO que o indicado ganha
     */
    const REFERRED_REWARD_DAYS = 7;

    /**
     * Gera um código de indicação único
     * 
     * @return string
     */
    public function generateReferralCode(): string
    {
        $attempts = 0;
        $maxAttempts = 10;

        do {
            // Gera código de 8 caracteres alfanuméricos uppercase
            $code = strtoupper(Str::random(8));
            $exists = Usuario::where('referral_code', $code)->exists();
            $attempts++;
        } while ($exists && $attempts < $maxAttempts);

        if ($exists) {
            // Fallback: adiciona timestamp
            $code = strtoupper(substr(md5(uniqid()), 0, 8));
        }

        return $code;
    }

    /**
     * Busca um usuário pelo código de indicação
     * 
     * @param string $code
     * @return Usuario|null
     */
    public function findUserByCode(string $code): ?Usuario
    {
        if (empty($code)) {
            return null;
        }

        return Usuario::where('referral_code', strtoupper(trim($code)))
            ->first();
    }

    /**
     * Valida se um código de indicação é válido
     * 
     * @param string $code
     * @param int|null $excludeUserId ID do usuário a excluir (para não indicar a si mesmo)
     * @return array ['valid' => bool, 'message' => string, 'referrer' => Usuario|null]
     */
    public function validateCode(string $code, ?int $excludeUserId = null): array
    {
        if (empty($code)) {
            return [
                'valid' => false,
                'message' => 'Código de indicação não informado',
                'referrer' => null
            ];
        }

        $referrer = $this->findUserByCode($code);

        if (!$referrer) {
            return [
                'valid' => false,
                'message' => 'Código de indicação inválido',
                'referrer' => null
            ];
        }

        if ($excludeUserId && $referrer->id === $excludeUserId) {
            return [
                'valid' => false,
                'message' => 'Você não pode usar seu próprio código de indicação',
                'referrer' => null
            ];
        }

        return [
            'valid' => true,
            'message' => 'Código válido! Indicado por ' . $referrer->nome,
            'referrer' => $referrer
        ];
    }

    /**
     * Processa uma indicação quando um novo usuário se cadastra
     * 
     * @param Usuario $referredUser O novo usuário que foi indicado
     * @param string $referralCode O código de quem indicou
     * @return Indicacao|null
     */
    public function processReferral(Usuario $referredUser, string $referralCode): ?Indicacao
    {
        // Valida o código
        $validation = $this->validateCode($referralCode, $referredUser->id);

        if (!$validation['valid']) {
            return null;
        }

        $referrer = $validation['referrer'];

        // Verifica se já existe uma indicação pendente ou completada para este usuário
        $existingReferral = Indicacao::where('referred_id', $referredUser->id)->first();
        if ($existingReferral) {
            return null; // Já foi indicado antes
        }

        // Cria o registro de indicação
        $indicacao = Indicacao::create([
            'referrer_id' => $referrer->id,
            'referred_id' => $referredUser->id,
            'status' => Indicacao::STATUS_PENDING,
            'referrer_reward_days' => self::REFERRER_REWARD_DAYS,
            'referred_reward_days' => self::REFERRED_REWARD_DAYS,
            'referrer_rewarded' => false,
            'referred_rewarded' => false,
        ]);

        // Atualiza o referred_by do usuário
        $referredUser->referred_by = $referrer->id;
        $referredUser->save();

        // Aplica a recompensa imediatamente ao indicado (7 dias de PRO)
        $this->applyRewardToUser($referredUser, self::REFERRED_REWARD_DAYS);
        $indicacao->referred_rewarded = true;
        $indicacao->referred_rewarded_at = now();

        // Aplica a recompensa ao referrer (15 dias de PRO)
        $this->applyRewardToUser($referrer, self::REFERRER_REWARD_DAYS);
        $indicacao->referrer_rewarded = true;
        $indicacao->referrer_rewarded_at = now();

        // Marca como completada
        $indicacao->status = Indicacao::STATUS_COMPLETED;
        $indicacao->completed_at = now();
        $indicacao->save();

        return $indicacao;
    }

    /**
     * Aplica dias de PRO a um usuário
     * 
     * @param Usuario $user
     * @param int $days
     * @return AssinaturaUsuario
     */
    public function applyRewardToUser(Usuario $user, int $days): AssinaturaUsuario
    {
        // Busca o plano PRO
        $planoPro = Plano::where('code', 'pro')->first();

        if (!$planoPro) {
            throw new \Exception('Plano PRO não encontrado');
        }

        // Verifica se já tem assinatura ativa
        $assinaturaAtiva = AssinaturaUsuario::where('user_id', $user->id)
            ->where('status', AssinaturaUsuario::ST_ACTIVE)
            ->first();

        if ($assinaturaAtiva) {
            // Estende a assinatura existente
            $novaData = $assinaturaAtiva->renova_em
                ? $assinaturaAtiva->renova_em->addDays($days)
                : now()->addDays($days);

            $assinaturaAtiva->renova_em = $novaData;
            $assinaturaAtiva->save();

            return $assinaturaAtiva;
        }

        // Cria uma nova assinatura
        $assinatura = AssinaturaUsuario::create([
            'user_id' => $user->id,
            'plano_id' => $planoPro->id,
            'gateway' => 'referral', // Indica que veio de indicação
            'status' => AssinaturaUsuario::ST_ACTIVE,
            'renova_em' => now()->addDays($days),
        ]);

        return $assinatura;
    }

    /**
     * Obtém as estatísticas de indicação de um usuário
     * 
     * @param Usuario $user
     * @return array
     */
    public function getUserStats(Usuario $user): array
    {
        $totalIndicacoes = Indicacao::where('referrer_id', $user->id)->count();

        $indicacoesCompletadas = Indicacao::where('referrer_id', $user->id)
            ->where('status', Indicacao::STATUS_COMPLETED)
            ->count();

        $indicacoesPendentes = Indicacao::where('referrer_id', $user->id)
            ->where('status', Indicacao::STATUS_PENDING)
            ->count();

        $diasGanhos = Indicacao::where('referrer_id', $user->id)
            ->where('referrer_rewarded', true)
            ->sum('referrer_reward_days');

        $ultimasIndicacoes = Indicacao::where('referrer_id', $user->id)
            ->with('referred:id,nome,email,created_at')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($indicacao) {
                return [
                    'id' => $indicacao->id,
                    'nome' => $indicacao->referred ? $indicacao->referred->nome : 'Usuário removido',
                    'status' => $indicacao->status,
                    'status_label' => $indicacao->getStatusLabel(),
                    'reward_days' => $indicacao->referrer_reward_days,
                    'rewarded' => $indicacao->referrer_rewarded,
                    'created_at' => $indicacao->created_at->format('d/m/Y H:i'),
                ];
            });

        return [
            'referral_code' => $user->referral_code,
            'referral_link' => $this->getReferralLink($user),
            'total_indicacoes' => $totalIndicacoes,
            'indicacoes_completadas' => $indicacoesCompletadas,
            'indicacoes_pendentes' => $indicacoesPendentes,
            'dias_ganhos' => $diasGanhos,
            'ultimas_indicacoes' => $ultimasIndicacoes,
        ];
    }

    /**
     * Gera o link de indicação para um usuário
     * 
     * @param Usuario $user
     * @return string
     */
    public function getReferralLink(Usuario $user): string
    {
        $baseUrl = rtrim(getenv('APP_URL') ?: 'https://lukrato.com.br', '/');
        return $baseUrl . '/login?ref=' . $user->referral_code;
    }

    /**
     * Garante que um usuário tenha código de indicação
     * 
     * @param Usuario $user
     * @return string
     */
    public function ensureUserHasReferralCode(Usuario $user): string
    {
        if (empty($user->referral_code)) {
            $user->referral_code = $this->generateReferralCode();
            $user->save();
        }

        return $user->referral_code;
    }

    /**
     * Obtém o ranking de indicações
     * 
     * @param int $limit
     * @return array
     */
    public function getReferralRanking(int $limit = 10): array
    {
        return Indicacao::selectRaw('referrer_id, COUNT(*) as total_indicacoes, SUM(referrer_reward_days) as total_dias')
            ->where('status', Indicacao::STATUS_COMPLETED)
            ->groupBy('referrer_id')
            ->orderByDesc('total_indicacoes')
            ->limit($limit)
            ->with('referrer:id,nome')
            ->get()
            ->map(function ($item) {
                return [
                    'usuario' => $item->referrer ? $item->referrer->nome : 'Usuário removido',
                    'total_indicacoes' => $item->total_indicacoes,
                    'total_dias' => $item->total_dias,
                ];
            })
            ->toArray();
    }
}
