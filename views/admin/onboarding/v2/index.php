<?php

/**
 * Onboarding V2 view shell.
 * All data shaping now comes ready from the controller/service layer.
 */
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="<?= htmlspecialchars((string) ($theme ?? 'dark'), ENT_QUOTES, 'UTF-8') ?>">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= htmlspecialchars((string) ($pageTitle ?? 'Lukrato - Bem-vindo'), ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="base-url"
        content="<?= htmlspecialchars((string) ($baseUrl ?? rtrim(BASE_URL, '/') . '/'), ENT_QUOTES, 'UTF-8') ?>">
    <meta name="theme-color" content="#e67e22">
    <?= csrf_meta('default') ?>
    <link rel="icon" type="image/png" href="<?= htmlspecialchars((string) ($faviconUrl ?? ''), ENT_QUOTES, 'UTF-8') ?>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link rel="stylesheet"
        href="<?= htmlspecialchars((string) ($baseUrl ?? ''), ENT_QUOTES, 'UTF-8') ?>assets/css/vendor/lucide-compat.css">
    <script src="<?= htmlspecialchars((string) ($baseUrl ?? ''), ENT_QUOTES, 'UTF-8') ?>assets/js/lucide.min.js">
    </script>

    <link rel="stylesheet"
        href="<?= htmlspecialchars((string) ($baseUrl ?? ''), ENT_QUOTES, 'UTF-8') ?>assets/css/core/variables.css">
    <link rel="stylesheet"
        href="<?= htmlspecialchars((string) ($baseUrl ?? ''), ENT_QUOTES, 'UTF-8') ?>assets/css/pages/onboarding-v2.css">

    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.2/dist/confetti.browser.min.js" defer></script>

    <script>
    window.__LK_CONFIG__ = <?= $globalConfigJson ?? '{}' ?>;
    window.__LK_CONFIG = window.__LK_CONFIG__;
    window.__LK_ONBOARDING_CONFIG__ = <?= $onboardingConfigJson ?? '{}' ?>;
    </script>
</head>

<body>
    <div id="onboardingRoot"></div>

    <?= function_exists('vite_scripts') ? vite_scripts('admin/onboarding/v2/app.js') : '' ?>
    <script src="<?= htmlspecialchars((string) ($baseUrl ?? ''), ENT_QUOTES, 'UTF-8') ?>assets/js/lucide-init.js">
    </script>
</body>

</html>
