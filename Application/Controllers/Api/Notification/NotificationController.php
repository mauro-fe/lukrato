<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Notification;

use Application\Controllers\ApiController;
use Application\Core\Response;
use Application\Models\Notification;
use Application\Services\Communication\NotificationService;
use Exception;

/**
 * API para gerenciamento de notificações do usuário logado.
 */
class NotificationController extends ApiController
{
    private NotificationService $notificationService;

    public function __construct(?NotificationService $notificationService = null)
    {
        parent::__construct();
        $this->notificationService = $this->resolveOrCreate($notificationService, NotificationService::class);
    }

    public function index(): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        try {
            $limit = $this->getIntQuery('limit', 20);
            $unreadOnly = $this->getBoolQuery('unread_only', false);

            $notifications = $this->notificationService->getUserNotifications(
                $userId,
                min($limit, 50),
                $unreadOnly
            );

            $notifications = array_map(function (array $notification): array {
                $model = new Notification($notification);

                return array_merge($notification, [
                    'icon' => $model->icon,
                    'color' => $model->color,
                    'time_ago' => $this->timeAgo((string) $notification['created_at']),
                ]);
            }, $notifications);

            return Response::successResponse([
                'notifications' => $notifications,
                'total' => count($notifications),
            ], 'Notificacoes listadas com sucesso');
        } catch (Exception $e) {
            return $this->internalErrorResponse($e, 'Erro ao listar notificacoes.');
        }
    }

    public function count(): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        try {
            $count = $this->notificationService->getUnreadCount($userId);

            return Response::successResponse([
                'unread_count' => $count,
            ]);
        } catch (Exception $e) {
            return $this->internalErrorResponse($e, 'Erro ao contar notificacoes.');
        }
    }

    public function markAsRead(int $id): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        try {
            $success = $this->notificationService->markAsRead($id, $userId);

            if (!$success) {
                return Response::errorResponse('Notificação não encontrada', 404);
            }

            return Response::successResponse(['marked' => true], 'Notificacao marcada como lida');
        } catch (Exception $e) {
            return $this->internalErrorResponse($e, 'Erro ao marcar notificacao como lida.');
        }
    }

    public function markAllAsRead(): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        try {
            $count = $this->notificationService->markAllAsRead($userId);

            return Response::successResponse([
                'marked_count' => $count,
            ], 'Todas as notificacoes foram marcadas como lidas');
        } catch (Exception $e) {
            return $this->internalErrorResponse($e, 'Erro ao marcar notificacoes como lidas.');
        }
    }

    public function destroy(int $id): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        try {
            $success = $this->notificationService->deleteNotification($id, $userId);

            if (!$success) {
                return Response::errorResponse('Notificacao nao encontrada', 404);
            }

            return Response::successResponse(['deleted' => true], 'Notificacao deletada com sucesso');
        } catch (Exception $e) {
            return $this->internalErrorResponse($e, 'Erro ao deletar notificacao.');
        }
    }

    public function deleteRead(): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        try {
            $count = $this->notificationService->deleteReadNotifications($userId);

            return Response::successResponse([
                'deleted_count' => $count,
            ], 'Notificacoes lidas foram deletadas');
        } catch (Exception $e) {
            return $this->internalErrorResponse($e, 'Erro ao deletar notificacoes.');
        }
    }

    private function timeAgo(string $datetime): string
    {
        $time = strtotime($datetime);
        $now = time();
        $diff = $now - $time;

        if ($diff < 60) {
            return 'agora mesmo';
        }

        if ($diff < 3600) {
            $minutes = floor($diff / 60);

            return "ha {$minutes} " . ($minutes === 1 ? 'minuto' : 'minutos');
        }

        if ($diff < 86400) {
            $hours = floor($diff / 3600);

            return "ha {$hours} " . ($hours === 1 ? 'hora' : 'horas');
        }

        if ($diff < 604800) {
            $days = floor($diff / 86400);

            return "ha {$days} " . ($days === 1 ? 'dia' : 'dias');
        }

        if ($diff < 2592000) {
            $weeks = floor($diff / 604800);

            return "ha {$weeks} " . ($weeks === 1 ? 'semana' : 'semanas');
        }

        return date('d/m/Y', $time);
    }
}
