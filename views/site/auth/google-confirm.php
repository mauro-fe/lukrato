<?php

/**
 * Página de confirmação para criar conta via Google.
 */
$siteBaseUrl = rtrim(BASE_URL, '/');
$favicon = $siteBaseUrl . '/assets/img/icone.png?v=1';
$googleLoginUrl = $googleLoginUrl ?? $siteBaseUrl . '/login';
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= $favicon ?>">
    <link rel="shortcut icon" type="image/png" sizes="32x32" href="<?= $favicon ?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base-url" content="<?= htmlspecialchars($siteBaseUrl . '/', ENT_QUOTES, 'UTF-8') ?>">
    <meta name="api-base-url" content="<?= htmlspecialchars($siteBaseUrl . '/', ENT_QUOTES, 'UTF-8') ?>">
    <title>Confirmar Cadastro - <?= $_ENV['APP_NAME'] ?? 'Lukrato' ?></title>
    <script>
        window.APP_BASE_URL = <?= json_encode($siteBaseUrl, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        window.API_BASE_URL = <?= json_encode($siteBaseUrl, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    </script>
    <?= function_exists('vite_css') ? vite_css('site-google-confirm') : '' ?>
</head>

<body>
    <div
        class="confirm-container"
        data-google-confirm-root
        data-login-url="<?= htmlspecialchars($googleLoginUrl, ENT_QUOTES, 'UTF-8') ?>">
        <img src="<?= BASE_URL ?>assets/img/logo.png" alt="Lukrato" class="logo w-100">

        <h1>Criar sua conta</h1>
        <p class="subtitle">Você está a um passo de começar!</p>

        <div class="user-info">
            <div class="user-avatar user-avatar-placeholder" data-google-user-avatar-fallback>
                G
            </div>
            <img src="" alt="Foto" class="user-avatar" data-google-user-avatar hidden>
            <div class="user-name" data-google-user-name>Carregando seus dados...</div>
            <div class="user-email" data-google-user-email>Aguarde um instante.</div>
        </div>

        <div class="info-text">
            <strong>Não encontramos uma conta com este email.</strong><br>
            Ao continuar, criaremos uma nova conta para você com os dados acima.
        </div>

        <p class="confirm-status" data-google-confirm-status hidden></p>

        <div class="buttons">
            <button type="button" class="btn btn-cancel" data-google-action="cancel">
                Cancelar
            </button>
            <button type="button" class="btn btn-confirm" data-google-action="confirm">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                Criar Conta
            </button>
        </div>

        <p class="terms">
            Ao criar sua conta, você concorda com nossos<br>
            <a href="<?= BASE_URL ?>termos">Termos de Uso</a> e <a href="<?= BASE_URL ?>privacidade">Política de
                Privacidade</a>
        </p>
    </div>
    <?= function_exists('vite_scripts') ? vite_scripts('site/auth/google-confirm/index.js') : '' ?>
</body>

</html>