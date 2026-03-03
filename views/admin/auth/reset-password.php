<?php
$favicon        = rtrim(BASE_URL, '/') . '/assets/img/icone.png?v=1'; ?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= $favicon ?>">
    <link rel="shortcut icon" type="image/png" sizes="32x32" href="<?= $favicon ?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base-url" content="<?= rtrim(BASE_URL, '/') . '/' ?>">

    <!-- CSRF Meta Tags para renovação automática -->
    <?= csrf_meta('reset_form') ?>

    <title>Redefinir Senha - Lukrato</title>
    <!-- Lucide Icons + FA Brands -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/brands.min.css">
    <link rel="stylesheet" href="<?= rtrim(BASE_URL, '/') ?>/assets/css/vendor/lucide-compat.css">
    <script src="<?= rtrim(BASE_URL, '/') ?>/assets/js/lucide.min.js"></script>
    <?php loadPageCss('auth-shared'); ?>
</head>

<body>
    <div class="particles" id="particles"></div>

    <main class="lukrato-auth">
        <div class="login-wrapper">
            <section class="login-left">
                <div class="brand">
                    <div class="imagem-logo">
                        <img src="<?= BASE_URL ?>assets/img/logo.png" alt="Lukrato">
                    </div>
                </div>

                <header class="welcome">
                    <h2>Definir nova senha</h2>
                    <p>Escolha uma senha forte e memorável para manter sua conta segura.</p>
                </header>
            </section>

            <section class="login-right">
                <div class="card">
                    <h3 class="card-title">Nova senha</h3>

                    <div id="messageContainer"></div>

                    <form action="<?= BASE_URL ?>resetar-senha" method="POST" novalidate id="resetForm">
                        <?= csrf_input('reset_form') ?>
                        <input type="hidden" name="token" value="<?= htmlspecialchars($token ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <div class="field">
                            <input type="password" name="password" id="password"
                                placeholder="Nova senha (mínimo 8 caracteres)" required minlength="8"
                                aria-label="Nova senha">
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
                                aria-label="Confirmar nova senha">
                            <button type="button" class="toggle-password" data-target="password_confirmation">
                                <i data-lucide="eye"></i>
                            </button>
                        </div>

                        <button type="submit" class="btn-primary" id="submitBtn">
                            <span>Redefinir senha</span>
                        </button>

                        <p class="extra-link">
                            <a href="<?= BASE_URL ?>login"> <i data-lucide="arrow-left"></i>
                                Voltar para o login</a>
                        </p>
                    </form>
                </div>
            </section>
        </div>
    </main>

    <?php loadPageJs('admin-auth-reset-password'); ?>
    <script src="<?= BASE_URL ?>assets/js/lucide-init.js"></script>
</body>

</html>