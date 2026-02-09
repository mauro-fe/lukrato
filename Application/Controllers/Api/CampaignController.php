<?php

declare(strict_types=1);

namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Lib\Auth;
use Application\Models\MessageCampaign;
use Application\Services\NotificationService;
use Exception;

/**
 * CampaignController
 * 
 * API para gerenciamento de campanhas de mensagens pelo sysadmin.
 * Endpoints protegidos apenas para administradores.
 */
class CampaignController extends BaseController
{
    private NotificationService $notificationService;

    public function __construct()
    {
        $this->notificationService = new NotificationService();
    }

    /**
     * Verifica se o usuário atual é admin
     */
    private function requireAdmin(): ?object
    {
        $user = Auth::user();

        if (!$user || $user->is_admin != 1) {
            Response::error('Acesso negado. Apenas administradores podem acessar este recurso.', 403);
            return null;
        }

        return $user;
    }

    /**
     * GET /api/campaigns
     * Lista campanhas com paginação
     */
    public function index(): void
    {
        $this->requireAuthApi();

        if (!$this->requireAdmin()) {
            return;
        }

        try {
            $page = (int) ($_GET['page'] ?? 1);
            $perPage = (int) ($_GET['per_page'] ?? 10);

            $result = $this->notificationService->listCampaigns($page, $perPage);

            Response::success($result, 'Campanhas listadas com sucesso');
        } catch (Exception $e) {
            error_log("[CampaignController] Erro ao listar campanhas: " . $e->getMessage());
            Response::error('Erro ao listar campanhas: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/campaigns
     * Cria e envia uma nova campanha
     */
    public function store(): void
    {
        $this->requireAuthApi();

        $admin = $this->requireAdmin();
        if (!$admin) {
            return;
        }

        try {
            $payload = $this->getRequestPayload();

            // Validações
            $title = trim($payload['title'] ?? '');
            $message = trim($payload['message'] ?? '');

            if (empty($title)) {
                Response::error('O título é obrigatório', 400);
                return;
            }

            if (empty($message)) {
                Response::error('A mensagem é obrigatória', 400);
                return;
            }

            if (strlen($title) > 255) {
                Response::error('O título deve ter no máximo 255 caracteres', 400);
                return;
            }

            // Parâmetros da campanha
            $type = $payload['type'] ?? MessageCampaign::TYPE_INFO;
            $sendNotification = (bool) ($payload['send_notification'] ?? true);
            $sendEmail = (bool) ($payload['send_email'] ?? false);
            $link = !empty($payload['link']) ? trim($payload['link']) : null;
            $linkText = !empty($payload['link_text']) ? trim($payload['link_text']) : null;

            // Filtros
            $filters = [
                'plan' => $payload['filters']['plan'] ?? 'all',
                'status' => $payload['filters']['status'] ?? 'all',
                'days_inactive' => !empty($payload['filters']['days_inactive'])
                    ? (int) $payload['filters']['days_inactive']
                    : null,
                'email_verified' => isset($payload['filters']['email_verified'])
                    ? (bool) $payload['filters']['email_verified']
                    : null,
            ];

            // Validar que pelo menos um canal está selecionado
            if (!$sendNotification && !$sendEmail) {
                Response::error('Selecione pelo menos um canal de envio (notificação ou e-mail)', 400);
                return;
            }

            // Validar tipo
            $validTypes = array_keys(MessageCampaign::getTypes());
            if (!in_array($type, $validTypes)) {
                Response::error('Tipo de campanha inválido', 400);
                return;
            }

            // Criar e enviar campanha
            $campaign = $this->notificationService->sendCampaign(
                $admin->id,
                $title,
                $message,
                $type,
                $filters,
                $sendNotification,
                $sendEmail,
                $link,
                $linkText
            );

            error_log("📢 [CAMPAIGN] Campanha #{$campaign->id} enviada por {$admin->nome} (ID: {$admin->id}) - {$campaign->total_recipients} destinatários");

            Response::success([
                'campaign_id' => $campaign->id,
                'title' => $campaign->title,
                'total_recipients' => $campaign->total_recipients,
                'emails_sent' => $campaign->emails_sent,
                'emails_failed' => $campaign->emails_failed,
                'status' => $campaign->status,
            ], 'Campanha enviada com sucesso');
        } catch (Exception $e) {
            error_log("[CampaignController] Erro ao criar campanha: " . $e->getMessage());
            Response::error('Erro ao enviar campanha: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/campaigns/preview
     * Preview: conta quantos usuários serão impactados pelos filtros
     */
    public function preview(): void
    {
        $this->requireAuthApi();

        if (!$this->requireAdmin()) {
            return;
        }

        try {
            // Filtros da query string
            $filters = [
                'plan' => $_GET['plan'] ?? 'all',
                'status' => $_GET['status'] ?? 'all',
                'days_inactive' => !empty($_GET['days_inactive'])
                    ? (int) $_GET['days_inactive']
                    : null,
                'email_verified' => isset($_GET['email_verified'])
                    ? filter_var($_GET['email_verified'], FILTER_VALIDATE_BOOLEAN)
                    : null,
            ];

            $count = $this->notificationService->countUsersByFilters($filters);

            Response::success([
                'count' => $count,
                'filters' => $filters,
            ], 'Preview gerado com sucesso');
        } catch (Exception $e) {
            error_log("[CampaignController] Erro no preview: " . $e->getMessage());
            Response::error('Erro ao gerar preview: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/campaigns/stats
     * Estatísticas gerais de campanhas e notificações
     */
    public function stats(): void
    {
        $this->requireAuthApi();

        if (!$this->requireAdmin()) {
            return;
        }

        try {
            $stats = $this->notificationService->getStats();

            Response::success($stats, 'Estatísticas obtidas com sucesso');
        } catch (Exception $e) {
            error_log("[CampaignController] Erro ao obter stats: " . $e->getMessage());
            Response::error('Erro ao obter estatísticas: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/campaigns/options
     * Retorna opções para os selects do formulário
     */
    public function options(): void
    {
        $this->requireAuthApi();

        if (!$this->requireAdmin()) {
            return;
        }

        Response::success([
            'types' => MessageCampaign::getTypes(),
            'plans' => MessageCampaign::getPlanOptions(),
            'statuses' => MessageCampaign::getStatusOptions(),
            'inactive_days' => MessageCampaign::getInactiveDaysOptions(),
        ], 'Opções obtidas com sucesso');
    }

    /**
     * GET /api/campaigns/{id}
     * Detalhes de uma campanha específica
     */
    public function show(int $id): void
    {
        $this->requireAuthApi();

        if (!$this->requireAdmin()) {
            return;
        }

        try {
            $campaign = MessageCampaign::with('creator:id,nome,email')->find($id);

            if (!$campaign) {
                Response::error('Campanha não encontrada', 404);
                return;
            }

            Response::success([
                'id' => $campaign->id,
                'title' => $campaign->title,
                'message' => $campaign->message,
                'link' => $campaign->link,
                'link_text' => $campaign->link_text,
                'type' => $campaign->type,
                'icon' => $campaign->icon,
                'color' => $campaign->color,
                'filters' => $campaign->filters,
                'filters_description' => $campaign->filters_description,
                'send_notification' => $campaign->send_notification,
                'send_email' => $campaign->send_email,
                'channels_description' => $campaign->channels_description,
                'total_recipients' => $campaign->total_recipients,
                'notifications_read' => $campaign->notifications_read,
                'read_rate' => $campaign->read_rate,
                'emails_sent' => $campaign->emails_sent,
                'emails_failed' => $campaign->emails_failed,
                'email_success_rate' => $campaign->email_success_rate,
                'status' => $campaign->status,
                'status_badge' => $campaign->status_badge,
                'creator' => [
                    'id' => $campaign->creator->id ?? null,
                    'nome' => $campaign->creator->nome ?? 'Sistema',
                    'email' => $campaign->creator->email ?? null,
                ],
                'sent_at' => $campaign->sent_at?->format('d/m/Y H:i'),
                'created_at' => $campaign->created_at->format('d/m/Y H:i'),
            ], 'Campanha obtida com sucesso');
        } catch (Exception $e) {
            error_log("[CampaignController] Erro ao obter campanha: " . $e->getMessage());
            Response::error('Erro ao obter campanha: ' . $e->getMessage(), 500);
        }
    }

    // =========================================================================
    // ANIVERSÁRIOS
    // =========================================================================

    /**
     * GET /api/campaigns/birthdays
     * Lista aniversariantes de hoje e dos próximos dias
     */
    public function birthdays(): void
    {
        $this->requireAuthApi();

        if (!$this->requireAdmin()) {
            return;
        }

        try {
            $days = (int) ($_GET['days'] ?? 7);
            $days = min(max($days, 1), 30); // Entre 1 e 30 dias

            $today = $this->notificationService->getBirthdayUsers();
            $upcoming = $this->notificationService->getUpcomingBirthdays($days);

            Response::success([
                'today' => $today,
                'today_count' => count($today),
                'upcoming' => $upcoming,
                'upcoming_count' => count($upcoming),
                'days_range' => $days,
            ], 'Aniversariantes obtidos com sucesso');
        } catch (Exception $e) {
            error_log("[CampaignController] Erro ao obter aniversariantes: " . $e->getMessage());
            Response::error('Erro ao obter aniversariantes: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/campaigns/birthdays/send
     * Dispara notificações de aniversário manualmente
     */
    public function sendBirthdays(): void
    {
        $this->requireAuthApi();

        if (!$this->requireAdmin()) {
            return;
        }

        try {
            $payload = $this->getRequestPayload();
            $sendEmail = (bool) ($payload['send_email'] ?? true);

            $result = $this->notificationService->processBirthdayNotifications($sendEmail);

            error_log("🎂 [BIRTHDAY-MANUAL] Admin disparou notificações de aniversário - {$result['notifications_sent']} enviadas");

            Response::success($result, 'Notificações de aniversário processadas');
        } catch (Exception $e) {
            error_log("[CampaignController] Erro ao enviar aniversários: " . $e->getMessage());
            Response::error('Erro ao enviar notificações de aniversário: ' . $e->getMessage(), 500);
        }
    }
}
