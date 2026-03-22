<?php

declare(strict_types=1);

namespace Application\Services\Admin;

use Application\Models\Cupom;
use Application\Models\MessageCampaign;
use Application\Services\Communication\NotificationService;

class CommunicationAdminViewService
{
    public function __construct(
        private readonly NotificationService $notificationService = new NotificationService()
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function buildViewData(): array
    {
        $stats = $this->notificationService->getStats();

        return [
            'pageTitle' => 'Comunicações - SysAdmin',
            'subTitle' => 'Envie mensagens e notificações para seus usuários',
            'skipPlanLimits' => true,
            'stats' => $stats,
            'typeOptions' => MessageCampaign::getTypes(),
            'planOptions' => MessageCampaign::getPlanOptions(),
            'statusOptions' => MessageCampaign::getStatusOptions(),
            'inactiveDaysOptions' => MessageCampaign::getInactiveDaysOptions(),
            'cuponsAtivos' => Cupom::where('ativo', true)
                ->where(function ($q) {
                    $q->whereNull('valido_ate')
                        ->orWhere('valido_ate', '>', now());
                })
                ->orderBy('codigo')
                ->get(),
        ];
    }
}
