<!DOCTYPE html>
<html lang="pt-BR" data-theme="dark">
<?php
$favicon = rtrim(BASE_URL, '/') . '/assets/img/icone.png?v=1';
$loginUrl = $loginUrl ?? rtrim(BASE_URL, '/') . '/login';
$currentFormAction = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
$initialEmail = trim((string) ($email ?? ''));
$initialMessage = trim((string) ($message ?? ''));

if ($initialMessage === '') {
    $initialMessage = 'Carregando seu aviso de verificacao...';
}
?>

<head>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= $favicon ?>">
    <link rel="shortcut icon" type="image/png" sizes="32x32" href="<?= $favicon ?>">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base-url" content="<?= rtrim(BASE_URL, '/') . '/' ?>">
    <?= csrf_meta('verify_email_form') ?>

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

    <title>Verifique seu Email - Lukrato</title>
    <script src="<?= rtrim(BASE_URL, '/') ?>/assets/js/lucide.min.js"></script>
    <?= function_exists('vite_css') ? vite_css('auth-verify-email-style') : '' ?>
</head>

<body>
    <div
        class="container"
        data-verify-email-root
        data-login-url="<?= htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') ?>">
        <div class="icon" aria-hidden="true">
            <i data-lucide="mail" style="width:64px;height:64px;" aria-hidden="true"></i>
        </div>

        <p class="eyebrow">Confirmação de cadastro</p>
        <h1>Verifique seu e-mail</h1>

        <p class="message" data-verify-email-message>
            <?= htmlspecialchars($initialMessage, ENT_QUOTES, 'UTF-8') ?>
        </p>

        <div class="email-highlight" data-verify-email-highlight<?= $initialEmail !== '' ? '' : ' hidden' ?>>
            <i data-lucide="mail" style="width:16px;height:16px;" aria-hidden="true"></i> <span data-verify-email-address><?= htmlspecialchars($initialEmail, ENT_QUOTES, 'UTF-8') ?></span>
        </div>

        <div class="tips">
            <div class="tips-title"><i data-lucide="lightbulb" style="width:16px;height:16px;" aria-hidden="true"></i> Não encontrou o e-mail?</div>
            <ul>
                <li>Verifique sua pasta de spam ou lixo eletrônico</li>
                <li>Aguarde alguns minutos, pode haver um pequeno atraso</li>
                <li>Verifique se digitou o e-mail corretamente</li>
            </ul>
        </div>

        <div class="actions">
            <form class="resend-form" id="resendForm" method="POST" action="<?= htmlspecialchars($currentFormAction, ENT_QUOTES, 'UTF-8') ?>">
                <?= csrf_input('verify_email_form') ?>
                <input type="hidden" name="email" value="<?= htmlspecialchars($initialEmail, ENT_QUOTES, 'UTF-8') ?>" data-verify-email-input>
                <button type="submit" class="btn btn-primary" id="resendBtn" aria-label="Reenviar e-mail de verificação">
                    <span>Reenviar e-mail de verificação</span>
                </button>
            </form>

            <div class="resend-message" id="resendMessage" aria-live="polite" role="status"></div>

            <a href="<?= htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline" data-verify-email-login-link>
                Voltar para o login
            </a>
        </div>

        <p class="footer-link">
            Precisa de ajuda? <a href="<?= BASE_URL ?>contato">Entre em contato</a>
        </p>
    </div>
    <?php loadPageJs('admin-auth-verify-email'); ?>
</body>

</html>