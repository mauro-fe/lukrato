<?php

/**
 * Página de confirmação para criar conta via Google.
 */
$siteBaseUrl = rtrim(BASE_URL, '/');
$favicon = $siteBaseUrl . '/assets/img/icone.png?v=1';
$googleLoginUrl = $googleLoginUrl ?? $siteBaseUrl . '/login';
$googlePendingUrl = $googlePendingUrl ?? $siteBaseUrl . '/api/v1/auth/google/pending';
$googleConfirmUrl = $googleConfirmUrl ?? $siteBaseUrl . '/api/v1/auth/google/confirm';
$googleCancelUrl = $googleCancelUrl ?? $siteBaseUrl . '/api/v1/auth/google/cancel';
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="dark">

<head>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= $favicon ?>">
    <link rel="shortcut icon" type="image/png" sizes="32x32" href="<?= $favicon ?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base-url" content="<?= htmlspecialchars($siteBaseUrl . '/', ENT_QUOTES, 'UTF-8') ?>">
    <meta name="api-base-url" content="<?= htmlspecialchars($siteBaseUrl . '/', ENT_QUOTES, 'UTF-8') ?>">
    <title>Confirmar Cadastro - <?= $_ENV['APP_NAME'] ?? 'Lukrato' ?></title>
    <script>
        (function() {
            try {
                var savedTheme = localStorage.getItem('lukrato-theme');
                var normalizedTheme = savedTheme === 'light' ? 'light' : 'dark';
                document.documentElement.setAttribute('data-theme', normalizedTheme);
            } catch (error) {
                document.documentElement.setAttribute('data-theme', 'dark');
            }
        })();
    </script>
    <script>
        window.APP_BASE_URL = <?= json_encode($siteBaseUrl, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        window.API_BASE_URL = <?= json_encode($siteBaseUrl, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    </script>
    <?= function_exists('vite_css') ? vite_css('site-google-confirm') : '' ?>
</head>

<body>
    <main class="confirm-page">
        <div
            class="confirm-container"
            data-google-confirm-root
            data-login-url="<?= htmlspecialchars($googleLoginUrl, ENT_QUOTES, 'UTF-8') ?>"
            data-pending-url="<?= htmlspecialchars($googlePendingUrl, ENT_QUOTES, 'UTF-8') ?>"
            data-confirm-url="<?= htmlspecialchars($googleConfirmUrl, ENT_QUOTES, 'UTF-8') ?>"
            data-cancel-url="<?= htmlspecialchars($googleCancelUrl, ENT_QUOTES, 'UTF-8') ?>">
            <div class="confirm-brand">
                <img src="<?= BASE_URL ?>assets/img/logo-top.png" alt="Lukrato" class="logo">
            </div>

            <p class="eyebrow">Cadastro com Google</p>
            <h1>Criar sua conta</h1>
            <p class="subtitle">Confirme os dados recebidos do Google para concluir seu acesso com segurança.</p>

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
                Ao continuar, criaremos uma nova conta para você usando os dados confirmados acima.
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
                    Criar conta
                </button>
            </div>

            <p class="terms">
                Ao criar sua conta, você concorda com nossos<br>
                <a href="<?= BASE_URL ?>termos">Termos de Uso</a> e <a href="<?= BASE_URL ?>privacidade">Política de Privacidade</a>
            </p>
        </div>
    </main>
    <?= function_exists('vite_scripts') ? vite_scripts('site/auth/google-confirm/index.js') : '' ?>
</body>

</html>
