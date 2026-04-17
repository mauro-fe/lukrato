<?php

declare(strict_types=1);

/**
 * API routes split by domain for easier maintenance.
 * Keep load order stable to preserve route matching behavior.
 */
$apiRouteFiles = [
    __DIR__ . '/api/01_user_access.php',
    __DIR__ . '/api/02_dashboard_reports.php',
    __DIR__ . '/api/03_lancamentos_transactions.php',
    __DIR__ . '/api/04_contas_categorias_cartoes.php',
    __DIR__ . '/api/05_gamification_financas.php',
    __DIR__ . '/api/06_notificacoes_userprefs.php',
    __DIR__ . '/api/07_premium_cupons.php',
    __DIR__ . '/api/08_faturas_parcelamentos.php',
    __DIR__ . '/api/09_sysadmin_core_blog.php',
    __DIR__ . '/api/10_ai.php',
    __DIR__ . '/api/11_campaigns_notifications.php',
    __DIR__ . '/api/12_plan_referral_feedback.php',
    __DIR__ . '/api/13_frontend_pilot_v1.php',
    __DIR__ . '/api/14_financas_shared_v1.php',
    __DIR__ . '/api/15_faturas_parcelamentos_v1.php',
    __DIR__ . '/api/16_lancamentos_transactions_v1.php',
    __DIR__ . '/api/17_reports_gamification_v1.php',
    __DIR__ . '/api/18_engagement_billing_dashboard_v1.php',
    __DIR__ . '/api/19_sysadmin_adminops_v1.php',
    __DIR__ . '/api/20_finance_dashboard_ai_v1.php',
    __DIR__ . '/api/21_auth_v1.php',
    __DIR__ . '/api/22_integrations_v1.php',
    __DIR__ . '/api/23_remaining_legacy_v1.php',
];

foreach ($apiRouteFiles as $routeFile) {
    if (!file_exists($routeFile)) {
        throw new \RuntimeException("API route file not found: {$routeFile}");
    }

    require $routeFile;
}
