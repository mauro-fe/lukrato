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
];

foreach ($apiRouteFiles as $routeFile) {
    if (!file_exists($routeFile)) {
        throw new \RuntimeException("API route file not found: {$routeFile}");
    }

    require_once $routeFile;
}
