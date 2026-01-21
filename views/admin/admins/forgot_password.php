<?php
$favicon        = rtrim(BASE_URL, '/') . '/assets/img/icone.png?v=1'; ?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= $favicon ?>">
    <link rel="shortcut icon" type="image/png" sizes="32x32" href="<?= $favicon ?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar senha - Lukrato</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css">
    <style>
        /* Paleta e tokens */
        :root {
            --orange: #e67e22;
            --orange-strong: #f39c12;
            --orange-light: #f8b575;
            --bg-1: #0b141c;
            --bg-2: #152636;
            --card: #0f1b26;
            --card-2: #111f2b;
            --text: #e8edf3;
            --muted: #a9b7c5;
            --input: #1b2a37;
            --border: #243647;
            --shadow: rgba(0, 0, 0, 0.45);
            --success: #79e6a0;
            --error: #ffb4b4;
            --radius: 18px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html,
        body {
            height: 100%;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, Ubuntu, "Helvetica Neue", Arial, sans-serif;
            background: var(--bg-1);
            color: var(--text);
        }

        /* Animação de fundo */
        @keyframes gradientShift {

            0%,
            100% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }
        }

        main.lukrato-auth {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
            background: radial-gradient(ellipse 1200px 800px at 20% 30%,
                    #1b3145 0%,
                    var(--bg-2) 40%,
                    var(--bg-1) 100%);
            background-size: 200% 200%;
            animation: gradientShift 15s ease infinite;
        }

        /* Partículas decorativas */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            pointer-events: none;
            z-index: 0;
        }

        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: var(--orange);
            border-radius: 50%;
            opacity: 0;
            animation: float 8s ease-in-out infinite;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(100vh) scale(0);
                opacity: 0;
            }

            10% {
                opacity: 0.6;
            }

            90% {
                opacity: 0.3;
            }

            100% {
                transform: translateY(-100px) scale(1);
            }
        }

        .login-wrapper {
            display: flex;
            gap: 60px;
            max-width: 1200px;
            width: 100%;
            align-items: center;
            position: relative;
            z-index: 1;
            animation: fadeInUp 0.8s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* LEFT SECTION */
        .login-left {
            flex: 1;
            animation: slideInLeft 0.8s ease;
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-40px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .brand {
            margin-bottom: 32px;
        }

        .imagem-logo {
            display: inline-block;
            animation: logoFloat 3s ease-in-out infinite;
        }

        @keyframes logoFloat {

            0%,
            100% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        .imagem-logo img {
            width: 250px;
            max-width: 100%;
            height: auto;
            filter: drop-shadow(0 4px 12px rgba(230, 126, 34, 0.3));
            transition: filter 0.3s ease;
        }

        .imagem-logo img:hover {
            filter: drop-shadow(0 6px 20px rgba(230, 126, 34, 0.5));
        }

        .welcome h2 {
            font-size: 42px;
            line-height: 1.1;
            margin: 0 0 16px;
            background: linear-gradient(135deg, var(--text), var(--orange-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: titleShine 3s ease infinite;
        }

        @keyframes titleShine {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.9;
            }
        }

        .welcome p {
            color: var(--muted);
            line-height: 1.65;
            max-width: 50ch;
        }

        /* RIGHT SECTION */
        .login-right {
            flex: 1;
            animation: slideInRight 0.8s ease;
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(40px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .card {
            background: linear-gradient(180deg, var(--card), var(--card-2));
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 40px;
            box-shadow: 0 26px 60px var(--shadow), 0 0 0 1px rgba(230, 126, 34, 0.1);
            position: relative;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            height: 4px;
            background: linear-gradient(90deg, var(--orange), var(--orange-strong), var(--orange));
            background-size: 200% 100%;
            animation: borderFlow 3s linear infinite;
            border-radius: var(--radius) var(--radius) 0 0;
        }

        @keyframes borderFlow {
            0% {
                background-position: 0% 0%;
            }

            100% {
                background-position: 200% 0%;
            }
        }

        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 32px 70px var(--shadow), 0 0 0 1px rgba(230, 126, 34, 0.2);
        }

        .card-title {
            text-align: center;
            font-size: 32px;
            margin: 0 0 12px;
            position: relative;
            display: inline-block;
            width: 100%;
        }

        .card-title::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: linear-gradient(90deg, transparent, var(--orange), transparent);
            border-radius: 3px;
        }

        /* FIELD ANIMATIONS */
        .field {
            margin: 24px 0;
            position: relative;
        }

        .field input {
            width: 100%;
            background: var(--input);
            color: var(--text);
            border: 2px solid transparent;
            border-radius: 14px;
            padding: 16px 50px 16px 16px;
            font-size: 16px;
            outline: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .field input::placeholder {
            color: var(--muted);
            transition: opacity 0.3s ease;
        }

        .field input:focus::placeholder {
            opacity: 0.5;
        }

        .field input:focus {
            border-color: var(--orange);
            background: #203040;
            box-shadow:
                0 0 0 4px rgba(230, 126, 34, 0.15),
                inset 0 2px 4px rgba(0, 0, 0, 0.2);
            transform: translateY(-2px);
        }

        /* Ícone animado no campo */
        .field::before {
            content: '\f0e0';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
            font-size: 18px;
            pointer-events: none;
            transition: all 0.3s ease;
        }

        .field:has(input:focus)::before {
            color: var(--orange);
            transform: translateY(-50%) scale(1.1);
        }

        /* BUTTON */
        .btn-primary {
            width: 100%;
            background: linear-gradient(135deg, var(--orange), var(--orange-strong));
            color: #101417;
            border: none;
            border-radius: 14px;
            padding: 18px;
            font-size: 18px;
            font-weight: 800;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(230, 126, 34, 0.4);
        }

        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s ease;
        }

        .btn-primary:hover::before {
            left: 100%;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(230, 126, 34, 0.5);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-primary.loading span {
            opacity: 0;
        }

        .btn-primary.loading::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(16, 20, 23, 0.3);
            border-top-color: #101417;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* EXTRA LINKS */
        .extra-link {
            margin-top: 20px;
            text-align: center;
        }

        .extra-link a {
            color: var(--orange-strong);
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s ease;
        }

        .extra-link a:hover {
            color: var(--orange-light);
            gap: 10px;
        }

        .extra-link small {
            color: var(--muted);
            font-size: 13px;
            line-height: 1.5;
            display: block;
            margin-top: 8px;
        }

        .extra-link strong {
            color: var(--orange);
        }

        /* MESSAGES */
        .msg {
            padding: 14px 18px;
            border-radius: 12px;
            margin: 16px 0;
            font-size: 14px;
            animation: slideDown 0.4s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .msg::before {
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            font-size: 16px;
        }

        .msg-error {
            background: rgba(255, 180, 180, 0.1);
            border: 1px solid rgba(255, 180, 180, 0.3);
            color: var(--error);
        }

        .msg-error::before {
            content: '\f06a';
        }

        .msg-success {
            background: rgba(121, 230, 160, 0.1);
            border: 1px solid rgba(121, 230, 160, 0.3);
            color: var(--success);
        }

        .msg-success::before {
            content: '\f058';
        }

        /* RESPONSIVE */
        @media (max-width: 992px) {
            .login-wrapper {
                flex-direction: column;
                gap: 40px;
            }

            .login-left {
                text-align: center;
                align-items: center;
            }

            .welcome h2 {
                font-size: 36px;
            }

            .card {
                padding: 32px 28px;
            }
        }

        @media (max-width: 600px) {
            main.lukrato-auth {
                padding: 24px 16px;
            }

            .welcome h2 {
                font-size: 30px;
            }

            .welcome p {
                font-size: 15px;
            }

            .card {
                padding: 28px 24px;
            }

            .card-title {
                font-size: 26px;
            }

            .imagem-logo img {
                width: 180px;
            }
        }
    </style>

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
                                autocomplete="email" required>
                        </div>

                        <button type="submit" class="btn-primary" id="submitBtn">
                            <span>Enviar link de recuperação</span>
                        </button>

                        <p class="extra-link">
                            <a href="<?= BASE_URL ?>login"> <i class="fas fa-arrow-left"></i>
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

    <script>
        // Criar partículas animadas
        function createParticles() {
            const container = document.getElementById('particles');
            const particleCount = 20;

            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 8 + 's';
                particle.style.animationDuration = (Math.random() * 4 + 6) + 's';
                container.appendChild(particle);
            }
        }

        createParticles();

        // Form handling
        const form = document.getElementById('recoverForm');
        const submitBtn = document.getElementById('submitBtn');
        const emailInput = document.getElementById('email');
        const messageContainer = document.getElementById('messageContainer');

        function showMessage(type, text) {
            messageContainer.innerHTML = `
                <div class="msg msg-${type}">
                    ${text}
                </div>
            `;
        }

        function clearMessage() {
            messageContainer.innerHTML = '';
        }

        // Validação de email em tempo real
        emailInput.addEventListener('blur', function() {
            const email = this.value.trim();
            if (email && !email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
                this.style.borderColor = 'var(--error)';
                showMessage('error', 'Por favor, insira um e-mail válido');
            } else {
                this.style.borderColor = 'transparent';
                clearMessage();
            }
        });

        emailInput.addEventListener('input', function() {
            this.style.borderColor = 'transparent';
            clearMessage();
        });

        // Submit form REAL (AJAX)
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            clearMessage();

            const email = emailInput.value.trim();

            // Validação
            if (!email) {
                emailInput.focus();
                showMessage('error', 'Por favor, digite seu e-mail');
                return;
            }

            if (!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
                emailInput.focus();
                showMessage('error', 'Por favor, insira um e-mail válido');
                return;
            }

            // Loading state
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;
            const originalBtnHtml = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span>Enviando...</span>';

            try {
                const formData = new FormData(form);

                const response = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                let data = null;
                try {
                    data = await response.json();
                } catch (err) {
                    // resposta não é JSON → trata genérico
                }

                const success = data && (data.success === true || data.status === 'success');

                if (!response.ok || !success) {
                    const message =
                        (data && data.message) ||
                        (response.status === 429 ?
                            'Muitas tentativas. Aguarde um pouco e tente novamente.' :
                            'Não foi possível enviar o link de recuperação. Verifique o e-mail e tente novamente.'
                        );

                    showMessage('error', message);

                    // Se vierem erros de campo (ex: { errors: { email: [...] } })
                    if (data && data.errors && data.errors.email) {
                        emailInput.style.borderColor = 'var(--error)';
                    }

                    submitBtn.classList.remove('loading');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnHtml;
                    return;
                }

                // Sucesso
                const successMessage =
                    (data && data.message) ||
                    'Link de recuperação enviado! Verifique seu e-mail.';

                showMessage('success', successMessage);
                form.reset();
                emailInput.style.borderColor = 'transparent';

                // Confete de sucesso 🎉
                createConfetti();

            } catch (error) {
                console.error('Erro na requisição de recuperação de senha:', error);
                showMessage('error', 'Ocorreu um erro ao enviar o link. Tente novamente em instantes.');
            } finally {
                submitBtn.classList.remove('loading');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnHtml;
            }
        });

        // Efeito confete de sucesso
        function createConfetti() {
            const colors = ['#e67e22', '#f39c12', '#79e6a0', '#7aa7ff'];
            const confettiCount = 30;

            for (let i = 0; i < confettiCount; i++) {
                const confetti = document.createElement('div');
                confetti.style.position = 'fixed';
                confetti.style.width = '10px';
                confetti.style.height = '10px';
                confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                confetti.style.left = Math.random() * 100 + '%';
                confetti.style.top = '-10px';
                confetti.style.borderRadius = '50%';
                confetti.style.pointerEvents = 'none';
                confetti.style.zIndex = '9999';
                confetti.style.animation = `confettiFall ${Math.random() * 2 + 2}s ease-out forwards`;

                document.body.appendChild(confetti);

                setTimeout(() => confetti.remove(), 4000);
            }
        }

        // Adicionar animação de confete
        const style = document.createElement('style');
        style.textContent = `
            @keyframes confettiFall {
                to {
                    transform: translateY(100vh) rotate(${Math.random() * 360}deg);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);

        // Easter egg: Konami code
        let konamiCode = [];
        const konamiPattern = [
            'ArrowUp', 'ArrowUp',
            'ArrowDown', 'ArrowDown',
            'ArrowLeft', 'ArrowRight',
            'ArrowLeft', 'ArrowRight',
            'b', 'a'
        ];

        document.addEventListener('keydown', function(e) {
            konamiCode.push(e.key);
            konamiCode = konamiCode.slice(-10);

            if (konamiCode.join(',') === konamiPattern.join(',')) {
                document.body.style.animation = 'rainbow 2s linear infinite';
                setTimeout(() => {
                    document.body.style.animation = '';
                }, 5000);
            }
        });

        const rainbowStyle = document.createElement('style');
        rainbowStyle.textContent = `
            @keyframes rainbow {
                0% { filter: hue-rotate(0deg); }
                100% { filter: hue-rotate(360deg); }
            }
        `;
        document.head.appendChild(rainbowStyle);
    </script>