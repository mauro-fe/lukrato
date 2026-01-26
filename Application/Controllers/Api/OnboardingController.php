<?php

namespace Application\Controllers\Api;

use Application\Core\Response;
use Application\Lib\Auth;
use Application\Services\LogService;
use Throwable;

/**
 * Controller para gerenciar status do onboarding
 * 
 * O onboarding tem os seguintes estados:
 * - Não iniciado: onboarding_completed_at = null
 * - Tour guiado: onboarding_mode = 'guided', onboarding_tour_skipped_at = null
 * - Explorar por conta própria: onboarding_mode = 'self'
 * - Tour pulado: onboarding_tour_skipped_at != null
 */
class OnboardingController
{
    /**
     * Retorna o status completo do onboarding do usuário
     * GET /api/onboarding/status
     */
    public function status(): void
    {
        try {
            $user = Auth::user();

            if (!$user) {
                Response::error('Não autenticado', 401);
                return;
            }

            Response::success($user->getOnboardingStatus());
        } catch (Throwable $e) {
            LogService::error('Erro ao verificar status do onboarding', [
                'error' => $e->getMessage()
            ]);
            Response::error('Erro interno', 500);
        }
    }

    /**
     * Marca o onboarding como completo com o modo escolhido
     * POST /api/onboarding/complete
     * Body: { "mode": "guided" | "self" }
     */
    public function complete(): void
    {
        try {
            $user = Auth::user();

            if (!$user) {
                Response::error('Não autenticado', 401);
                return;
            }

            // Obter modo do body da requisição
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            $mode = $input['mode'] ?? 'guided';

            // Validar modo
            if (!in_array($mode, ['guided', 'self'])) {
                Response::error('Modo inválido. Use "guided" ou "self".', 400);
                return;
            }

            // Já está completo? Retorna sucesso sem atualizar (exceto se for pra atualizar o modo)
            if ($user->onboarding_completed_at && $user->onboarding_mode === $mode) {
                Response::success(
                    array_merge($user->getOnboardingStatus(), ['already_completed' => true]),
                    'Onboarding já foi completado'
                );
                return;
            }

            // Marca como completo com o modo escolhido
            $saved = $user->markOnboardingComplete($mode);
            if (!$saved) {
                LogService::error('Falha ao salvar onboarding', [
                    'user_id' => $user->id,
                    'mode' => $mode,
                ]);
                Response::error('Erro ao salvar status do onboarding.', 500);
                return;
            }

            LogService::info('Onboarding completado', [
                'user_id' => $user->id,
                'mode' => $mode,
            ]);

            Response::success(
                $user->getOnboardingStatus(),
                'Onboarding completado com sucesso'
            );
        } catch (Throwable $e) {
            LogService::error('Erro ao completar onboarding', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            Response::error('Erro interno', 500);
        }
    }

    /**
     * Marca o tour como pulado
     * POST /api/onboarding/skip-tour
     */
    public function skipTour(): void
    {
        try {
            $user = Auth::user();

            if (!$user) {
                Response::error('Não autenticado', 401);
                return;
            }

            // Já pulou? Retorna sucesso sem atualizar
            if ($user->onboarding_tour_skipped_at) {
                Response::success(
                    array_merge($user->getOnboardingStatus(), ['already_skipped' => true]),
                    'Tour já foi pulado anteriormente'
                );
                return;
            }

            // Marca como pulado
            $saved = $user->skipOnboardingTour();
            if (!$saved) {
                LogService::error('Falha ao marcar tour como pulado', [
                    'user_id' => $user->id,
                ]);
                Response::error('Erro ao salvar status do tour.', 500);
                return;
            }

            LogService::info('Tour do onboarding pulado', [
                'user_id' => $user->id,
            ]);

            Response::success(
                $user->getOnboardingStatus(),
                'Tour pulado com sucesso'
            );
        } catch (Throwable $e) {
            LogService::error('Erro ao pular tour', [
                'error' => $e->getMessage(),
            ]);
            Response::error('Erro interno', 500);
        }
    }

    /**
     * Reseta o onboarding completamente (útil para testes)
     * POST /api/onboarding/reset
     */
    public function reset(): void
    {
        try {
            $user = Auth::user();

            if (!$user) {
                Response::error('Não autenticado', 401);
                return;
            }

            $user->onboarding_completed_at = null;
            $user->onboarding_mode = null;
            $user->onboarding_tour_skipped_at = null;
            $user->save();

            LogService::info('Onboarding resetado', [
                'user_id' => $user->id,
            ]);

            Response::success(
                $user->getOnboardingStatus(),
                'Onboarding resetado'
            );
        } catch (Throwable $e) {
            LogService::error('Erro ao resetar onboarding', [
                'error' => $e->getMessage()
            ]);
            Response::error('Erro interno', 500);
        }
    }
}
