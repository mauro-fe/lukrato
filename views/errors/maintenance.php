<?php

/**
 * 503 - Em Manutenção
 * 
 * Variáveis injetadas por Application::showMaintenancePage():
 * @var string|null $reason           Motivo da manutenção
 * @var int|null    $estimatedMinutes Tempo estimado em minutos
 * @var string|null $activatedAt      Hora de ativação
 */

$errorCode        = 503;
$errorTitle       = 'Estamos melhorando o Lukrato';
$errorDescription = 'O sistema está passando por uma manutenção programada para trazer melhorias e mais estabilidade. Voltaremos em breve!';
$pageTitle        = 'Lukrato - Em Manutenção';

// Ícone: Chave inglesa (Lucide wrench)
$errorIconSvg = '<svg viewBox="0 0 24 24">
    <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
</svg>';

$base = defined('BASE_URL') ? rtrim(BASE_URL, '/') . '/' : '/';

// ── Custom content: reason, estimate, progress bar, logout ──
ob_start();

if (!empty($reason)): ?>
<div class="lk-error-reason">
    <strong>Motivo:</strong> <?= htmlspecialchars($reason, ENT_QUOTES, 'UTF-8') ?>
</div>
<?php endif;

if (!empty($estimatedMinutes)): ?>
<div class="lk-error-estimate">
    <span class="lk-error-estimate-dot"></span>
    Previsão de retorno: ~<?= (int) $estimatedMinutes ?> minuto<?= $estimatedMinutes > 1 ? 's' : '' ?>
</div>
<?php endif; ?>

<div class="lk-error-progress">
    <div class="lk-error-progress-fill"></div>
</div>

<?php
$customContent = ob_get_clean();

// Ações: logout + contato
$errorActions = [
    ['label' => 'Sair da conta', 'href' => $base . 'logout', 'class' => 'lk-error-btn-secondary'],
];

include __DIR__ . '/partials/error-layout.php';