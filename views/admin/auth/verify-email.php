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

    <title>Verifique seu Email - Lukrato</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <?php loadPageCss('auth-verify-email'); ?>
</head>

<body>
    <div class="container">
        <div class="icon">✉️</div>
        
        <h1>Verifique seu e-mail</h1>
        
        <p class="message">
            <?= htmlspecialchars($message) ?>
        </p>

        <?php if ($email): ?>
        <div class="email-highlight">
            📧 <?= htmlspecialchars($email) ?>
        </div>
        <?php endif; ?>

        <div class="tips">
            <div class="tips-title">💡 Não encontrou o e-mail?</div>
            <ul>
                <li>Verifique sua pasta de spam ou lixo eletrônico</li>
                <li>Aguarde alguns minutos, pode haver um pequeno atraso</li>
                <li>Verifique se digitou o e-mail corretamente</li>
            </ul>
        </div>

        <form class="resend-form" id="resendForm" method="POST" action="<?= BASE_URL ?>verificar-email/reenviar">
            <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
            <button type="submit" class="btn btn-primary" id="resendBtn">
                <span>Reenviar e-mail de verificação</span>
            </button>
        </form>

        <div class="resend-message" id="resendMessage"></div>

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
