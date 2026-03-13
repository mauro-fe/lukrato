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
    <?= csrf_meta('forgot_form') ?>

    <title>Recuperar senha - Lukrato</title>

    <!-- Lucide Icons + FA Brands -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/brands.min.css">
    <link rel="stylesheet" href="<?= rtrim(BASE_URL, '/') ?>/assets/css/vendor/lucide-compat.css">
    <script src="<?= rtrim(BASE_URL, '/') ?>/assets/js/lucide.min.js"></script>
    <link rel="stylesheet" href="<?= rtrim(BASE_URL, '/') ?>/assets/css/core/fonts.css">
    <link rel="stylesheet" href="<?= rtrim(BASE_URL, '/') ?>/assets/css/core/variables.css">
    <?php loadPageCss('auth-shared'); ?>
</head>

<body>

    <!-- Partículas decorativas -->
    <div class="particles" id="particles"></div>

    <main class="lukrato-auth">
        <div class="login-wrapper">
            <!-- LEFT: Branding & Welcome -->
            <section class="login-left">
                <div class="brand">
                    <div class="imagem-logo">
                        <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 200 60'%3E%3Ctext x='10' y='40' font-family='Arial,sans-serif' font-size='36' font-weight='bold' fill='%23e67e22'%3ELukrato%3C/text%3E%3C/svg%3E"
                            alt="Lukrato">
                    </div>
                </div>

                <header class="welcome">
                    <h2>Recuperar senha</h2>
                    <p>Não se preocupe! Digite seu e-mail e enviaremos um link seguro para redefinir sua senha.</p>
                </header>
            </section>

            <!-- RIGHT: Form Card -->
            <section class="login-right">
                <div class="card">
                    <h3 class="card-title">Esqueceu sua senha?</h3>

                    <div id="messageContainer"></div>

                    <form action="<?= BASE_URL ?>recuperar-senha" method="POST" novalidate id="recoverForm">
                        <?= csrf_input('forgot_form') ?>
                        <div class="field">
                            <input type="email" name="email" id="email" placeholder="Digite seu e-mail"
                                autocomplete="email" required aria-label="E-mail">
                            <i data-lucide="mail" class="field-icon"></i>
                        </div>

                        <button type="submit" class="btn-primary" id="submitBtn">
                            <span>Enviar link de recuperação</span>
                        </button>

                        <p class="extra-link">
                            <a href="<?= BASE_URL ?>login"> <i data-lucide="arrow-left"></i>
                                Voltar para o login</a>
                        </p>

                        <p class="extra-link">
                            <small>
                                <strong>Dica:</strong> Se você se cadastrou com o Google, use o botão "Entrar com
                                Google" na página de login.
                            </small>
                        </p>
                    </form>
                </div>
            </section>
        </div>
    </main>

    <?php loadPageJs('admin-auth-forgot-password'); ?>
    <script src="<?= rtrim(BASE_URL, '/') ?>/assets/js/lucide-init.js"></script>

</body>

</html>