<?php
$favicon = rtrim(BASE_URL, '/') . '/assets/img/icone.png?v=1';
$forgotPasswordPageUrl = rtrim(BASE_URL, '/') . '/recuperar-senha';
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="dark">

<head>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= $favicon ?>">
    <link rel="shortcut icon" type="image/png" sizes="32x32" href="<?= $favicon ?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base-url" content="<?= rtrim(BASE_URL, '/') . '/' ?>">

    <!-- CSRF Meta Tags para renovação automática -->
    <?= csrf_meta('forgot_form') ?>

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

    <title>Recuperar senha - Lukrato</title>

    <!-- Lucide Icons + FA Brands -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/brands.min.css">
    <script src="<?= rtrim(BASE_URL, '/') ?>/assets/js/lucide.min.js"></script>
    <?= function_exists('vite_css') ? vite_css('auth-shared-style') : '' ?>
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
                        <img src="<?= ASSETS_URL ?>img/logo-top.png" alt="Lukrato">
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
                    <div class="card-panel-head">
                        <p class="card-kicker">Recuperação por e-mail</p>
                        <h3 class="card-title">Receba um link seguro</h3>
                        <p class="card-subtitle">Informe o e-mail cadastrado e enviaremos as instruções para redefinir sua senha.</p>
                    </div>

                    <div id="messageContainer" class="card-status"></div>

                    <form action="<?= htmlspecialchars($forgotPasswordPageUrl, ENT_QUOTES, 'UTF-8') ?>" method="POST" novalidate id="recoverForm">
                        <?= csrf_input('forgot_form') ?>
                        <div class="field">
                            <input type="email" name="email" id="email" placeholder="Digite seu e-mail"
                                autocomplete="email" autocapitalize="off" spellcheck="false" required aria-label="E-mail">
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
</body>

</html>