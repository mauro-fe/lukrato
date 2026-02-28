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

    <style>
        :root {
            --primary: #e67e22;
            --primary-dark: #d35400;
            --success: #27ae60;
            --text: #2c3e50;
            --text-light: #7f8c8d;
            --bg: #f8f9fa;
            --card-bg: #ffffff;
            --border: #e5e7eb;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: var(--card-bg);
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            padding: 48px;
            max-width: 480px;
            width: 100%;
            text-align: center;
        }

        .icon {
            font-size: 64px;
            margin-bottom: 24px;
        }

        h1 {
            color: var(--text);
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 16px;
        }

        .message {
            color: var(--text-light);
            font-size: 15px;
            line-height: 1.6;
            margin-bottom: 24px;
        }

        .email-highlight {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 24px;
            font-weight: 500;
            color: #0369a1;
        }

        .tips {
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 24px;
            text-align: left;
        }

        .tips-title {
            font-weight: 600;
            color: #92400e;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .tips ul {
            color: #78350f;
            font-size: 13px;
            padding-left: 20px;
            margin: 0;
        }

        .tips li {
            margin-bottom: 4px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px 28px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 15px;
            text-decoration: none;
            transition: all 0.2s ease;
            cursor: pointer;
            border: none;
            width: 100%;
            margin-bottom: 12px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: #fff;
            box-shadow: 0 4px 14px rgba(230, 126, 34, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(230, 126, 34, 0.5);
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--border);
            color: var(--text);
        }

        .btn-outline:hover {
            background: var(--bg);
            border-color: var(--text-light);
        }

        .resend-form {
            margin-top: 8px;
        }

        .resend-message {
            font-size: 13px;
            margin-top: 16px;
            padding: 12px;
            border-radius: 8px;
            display: none;
        }

        .resend-message.success {
            background: #ecfdf5;
            color: #065f46;
            display: block;
        }

        .resend-message.error {
            background: #fef2f2;
            color: #991b1b;
            display: block;
        }

        .footer-link {
            margin-top: 24px;
            font-size: 14px;
            color: var(--text-light);
        }

        .footer-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }

        .footer-link a:hover {
            text-decoration: underline;
        }

        .btn.loading {
            opacity: 0.7;
            pointer-events: none;
        }

        .spinner {
            width: 18px;
            height: 18px;
            border: 2px solid #fff;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
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

    <script>
        const form = document.getElementById('resendForm');
        const btn = document.getElementById('resendBtn');
        const message = document.getElementById('resendMessage');

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            btn.classList.add('loading');
            btn.innerHTML = '<span class="spinner"></span><span>Enviando...</span>';
            message.className = 'resend-message';
            message.style.display = 'none';

            try {
                const resp = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams(new FormData(form))
                });

                const data = await resp.json();

                if (resp.ok) {
                    message.textContent = data.message || 'E-mail reenviado com sucesso!';
                    message.className = 'resend-message success';
                } else {
                    message.textContent = data.message || 'Não foi possível reenviar o e-mail.';
                    message.className = 'resend-message error';
                }
            } catch (err) {
                message.textContent = 'Erro ao reenviar. Tente novamente.';
                message.className = 'resend-message error';
            } finally {
                btn.classList.remove('loading');
                btn.innerHTML = '<span>Reenviar e-mail de verificação</span>';
            }
        });
    </script>
</body>
</html>
