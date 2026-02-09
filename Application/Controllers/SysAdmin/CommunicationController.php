<?php

declare(strict_types=1);

namespace Application\Controllers\SysAdmin;

use Application\Controllers\BaseController;
use Application\Lib\Auth;
use Application\Models\MessageCampaign;
use Application\Services\NotificationService;

/**
 * CommunicationController
 * 
 * Controller de view para a página de Comunicações no sysadmin.
 * Renderiza a interface de criação e histórico de campanhas.
 */
class CommunicationController extends BaseController
{
    public function index(): void
    {
        $this->requireAuth();
        $user = Auth::user();

        // Verificar se é admin
        if (!$user || $user->is_admin != 1) {
            $this->redirect('login');
            return;
        }

        // Obter estatísticas iniciais
        $notificationService = new NotificationService();
        $stats = $notificationService->getStats();

        // Opções para os selects
        $typeOptions = MessageCampaign::getTypes();
        $planOptions = MessageCampaign::getPlanOptions();
        $statusOptions = MessageCampaign::getStatusOptions();
        $inactiveDaysOptions = MessageCampaign::getInactiveDaysOptions();

        $this->render(
            'sysAdmin/communications',
            [
                'pageTitle' => 'Comunicações - SysAdmin',
                'subTitle' => 'Envie mensagens e notificações para seus usuários',
                'skipPlanLimits' => true,
                'stats' => $stats,
                'typeOptions' => $typeOptions,
                'planOptions' => $planOptions,
                'statusOptions' => $statusOptions,
                'inactiveDaysOptions' => $inactiveDaysOptions,
            ],
            'admin/partials/header',
            'admin/partials/footer'
        );
    }
}
