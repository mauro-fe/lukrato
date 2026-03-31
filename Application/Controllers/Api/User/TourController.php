<?php

declare(strict_types=1);

namespace Application\Controllers\Api\User;

use Application\Controllers\ApiController;
use Application\Core\Response;
use Application\Services\Infrastructure\LogService;
use Throwable;

class TourController extends ApiController
{
    /**
     * POST /api/tour/complete
     */
    public function complete(): Response
    {
        $user = $this->requireApiUserOrFail();

        try {
            if ($user->tour_completed_at) {
                return Response::successResponse([
                    'already_completed' => true,
                ]);
            }

            $user->tour_completed_at = now();
            $user->save();

            LogService::info('Tour concluido', [
                'user_id' => $user->id,
            ]);

            return Response::successResponse([
                'tour_completed' => true,
            ]);
        } catch (Throwable $e) {
            return $this->failAndLogResponse($e, 'Nao foi possivel concluir o tour.');
        }
    }
}
