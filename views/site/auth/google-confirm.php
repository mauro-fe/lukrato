<?php

/**
 * Página de confirmação para criar conta via Google
 */
$googleData = $_SESSION['google_pending_user'] ?? null;
if (!$googleData) {
    header('Location: ' . BASE_URL . 'login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmar Cadastro - <?= $_ENV['APP_NAME'] ?? 'Lukrato' ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
        min-height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 20px;
    }

    .confirm-container {
        background: rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(10px);
        border-radius: 20px;
        padding: 40px;
        max-width: 450px;
        width: 100%;
        text-align: center;
        border: 1px solid rgba(255, 255, 255, 0.1);
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    }

    .logo {

        margin-bottom: 20px;
    }

    h1 {
        color: #fff;
        font-size: 24px;
        margin-bottom: 10px;
    }

    .subtitle {
        color: rgba(255, 255, 255, 0.7);
        font-size: 14px;
        margin-bottom: 30px;
    }

    .user-info {
        background: rgba(255, 255, 255, 0.08);
        border-radius: 12px;
        padding: 25px;
        margin-bottom: 30px;
    }

    .user-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        margin-bottom: 15px;
        border: 3px solid #f5a623;
    }

    .user-name {
        color: #fff;
        font-size: 20px;
        font-weight: 600;
        margin-bottom: 5px;
    }

    .user-email {
        color: rgba(255, 255, 255, 0.7);
        font-size: 14px;
    }

    .info-text {
        color: rgba(255, 255, 255, 0.6);
        font-size: 13px;
        line-height: 1.6;
        margin-bottom: 25px;
        padding: 15px;
        background: rgba(245, 166, 35, 0.1);
        border-radius: 8px;
        border-left: 3px solid #f5a623;
        text-align: left;
    }

    .buttons {
        display: flex;
        gap: 15px;
    }

    .btn {
        flex: 1;
        padding: 14px 24px;
        border-radius: 10px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .btn-confirm {
        background: linear-gradient(135deg, #f5a623 0%, #e6951d 100%);
        color: #fff;
        border: none;
    }

    .btn-confirm:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(245, 166, 35, 0.3);
    }

    .btn-cancel {
        background: transparent;
        color: rgba(255, 255, 255, 0.7);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .btn-cancel:hover {
        background: rgba(255, 255, 255, 0.05);
        color: #fff;
    }

    .terms {
        margin-top: 20px;
        font-size: 12px;
        color: rgba(255, 255, 255, 0.5);
    }

    .terms a {
        color: #f5a623;
        text-decoration: none;
    }

    .terms a:hover {
        text-decoration: underline;
    }
    </style>
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