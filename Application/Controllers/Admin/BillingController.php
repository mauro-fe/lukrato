<?php

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;
use Application\Lib\Auth;
use Application\Models\Plano;
use Application\Services\SubscriptionExpirationService;

class BillingController extends BaseController
{
    public function index(): void
    {
        $this->requireAuth();
        $user = Auth::user();
        $plans = Plano::query()
            ->where('ativo', true)
            ->orderBy('preco_centavos')
            ->orderBy('id')
            ->get();
        $currentPlan = $user?->planoAtual();
        $assinatura = $user?->assinaturaAtiva()->first();

        // Obter status completo da assinatura (inclui período de carência)
        $subscriptionStatus = SubscriptionExpirationService::getSubscriptionStatus($assinatura);

        $this->render(
            'admin/billing/index',
            [
                'user' => $user,
                'plans' => $plans,
                'currentPlanCode' => $currentPlan?->code,
                'assinatura' => $assinatura,
                // Status detalhado da assinatura
                'subscriptionStatus' => $subscriptionStatus,
                // Atalhos para compatibilidade com view existente
                'isCanceled' => $subscriptionStatus['is_canceled'],
                'isInGrace' => $subscriptionStatus['is_in_grace'],
                'isExpired' => $subscriptionStatus['is_expired'],
                'accessUntil' => $subscriptionStatus['access_until'],
                'graceDaysRemaining' => $subscriptionStatus['grace_days_remaining'],
                'graceHoursRemaining' => $subscriptionStatus['grace_hours_remaining'],
                'shouldShowRenew' => $subscriptionStatus['should_show_renew'],
                'alertMessage' => $subscriptionStatus['alert_message'],
                'statusLabel' => $subscriptionStatus['status_label'],
                'statusColor' => $subscriptionStatus['status_color'],
                'actionLabel' => $subscriptionStatus['action_label'],
                'pageTitle' => 'Assinar Pro',
                'subTitle' => 'Assine o pro e tenha acesso a todas as funcionalidades'
            ],
            'admin/partials/header',
            'admin/partials/footer',
        );
    }
}
