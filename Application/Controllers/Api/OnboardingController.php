<?php

namespace Application\Controllers\Api;

use Application\Core\Response;
use Application\Lib\Auth;
use Application\Models\Usuario;
use Application\Services\LogService;
use Throwable;

/**
 * Controller para gerenciar status do onboarding
 */
class OnboardingController
{
    /**
     * Retorna o status do onboarding do usuário
     */
    public function status(): void
    {
        try {
            $user = Auth::user();

            if (!$user) {
                Response::error('Não autenticado', 401);
                return;
            }

            Response::success([
                'completed' => (bool) $user->onboarding_completed_at,
                'completed_at' => $user->onboarding_completed_at,
            ]);
        } catch (Throwable $e) {
            LogService::error('Erro ao verificar status do onboarding', [
                'error' => $e->getMessage()
            ]);
            Response::error('Erro interno', 500);
        }
    }

    /**
     * Marca o onboarding como completo
     */
    public function complete(): void
    {
        try {
            $user = Auth::user();

            if (!$user) {
                Response::error('Não autenticado', 401);
                return;
            }

            // Já está completo? Retorna sucesso sem atualizar
            if ($user->onboarding_completed_at) {
                Response::success([
                    'completed' => true,
                    'completed_at' => $user->onboarding_completed_at,
                    'already_completed' => true,
                ], 'Onboarding já foi completado');
                return;
            }

            // Marca como completo
            $saved = $user->markOnboardingComplete();
            if (!$saved) {
                LogService::error('Falha ao salvar onboarding_completed_at', [
                    'user_id' => $user->id,
                    'attributes' => $user->getAttributes(),
                ]);
                Response::error('Erro ao salvar status do onboarding.', 500);
                return;
            }

            LogService::info('Onboarding completado', [
                'user_id' => $user->id,
            ]);

            Response::success([
                'completed' => true,
                'completed_at' => $user->onboarding_completed_at,
            ], 'Onboarding completado com sucesso');
        } catch (Throwable $e) {
            LogService::error('Erro ao completar onboarding', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            Response::error('Erro interno', 500);
        }
    }

    /**
     * Reseta o onboarding (útil para testes ou se usuário quiser ver novamente)
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
            $user->save();

            LogService::info('Onboarding resetado', [
                'user_id' => $user->id,
            ]);

            Response::success([
                'completed' => false,
                'completed_at' => null,
            ], 'Onboarding resetado');
        } catch (Throwable $e) {
            LogService::error('Erro ao resetar onboarding', [
                'error' => $e->getMessage()
            ]);
            Response::error('Erro interno', 500);
        }
    }
}
