<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Referral;

use Application\Controllers\ApiController;
use Application\Core\Response;
use Application\Lib\Auth;
use Application\Models\Usuario;
use Application\Services\Referral\ReferralService;
use Exception;

class ReferralController extends ApiController
{
    private ReferralService $referralService;

    public function __construct(?ReferralService $referralService = null)
    {
        parent::__construct();
        $this->referralService = $this->resolveOrCreate($referralService, ReferralService::class);
    }

    public function getStats(): Response
    {
        $user = $this->authenticatedUser();

        return $this->executeReferralAction(function () use ($user): Response {
            $this->referralService->ensureUserHasReferralCode($user);
            $stats = $this->referralService->getUserStats($user);

            return Response::successResponse($stats, 'Estatísticas de indicação');
        }, 'Erro ao carregar estatisticas.');
    }

    public function validateCode(): Response
    {
        $code = $this->requestedCode();
        if ($code === '') {
            return Response::errorResponse('Código de indicação não informado', 400);
        }

        return $this->executeReferralAction(function () use ($code): Response {
            $result = $this->referralService->validateCode($code, $this->excludeUserId());

            if ($result['valid']) {
                return Response::successResponse([
                    'valid' => true,
                    'referrer_name' => $result['referrer']->nome,
                    'reward_days' => ReferralService::REFERRED_REWARD_DAYS,
                ], $result['message']);
            }

            return Response::errorResponse($result['message'], 400);
        }, 'Erro ao validar codigo.');
    }

    public function getCode(): Response
    {
        $user = $this->authenticatedUser();

        return $this->executeReferralAction(function () use ($user): Response {
            $code = $this->referralService->ensureUserHasReferralCode($user);
            $link = $this->referralService->getReferralLink($user);

            return Response::successResponse([
                'code' => $code,
                'link' => $link,
                'reward_days' => ReferralService::REFERRER_REWARD_DAYS,
            ], 'Codigo de indicacao');
        }, 'Erro ao obter codigo.');
    }

    public function getRanking(): Response
    {
        $this->requireApiUserIdAndReleaseSessionOrFail();

        return $this->executeReferralAction(function (): Response {
            $limit = min($this->getIntQuery('limit', 10), 50);
            $ranking = $this->referralService->getReferralRanking($limit);

            return Response::successResponse([
                'ranking' => $ranking,
            ], 'Ranking de indicacoes');
        }, 'Erro ao carregar ranking.');
    }

    public function getInfo(): Response
    {
        return $this->executeReferralAction(function (): Response {
            return Response::successResponse([
                'referrer_reward_days' => ReferralService::REFERRER_REWARD_DAYS,
                'referred_reward_days' => ReferralService::REFERRED_REWARD_DAYS,
                'description' => 'Indique amigos para o Lukrato e ganhe '
                    . ReferralService::REFERRER_REWARD_DAYS
                    . ' dias de PRO! Seu amigo também ganha '
                    . ReferralService::REFERRED_REWARD_DAYS
                    . ' dias grátis.',
            ], 'Informações do programa de indicação');
        }, 'Erro ao carregar informações.');
    }

    private function authenticatedUser(): Usuario
    {
        return $this->requireApiUserAndReleaseSessionOrFail();
    }

    private function requestedCode(): string
    {
        return $this->getStringQuery('code', '');
    }

    private function excludeUserId(): ?int
    {
        return Auth::isLoggedIn() ? Auth::id() : null;
    }

    /**
     * @param callable():Response $action
     */
    private function executeReferralAction(callable $action, string $fallbackMessage): Response
    {
        try {
            return $action();
        } catch (Exception $e) {
            return $this->internalErrorResponse($e, $fallbackMessage);
        }
    }
}
