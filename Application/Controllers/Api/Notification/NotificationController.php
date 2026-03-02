<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Notification;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Lib\Auth;
use Application\Models\Notification;
use Application\Services\Communication\NotificationService;
use Exception;

/**
 * NotificationController
 * 
 * API para gerenciamento de notificações do usuário logado.
 * Usado pelo ícone de sino no header do sistema.
 */
class NotificationController extends BaseController
{
    private NotificationService $notificationService;

    public function __construct()
    {
        $this->notificationService = new NotificationService();
    }

    /**
     * GET /api/notifications
     * Lista notificações do usuário logado
     */
    public function index(): void
    {
        $this->requireAuthApi();

        try {
            $user = Auth::user();
            $limit = (int) ($_GET['limit'] ?? 20);
            $unreadOnly = filter_var($_GET['unread_only'] ?? false, FILTER_VALIDATE_BOOLEAN);

            $notifications = $this->notificationService->getUserNotifications(
                $user->id,
                min($limit, 50), // Máximo 50
                $unreadOnly
            );

            // Enriquecer com atributos computados
            $notifications = array_map(function ($notification) {
                $model = new Notification($notification);
                return array_merge($notification, [
                    'icon' => $model->icon,
                    'color' => $model->color,
                    'time_ago' => $this->timeAgo($notification['created_at']),
                ]);
            }, $notifications);

            Response::success([
                'notifications' => $notifications,
                'total' => count($notifications),
            ], 'Notificações listadas com sucesso');
        } catch (Exception $e) {
            error_log("[NotificationController] Erro ao listar: " . $e->getMessage());
            Response::error('Erro ao listar notificações: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/notifications/count
     * Conta notificações não lidas
     */
    public function count(): void
    {
        $this->requireAuthApi();

        try {
            $user = Auth::user();
            $count = $this->notificationService->getUnreadCount($user->id);

            Response::success([
                'unread_count' => $count,
            ]);
        } catch (Exception $e) {
            error_log("[NotificationController] Erro ao contar: " . $e->getMessage());
            Response::error('Erro ao contar notificações: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/notifications/{id}/read
     * Marca uma notificação como lida
     */
    public function markAsRead(int $id): void
    {
        $this->requireAuthApi();

        try {
            $user = Auth::user();
            $success = $this->notificationService->markAsRead($id, $user->id);

            if (!$success) {
                Response::error('Notificação não encontrada', 404);
                return;
            }

            Response::success(['marked' => true], 'Notificação marcada como lida');
        } catch (Exception $e) {
            error_log("[NotificationController] Erro ao marcar como lida: " . $e->getMessage());
            Response::error('Erro ao marcar notificação como lida: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/notifications/read-all
     * Marca todas as notificações como lidas
     */
    public function markAllAsRead(): void
    {
        $this->requireAuthApi();

        try {
            $user = Auth::user();
            $count = $this->notificationService->markAllAsRead($user->id);

            Response::success([
                'marked_count' => $count,
            ], 'Todas as notificações foram marcadas como lidas');
        } catch (Exception $e) {
            error_log("[NotificationController] Erro ao marcar todas: " . $e->getMessage());
            Response::error('Erro ao marcar notificações como lidas: ' . $e->getMessage(), 500);
        }
    }

    /**
     * DELETE /api/notifications/{id}
     * Deleta uma notificação
     */
    public function destroy(int $id): void
    {
        $this->requireAuthApi();

        try {
            $user = Auth::user();
            $success = $this->notificationService->deleteNotification($id, $user->id);

            if (!$success) {
                Response::error('Notificação não encontrada', 404);
                return;
            }

            Response::success(['deleted' => true], 'Notificação deletada com sucesso');
        } catch (Exception $e) {
            error_log("[NotificationController] Erro ao deletar: " . $e->getMessage());
            Response::error('Erro ao deletar notificação: ' . $e->getMessage(), 500);
        }
    }

    /**
     * DELETE /api/notifications/read
     * Deleta todas as notificações lidas
     */
    public function deleteRead(): void
    {
        $this->requireAuthApi();

        try {
            $user = Auth::user();
            $count = $this->notificationService->deleteReadNotifications($user->id);

            Response::success([
                'deleted_count' => $count,
            ], 'Notificações lidas foram deletadas');
        } catch (Exception $e) {
            error_log("[NotificationController] Erro ao deletar lidas: " . $e->getMessage());
            Response::error('Erro ao deletar notificações: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Formata tempo relativo (ex: "há 5 minutos")
     */
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
            return "há {$minutes} " . ($minutes === 1 ? 'minuto' : 'minutos');
        }

        if ($diff < 86400) {
            $hours = floor($diff / 3600);
            return "há {$hours} " . ($hours === 1 ? 'hora' : 'horas');
        }

        if ($diff < 604800) {
            $days = floor($diff / 86400);
            return "há {$days} " . ($days === 1 ? 'dia' : 'dias');
        }

        if ($diff < 2592000) {
            $weeks = floor($diff / 604800);
            return "há {$weeks} " . ($weeks === 1 ? 'semana' : 'semanas');
        }

        // Mais de um mês, mostrar data
        return date('d/m/Y', $time);
    }
}
