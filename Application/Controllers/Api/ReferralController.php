<?php

namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Services\ReferralService;
use Application\Lib\Auth;
use Exception;

class ReferralController extends BaseController
{
    private ReferralService $referralService;

    public function __construct()
    {
        parent::__construct();
        $this->referralService = new ReferralService();
    }

    /**
     * GET /api/referral/stats
     * Retorna as estatísticas de indicação do usuário logado
     */
    public function getStats(): void
    {
        $this->requireAuthApi();

        try {
            $user = Auth::user();

            // Garante que o usuário tenha um código de indicação
            $this->referralService->ensureUserHasReferralCode($user);

            $stats = $this->referralService->getUserStats($user);

            Response::success($stats, 'Estatísticas de indicação');
        } catch (Exception $e) {
            Response::error('Erro ao carregar estatísticas: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/referral/validate?code=XXXXXXXX
     * Valida um código de indicação (usado no cadastro)
     */
    public function validateCode(): void
    {
        try {
            $code = $_GET['code'] ?? '';

            if (empty($code)) {
                Response::error('Código de indicação não informado', 400);
                return;
            }

            // Se estiver logado, não pode usar seu próprio código
            $excludeUserId = Auth::isLoggedIn() ? Auth::id() : null;

            $result = $this->referralService->validateCode($code, $excludeUserId);

            if ($result['valid']) {
                Response::success([
                    'valid' => true,
                    'referrer_name' => $result['referrer']->nome,
                    'reward_days' => ReferralService::REFERRED_REWARD_DAYS,
                ], $result['message']);
            } else {
                Response::error($result['message'], 400);
            }
        } catch (Exception $e) {
            Response::error('Erro ao validar código: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/referral/code
     * Retorna o código de indicação do usuário logado
     */
    public function getCode(): void
    {
        $this->requireAuthApi();

        try {
            $user = Auth::user();

            // Garante que o usuário tenha um código de indicação
            $code = $this->referralService->ensureUserHasReferralCode($user);
            $link = $this->referralService->getReferralLink($user);

            Response::success([
                'code' => $code,
                'link' => $link,
                'reward_days' => ReferralService::REFERRER_REWARD_DAYS,
            ], 'Código de indicação');
        } catch (Exception $e) {
            Response::error('Erro ao obter código: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/referral/ranking
     * Retorna o ranking de indicações
     */
    public function getRanking(): void
    {
        $this->requireAuthApi();

        try {
            $limit = min(intval($_GET['limit'] ?? 10), 50);
            $ranking = $this->referralService->getReferralRanking($limit);

            Response::success([
                'ranking' => $ranking,
            ], 'Ranking de indicações');
        } catch (Exception $e) {
            Response::error('Erro ao carregar ranking: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/referral/info
     * Retorna informações gerais sobre o programa de indicação
     */
    public function getInfo(): void
    {
        try {
            Response::success([
                'referrer_reward_days' => ReferralService::REFERRER_REWARD_DAYS,
                'referred_reward_days' => ReferralService::REFERRED_REWARD_DAYS,
                'description' => 'Indique amigos para o Lukrato e ganhe ' . ReferralService::REFERRER_REWARD_DAYS . ' dias de PRO! Seu amigo também ganha ' . ReferralService::REFERRED_REWARD_DAYS . ' dias grátis.',
            ], 'Informações do programa de indicação');
        } catch (Exception $e) {
            Response::error('Erro ao carregar informações: ' . $e->getMessage(), 500);
        }
    }
}
