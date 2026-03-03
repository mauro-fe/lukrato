<?php

/**
 * 500 - Erro Interno do Servidor
 */

// Log da exceção se disponível
if (isset($exception)) {
    error_log("Erro 500: " . $exception->getMessage());
}

$errorCode        = 500;
$errorTitle       = 'Erro interno do servidor';
$errorDescription = 'Desculpe! Algo deu errado em nossos servidores. Nossa equipe já foi notificada e está trabalhando para resolver o problema.';
$pageTitle        = '500 - Erro Interno | Lukrato';

// Ícone: Triângulo de alerta (Lucide alert-triangle)
$errorIconSvg = '<svg viewBox="0 0 24 24">
    <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
    <line x1="12" y1="9" x2="12" y2="13"/>
    <line x1="12" y1="17" x2="12.01" y2="17"/>
</svg>';

// Ações: Página Inicial + Tentar Novamente
$base = defined('BASE_URL') ? rtrim(BASE_URL, '/') . '/' : '/';
$errorActions = [
    ['label' => 'Página Inicial',   'href' => $base, 'class' => 'lk-error-btn-primary'],
    ['label' => 'Tentar Novamente', 'href' => 'javascript:window.location.reload()', 'class' => 'lk-error-btn-secondary'],
];

include __DIR__ . '/partials/error-layout.php';