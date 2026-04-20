<?php

/**
 * Error Layout Template
 * 
 * Layout compartilhado para todas as páginas de erro.
 * Detecta autenticação e renderiza dentro do app shell (logado) ou standalone (visitante).
 * Respeita data-theme (light/dark) do design system.
 *
 * Variáveis esperadas (definidas pela página de erro antes de incluir este arquivo):
 * @var int    $errorCode         HTTP status code (404, 500, 403, 429, 503)
 * @var string $errorTitle        Título do erro
 * @var string $errorDescription  Descrição do erro
 * @var string $errorIconSvg      SVG inline para o ícone
 * @var string $pageTitle         Título da página (<title>)
 * @var string $customContent     HTML adicional (barra de progresso da manutenção, etc.)
 * @var array  $errorActions      Botões: [{label, href, class, onclick?}] — null para auto-detectar
 */

use Application\Lib\Auth;

// ── Defaults ──
$errorCode        = $errorCode        ?? 500;
$errorTitle       = $errorTitle       ?? 'Erro';
$errorDescription = $errorDescription ?? '';
$errorIconSvg     = $errorIconSvg     ?? '';
$pageTitle        = $pageTitle        ?? ($errorCode . ' - Lukrato');
$customContent    = $customContent    ?? '';

// ── Auth detection ──
$_errorIsLoggedIn  = false;
$_errorCurrentUser = null;
$_errorUserTheme   = 'dark';

try {
    if (class_exists(Auth::class) && Auth::isLoggedIn()) {
        $_errorIsLoggedIn  = true;
        $_errorCurrentUser = Auth::user();
        if ($_errorCurrentUser && isset($_errorCurrentUser->theme_preference)) {
            $_errorUserTheme = in_array($_errorCurrentUser->theme_preference, ['light', 'dark'])
                ? $_errorCurrentUser->theme_preference
                : 'dark';
        }
    }
} catch (\Throwable $e) {
    // Auth check failed — stay in standalone mode
}

$base = defined('BASE_URL') ? rtrim(BASE_URL, '/') . '/' : '/';
$_errorStandalone = !$_errorIsLoggedIn;

// ── Default actions based on auth state ──
if (!isset($errorActions)) {
    if ($_errorIsLoggedIn) {
        $errorActions = [
            ['label' => 'Voltar ao Dashboard', 'href' => $base . 'dashboard', 'class' => 'lk-error-btn-primary'],
            ['label' => 'Voltar',              'href' => 'javascript:history.back()', 'class' => 'lk-error-btn-secondary'],
        ];
    } else {
        $errorActions = [
            ['label' => 'Página Inicial', 'href' => $base, 'class' => 'lk-error-btn-primary'],
            ['label' => 'Voltar',         'href' => 'javascript:history.back()', 'class' => 'lk-error-btn-secondary'],
        ];
    }
}

// ── In-app mode: include header (sidebar, navbar, etc.) ──
if (!$_errorStandalone) {
    $userTheme      = $_errorUserTheme;
    $username       = $_errorCurrentUser?->nome ?? 'usuario';
    $menu           = '';
    $pageTitle      = $pageTitle ?? ($errorCode . ' - Lukrato');
    $isSysAdmin     = ((int) ($_errorCurrentUser?->is_admin ?? 0)) === 1;
    $showUpgradeCTA = !($_errorCurrentUser && method_exists($_errorCurrentUser, 'isPro') && $_errorCurrentUser->isPro());

    ob_start();
    try {
        include BASE_PATH . '/views/admin/partials/header.php';
        ob_end_flush();
    } catch (\Throwable $e) {
        ob_end_clean();
        $_errorStandalone = true;
    }
}

// ── Standalone mode: render HTML shell ──
if ($_errorStandalone): ?>
    <!DOCTYPE html>
    <html lang="pt-BR" data-theme="<?= htmlspecialchars($_errorUserTheme, ENT_QUOTES, 'UTF-8') ?>">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="robots" content="noindex, nofollow">
        <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
        <link rel="icon" type="image/png" sizes="32x32" href="<?= $base ?>assets/img/icone.png">
        <script>
            (function() {
                try {
                    var savedTheme = localStorage.getItem('lukrato-theme');
                    if (savedTheme === 'light' || savedTheme === 'dark') {
                        document.documentElement.setAttribute('data-theme', savedTheme);
                    }
                } catch (error) {}
            })();
        </script>
        <?= function_exists('vite_css') ? vite_css('error-page') : '' ?>
    </head>

    <body>
    <?php else: ?>
        <?= function_exists('vite_css') ? vite_css('error-page') : '' ?>
    <?php endif; ?>

    <!-- ── Error Content ── -->
    <div class="lk-error-page <?= $_errorStandalone ? 'lk-error-standalone' : 'lk-error-inapp' ?>">

        <?php if ($_errorStandalone): ?>
            <!-- Background particles -->
            <div class="lk-error-particles" aria-hidden="true">
                <?php for ($i = 0; $i < 15; $i++): ?>
                    <div class="lk-error-particle" style="left:<?= rand(0, 100) ?>%;animation-delay:<?= rand(0, 15) ?>s;animation-duration:<?= rand(12, 22) ?>s;"></div>
                <?php endfor; ?>
            </div>
            <!-- Background decorations -->
            <div class="lk-error-decoration lk-error-decoration-1" aria-hidden="true"><?= $errorCode ?></div>
            <div class="lk-error-decoration lk-error-decoration-2" aria-hidden="true"><?= $errorCode ?></div>
        <?php endif; ?>

        <div class="lk-error-content" role="main" aria-labelledby="lk-error-title">

            <div class="lk-error-brand">
                <img src="<?= $base ?>assets/img/logo-top.png" alt="Lukrato" class="lk-error-brand-logo">
            </div>

            <p class="lk-error-kicker"><?= $_errorStandalone ? 'Status do sistema' : 'Erro do aplicativo' ?></p>

            <?php if ($errorIconSvg): ?>
                <div class="lk-error-icon" aria-hidden="true">
                    <?= $errorIconSvg ?>
                </div>
            <?php endif; ?>

            <h1 class="lk-error-code"><?= (int) $errorCode ?></h1>
            <h2 class="lk-error-title" id="lk-error-title"><?= htmlspecialchars($errorTitle, ENT_QUOTES, 'UTF-8') ?></h2>

            <?php if ($errorDescription): ?>
                <p class="lk-error-description"><?= htmlspecialchars($errorDescription, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>

            <?php if ($customContent): ?>
                <?= $customContent ?>
            <?php endif; ?>

            <?php if (!empty($errorActions)): ?>
                <div class="lk-error-actions">
                    <?php foreach ($errorActions as $action): ?>
                        <a href="<?= htmlspecialchars($action['href'], ENT_QUOTES, 'UTF-8') ?>"
                            class="lk-error-btn <?= htmlspecialchars($action['class'] ?? 'lk-error-btn-secondary', ENT_QUOTES, 'UTF-8') ?>"
                            <?= isset($action['onclick']) ? 'onclick="' . htmlspecialchars($action['onclick'], ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
                            <?= htmlspecialchars($action['label'], ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($_errorStandalone): ?>
                <p class="lk-error-footer">
                    Lukrato &mdash; Suas finanças sob controle
                </p>
            <?php endif; ?>

        </div>
    </div>

    <?php if ($_errorStandalone): ?>
    </body>

    </html>
<?php else: ?>
    <?php include BASE_PATH . '/views/admin/partials/footer.php'; ?>
<?php endif; ?>