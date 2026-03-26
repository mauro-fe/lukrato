<?php

/**
 * Página de confirmação para criar conta via Google
 *
 * Espera $googleData ser fornecido pelo controller.
 */
$favicon = rtrim(BASE_URL, '/') . '/assets/img/icone.png?v=1';
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= $favicon ?>">
    <link rel="shortcut icon" type="image/png" sizes="32x32" href="<?= $favicon ?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmar Cadastro - <?= $_ENV['APP_NAME'] ?? 'Lukrato' ?></title>
    <?= function_exists('vite_css') ? vite_css('site-google-confirm') : '' ?>
</head>

<body>
    <div class="confirm-container">
        <img src="<?= BASE_URL ?>assets/img/logo.png" alt="Lukrato" class="logo w-100">

        <h1>Criar sua conta</h1>
        <p class="subtitle">Você está a um passo de começar!</p>

        <div class="user-info">
            <?php if (!empty($googleData['picture'])): ?>
                <img src="<?= htmlspecialchars($googleData['picture']) ?>" alt="Foto" class="user-avatar">
            <?php endif; ?>
            <div class="user-name"><?= htmlspecialchars($googleData['name']) ?></div>
            <div class="user-email"><?= htmlspecialchars($googleData['email']) ?></div>
        </div>

        <div class="info-text">
            <strong>Não encontramos uma conta com este email.</strong><br>
            Ao continuar, criaremos uma nova conta para você com os dados acima.
        </div>

        <div class="buttons">
            <a href="<?= BASE_URL ?>auth/google/cancel" class="btn btn-cancel">
                Cancelar
            </a>
            <a href="<?= BASE_URL ?>auth/google/confirm" class="btn btn-confirm">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                Criar Conta
            </a>
        </div>

        <p class="terms">
            Ao criar sua conta, você concorda com nossos<br>
            <a href="<?= BASE_URL ?>termos">Termos de Uso</a> e <a href="<?= BASE_URL ?>privacidade">Política de
                Privacidade</a>
        </p>
    </div>
</body>

</html>
