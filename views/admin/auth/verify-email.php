<!DOCTYPE html>
<html lang="pt-BR">
<?php
$favicon = rtrim(BASE_URL, '/') . '/assets/img/icone.png?v=1';
$email = $email ?? '';
$message = $message ?? 'Por favor, verifique seu email antes de fazer login.';
?>

<head>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= $favicon ?>">
    <link rel="shortcut icon" type="image/png" sizes="32x32" href="<?= $favicon ?>">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base-url" content="<?= rtrim(BASE_URL, '/') . '/' ?>">
    <?= csrf_meta('verify_email_form') ?>

    <title>Verifique seu Email - Lukrato</title>
    <script src="<?= rtrim(BASE_URL, '/') ?>/assets/js/lucide.min.js"></script>
    <?= function_exists('vite_css') ? vite_css('auth-verify-email-style') : '' ?>
</head>

<body>
    <div class="container">
        <div class="icon" aria-hidden="true">
            <i data-lucide="mail" style="width:64px;height:64px;" aria-hidden="true"></i>
        </div>
        
        <h1>Verifique seu e-mail</h1>
        
        <p class="message">
            <?= htmlspecialchars($message) ?>
        </p>

        <?php if ($email): ?>
        <div class="email-highlight">
            <i data-lucide="mail" style="width:16px;height:16px;" aria-hidden="true"></i> <?= htmlspecialchars($email) ?>
        </div>
        <?php endif; ?>

        <div class="tips">
            <div class="tips-title"><i data-lucide="lightbulb" style="width:16px;height:16px;" aria-hidden="true"></i> Não encontrou o e-mail?</div>
            <ul>
                <li>Verifique sua pasta de spam ou lixo eletrônico</li>
                <li>Aguarde alguns minutos, pode haver um pequeno atraso</li>
                <li>Verifique se digitou o e-mail corretamente</li>
            </ul>
        </div>

        <form class="resend-form" id="resendForm" method="POST" action="<?= BASE_URL ?>verificar-email/reenviar">
            <?= csrf_input('verify_email_form') ?>
            <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
            <button type="submit" class="btn btn-primary" id="resendBtn" aria-label="Reenviar e-mail de verificação">
                <span>Reenviar e-mail de verificação</span>
            </button>
        </form>

        <div class="resend-message" id="resendMessage" aria-live="polite" role="status"></div>

        <a href="<?= BASE_URL ?>login" class="btn btn-outline">
            ← Voltar para o login
        </a>

        <p class="footer-link">
            Precisa de ajuda? <a href="<?= BASE_URL ?>contato">Entre em contato</a>
        </p>
    </div>
    <?php loadPageJs('admin-auth-verify-email'); ?>
</body>
</html>
