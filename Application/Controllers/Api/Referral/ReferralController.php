<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Referral;

use Application\Controllers\ApiController;
use Application\Core\Response;
use Application\Lib\Auth;
use Application\Services\Referral\ReferralService;
use Exception;

class ReferralController extends ApiController
{
    private ReferralService $referralService;

    public function __construct(?ReferralService $referralService = null)
    {
        parent::__construct();
        $this->referralService = $referralService ?? new ReferralService();
    }

    public function getStats(): Response
    {
        $user = $this->requireApiUserAndReleaseSessionOrFail();

        try {
            $this->referralService->ensureUserHasReferralCode($user);
            $stats = $this->referralService->getUserStats($user);

            return Response::successResponse($stats, 'Estatísticas de indicação');
        } catch (Exception $e) {
            return $this->internalErrorResponse($e, 'Erro ao carregar estatisticas.');
        }
    }

    public function validateCode(): Response
    {
        try {
            $code = $this->getStringQuery('code', '');

            if (empty($code)) {
                return Response::errorResponse('Código de indicação não informado', 400);
            }

            $excludeUserId = Auth::isLoggedIn() ? Auth::id() : null;
            $result = $this->referralService->validateCode($code, $excludeUserId);

            if ($result['valid']) {
                return Response::successResponse([
                    'valid' => true,
                    'referrer_name' => $result['referrer']->nome,
                    'reward_days' => ReferralService::REFERRED_REWARD_DAYS,
                ], $result['message']);
            }

            return Response::errorResponse($result['message'], 400);
        } catch (Exception $e) {
            return $this->internalErrorResponse($e, 'Erro ao validar codigo.');
        }
    }

    public function getCode(): Response
    {
        $user = $this->requireApiUserAndReleaseSessionOrFail();

        try {
            $code = $this->referralService->ensureUserHasReferralCode($user);
            $link = $this->referralService->getReferralLink($user);

            return Response::successResponse([
                'code' => $code,
                'link' => $link,
                'reward_days' => ReferralService::REFERRER_REWARD_DAYS,
            ], 'Codigo de indicacao');
        } catch (Exception $e) {
            return $this->internalErrorResponse($e, 'Erro ao obter codigo.');
        }
    }

    public function getRanking(): Response
    {
        $this->requireApiUserIdAndReleaseSessionOrFail();

        try {
            $limit = min($this->getIntQuery('limit', 10), 50);
            $ranking = $this->referralService->getReferralRanking($limit);

            return Response::successResponse([
                'ranking' => $ranking,
            ], 'Ranking de indicacoes');
        } catch (Exception $e) {
            return $this->internalErrorResponse($e, 'Erro ao carregar ranking.');
        }
    }

    public function getInfo(): Response
    {
        try {
            return Response::successResponse([
                'referrer_reward_days' => ReferralService::REFERRER_REWARD_DAYS,
                'referred_reward_days' => ReferralService::REFERRED_REWARD_DAYS,
                'description' => 'Indique amigos para o Lukrato e ganhe '
                    . ReferralService::REFERRER_REWARD_DAYS
                    . ' dias de PRO! Seu amigo tambem ganha '
                    . ReferralService::REFERRED_REWARD_DAYS
                    . ' dias gratis.',
            ], 'Informacoes do programa de indicacao');
        } catch (Exception $e) {
            return $this->internalErrorResponse($e, 'Erro ao carregar informacoes.');
        }
    }
}
