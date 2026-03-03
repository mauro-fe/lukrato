<?php

/**
 * 429 - Muitas Requisições (Rate Limit)
 * 
 * Variável opcional injetada pelo Router:
 * @var int|null $retryAfter  Tempo de espera em segundos
 */

$errorCode        = 429;
$errorTitle       = 'Muitas requisições';
$errorDescription = 'Você excedeu o limite de requisições permitidas. Aguarde um momento e tente novamente.';
$pageTitle        = '429 - Muitas Requisições | Lukrato';

// Ícone: Timer (Lucide timer)
$errorIconSvg = '<svg viewBox="0 0 24 24">
    <line x1="10" y1="2" x2="14" y2="2"/>
    <line x1="12" y1="14" x2="12" y2="8"/>
    <circle cx="12" cy="14" r="8"/>
</svg>';

$base = defined('BASE_URL') ? rtrim(BASE_URL, '/') . '/' : '/';

// ── Custom content: tempo de espera ──
$customContent = '';
if (!empty($retryAfter)) {
    $customContent = '<div class="lk-error-retry-info">'
        . '<span class="lk-error-estimate-dot"></span>'
        . 'Tente novamente em ~' . (int) $retryAfter . ' segundo' . ($retryAfter > 1 ? 's' : '')
        . '</div>';
}

// Ações
$errorActions = [
    ['label' => 'Tentar Novamente', 'href' => 'javascript:window.location.reload()', 'class' => 'lk-error-btn-primary'],
    ['label' => 'Voltar',           'href' => 'javascript:history.back()', 'class' => 'lk-error-btn-secondary'],
];

include __DIR__ . '/partials/error-layout.php';
