<?php

namespace Application\Controllers\Api\User;

use Application\Core\Response;
use Application\Lib\Auth;
use Application\Services\Infrastructure\LogService;
use Throwable;

class TourController
{
    /**
     * POST /api/tour/complete
     */
    public function complete(): void
    {
        try {
            $user = Auth::user();

            if (!$user) {
                Response::error('Não autenticado', 401);
                return;
            }

            if ($user->tour_completed_at) {
                Response::success([
                    'already_completed' => true
                ]);
                return;
            }

            $user->tour_completed_at = now();
            $user->save();

            LogService::info('Tour concluído', [
                'user_id' => $user->id
            ]);

            Response::success([
                'tour_completed' => true
            ]);

        } catch (Throwable $e) {
            LogService::error('Erro ao completar tour', [
                'error' => $e->getMessage()
            ]);
            Response::error('Erro interno', 500);
        }
    }
}
