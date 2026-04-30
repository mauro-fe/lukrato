<?php
$siteBaseUrl = rtrim(BASE_URL, '/');
$favicon = $siteBaseUrl . '/assets/img/icone.png?v=1';
$logoUrl = $siteBaseUrl . '/assets/img/logo-top.png';
$forgotPasswordUrl = $forgotPasswordUrl ?? $siteBaseUrl . '/recuperar-senha';
$loginUrl = $loginUrl ?? $siteBaseUrl . '/login';
$resetValidateEndpoint = $resetValidateEndpoint ?? $siteBaseUrl . '/api/v1/auth/password/reset/validate';
$resetSubmitEndpoint = $resetSubmitEndpoint ?? $siteBaseUrl . '/api/v1/auth/password/reset';
$currentFormAction = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="dark">

<head>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= $favicon ?>">
    <link rel="shortcut icon" type="image/png" sizes="32x32" href="<?= $favicon ?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base-url" content="<?= $siteBaseUrl . '/' ?>">

    <!-- CSRF Meta Tags para renovação automática -->
    <?= csrf_meta('reset_form') ?>

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

    <title>Redefinir Senha - Lukrato</title>
    <!-- Lucide Icons + FA Brands -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/brands.min.css">
    <script src="<?= $siteBaseUrl ?>/assets/js/lucide.min.js"></script>
    <?= function_exists('vite_css') ? vite_css('auth-shared-style') : '' ?>
</head>

<body>
    <div class="particles" id="particles"></div>

    <main
        class="lukrato-auth"
        data-reset-password-root
        data-forgot-password-url="<?= htmlspecialchars($forgotPasswordUrl, ENT_QUOTES, 'UTF-8') ?>"
        data-login-url="<?= htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') ?>"
        data-reset-validate-endpoint="<?= htmlspecialchars($resetValidateEndpoint, ENT_QUOTES, 'UTF-8') ?>"
        data-reset-submit-endpoint="<?= htmlspecialchars($resetSubmitEndpoint, ENT_QUOTES, 'UTF-8') ?>">
        <div class="login-wrapper">
            <section class="login-left">
                <div class="brand">
                    <div class="imagem-logo">
                        <img src="<?= htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Lukrato">
                    </div>
                </div>

                <header class="welcome">
                    <h2>Definir nova senha</h2>
                    <p>Escolha uma senha forte e memorável para manter sua conta segura.</p>
                </header>
            </section>

            <section class="login-right">
                <div class="card">
                    <div class="card-panel-head">
                        <p class="card-kicker">Atualização de acesso</p>
                        <h3 class="card-title">Crie uma nova senha</h3>
                        <p class="card-subtitle">Escolha uma senha forte para proteger sua conta e concluir a recuperação.</p>
                    </div>

                    <div id="messageContainer" class="card-status">Validando seu link de redefinição...</div>

                    <form action="<?= htmlspecialchars($currentFormAction, ENT_QUOTES, 'UTF-8') ?>" method="POST" novalidate id="resetForm">
                        <?= csrf_input('reset_form') ?>
                        <input type="hidden" name="token" value="" data-reset-token>
                        <input type="hidden" name="selector" value="" data-reset-selector>
                        <input type="hidden" name="validator" value="" data-reset-validator>
                        <div class="field">
                            <input type="password" name="password" id="password"
                                placeholder="Nova senha (mínimo 8 caracteres)" required minlength="8"
                                aria-label="Nova senha" autocomplete="new-password">
                            <button type="button" class="toggle-password" data-target="password">
                                <i data-lucide="eye"></i>
                            </button>
                            <div class="password-strength" id="strengthBar">
                                <div class="password-strength-bar"></div>
                            </div>
                            <div class="password-hint">
                                <i data-lucide="info"></i>
                                <span>Use letras, números e símbolos para uma senha forte</span>
                            </div>
                        </div>

                        <div class="field">
                            <input type="password" name="password_confirmation" id="password_confirmation"
                                placeholder="Confirmar nova senha" required minlength="8"
                                aria-label="Confirmar nova senha" autocomplete="new-password">
                            <button type="button" class="toggle-password" data-target="password_confirmation">
                                <i data-lucide="eye"></i>
                            </button>
                        </div>

                        <button type="submit" class="btn-primary" id="submitBtn" disabled>
                            <span>Redefinir senha</span>
                        </button>

                        <p class="extra-link">
                            <a href="<?= htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') ?>"> <i data-lucide="arrow-left"></i>
                                Voltar para o login</a>
                        </p>
                    </form>
                </div>
            </section>
        </div>
    </main>

    <?php loadPageJs('admin-auth-reset-password'); ?>
</body>

</html>
