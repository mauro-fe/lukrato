<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login / Cadastro - Lukrato</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css">
    <?php loadPageCss('admin-admins-login'); ?>
</head>

<body>
    <div class="particles" id="particles"></div>

    <main class="lukrato-auth">
        <div class="login-wrapper">
            <section class="login-left">
                <div class="brand">
                    <div class="imagem-logo" aria-label="Lukrato">
                        <img src="<?= BASE_URL ?>assets/img/logo.png" alt="Lukrato">
                    </div>

                </div>

                <header class="welcome">
                    <h2>Bem-vindo</h2>
                    <p>
                        Acompanhe saldos, investimentos e objetivos em um único lugar.
                        Entre para gerenciar suas finanças com segurança e performance.
                    </p>
                </header>
            </section>

            <section class="login-right">
                <div class="card" data-active="login">
                    <div class="tabs">
                        <button class="tab-btn is-active" data-tab="login" type="button">
                            Entrar
                        </button>
                        <button class="tab-btn" data-tab="register" type="button">
                            Cadastrar
                        </button>
                    </div>

                    <div class="flip-container">
                        <div class="flip-inner">
                            <!-- LOGIN -->
                            <div class="flip-face flip-login">
                                <h3 class="card-title">Entrar</h3>

                                <form action="<?= BASE_URL ?>login/entrar" method="POST" id="loginForm" novalidate>
                                    <?= csrf_input('login_form') ?>
                                    <div class="field">
                                        <input type="email" id="email" name="email" placeholder="E-mail" required>
                                        <small class="field-error" id="emailError"></small>
                                    </div>

                                    <div class="field">
                                        <input type="password" id="password" name="password" placeholder="Senha"
                                            required>
                                        <button type="button" class="toggle-password" data-target="password">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                        <small class="field-error" id="passwordError"></small>
                                    </div>

                                    <button type="submit" class="btn-primary">
                                        <span>Entrar</span>
                                    </button>

                                    <div class="auth-separator"><span>ou</span></div>

                                    <a href="<?= BASE_URL ?>auth/google/login" class="google-sign-in-button">
                                        <svg class="google-icon" viewBox="0 0 48 48">
                                            <path fill="#EA4335"
                                                d="M24 9.5c3.3 0 6.2 1.1 8.5 3.2l6.3-6.3C34.6 2.4 29.7 0 24 0 14.6 0 6.6 5.4 2.7 13.2l7.4 5.7C12 13.1 17.5 9.5 24 9.5z" />
                                            <path fill="#4285F4"
                                                d="M46.5 24.5c0-1.6-.1-3.1-.4-4.5H24v9h12.7c-.6 3-2.3 5.5-4.8 7.2l7.4 5.7c4.3-4 6.8-9.8 6.8-17.4z" />
                                            <path fill="#FBBC05"
                                                d="M10.1 28.9c-.8-2.3-.8-4.8 0-7.1l-7.4-5.7c-3.2 6.4-3.2 13.9 0 20.3l7.4-5.7z" />
                                            <path fill="#34A853"
                                                d="M24 48c6.5 0 12.1-2.1 16.1-5.8l-7.4-5.7c-2.1 1.4-4.8 2.3-8.7 2.3-6.5 0-12-4.1-14-9.9l-7.4 5.7C6.6 42.6 14.6 48 24 48z" />
                                        </svg>
                                        <span>Entrar com Google</span>
                                    </a>

                                    <p class="extra-link">
                                        <a href="<?= BASE_URL ?>recuperar-senha">Esqueceu a senha?</a>

                                    </p>

                                    <div id="generalError" class="msg msg-error general-message"></div>
                                    <div id="generalSuccess" class="msg msg-success general-message"></div>
                                </form>
                            </div>

                            <!-- REGISTER -->
                            <div class="flip-face flip-register">
                                <h3 class="card-title">Cadastrar</h3>

                                <form action="<?= BASE_URL ?>register/criar" method="POST" id="registerForm" novalidate>
                                    <?= csrf_input('register_form') ?>
                                    <div class="field">
                                        <input type="text" id="name" name="name" placeholder="Nome completo" required>
                                        <small class="field-error" id="nameError"></small>
                                    </div>

                                    <div class="field">
                                        <input type="email" id="reg_email" name="email" placeholder="E-mail" required>
                                        <small class="field-error" id="regEmailError"></small>
                                    </div>

                                    <div class="field">
                                        <input type="password" id="reg_password" name="password"
                                            placeholder="Senha (mínimo 8 caracteres)" required>
                                        <button type="button" class="toggle-password" data-target="reg_password">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                        <small class="field-error" id="regPasswordError"></small>
                                    </div>

                                    <div class="field">
                                        <input type="password" id="reg_password_confirm" name="password_confirmation"
                                            placeholder="Confirmar senha" required>
                                        <button type="button" class="toggle-password"
                                            data-target="reg_password_confirm">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                        <small class="field-error" id="regPasswordConfirmError"></small>
                                    </div>

                                    <button type="submit" class="btn-primary">
                                        <span>Criar conta</span>
                                    </button>

                                    <div class="terms-link">
                                        Ao se cadastrar, você concorda com os
                                        <a href="<?= BASE_URL ?>termos">Termos de Uso</a> e a
                                        <a href="<?= BASE_URL ?>privacidade">Política de Privacidade</a>.
                                    </div>

                                    <div class="auth-separator"><span>ou</span></div>

                                    <a href="<?= BASE_URL ?>auth/google/register" class="google-sign-in-button">
                                        <svg class="google-icon" viewBox="0 0 48 48">
                                            <path fill="#EA4335"
                                                d="M24 9.5c3.3 0 6.2 1.1 8.5 3.2l6.3-6.3C34.6 2.4 29.7 0 24 0 14.6 0 6.6 5.4 2.7 13.2l7.4 5.7C12 13.1 17.5 9.5 24 9.5z" />
                                            <path fill="#4285F4"
                                                d="M46.5 24.5c0-1.6-.1-3.1-.4-4.5H24v9h12.7c-.6 3-2.3 5.5-4.8 7.2l7.4 5.7c4.3-4 6.8-9.8 6.8-17.4z" />
                                            <path fill="#FBBC05"
                                                d="M10.1 28.9c-.8-2.3-.8-4.8 0-7.1l-7.4-5.7c-3.2 6.4-3.2 13.9 0 20.3l7.4-5.7z" />
                                            <path fill="#34A853"
                                                d="M24 48c6.5 0 12.1-2.1 16.1-5.8l-7.4-5.7c-2.1 1.4-4.8 2.3-8.7 2.3-6.5 0-12-4.1-14-9.9l-7.4 5.7C6.6 42.6 14.6 48 24 48z" />
                                        </svg>
                                        <span>Cadastrar com Google</span>
                                    </a>

                                    <div id="registerGeneralError" class="msg msg-error general-message"></div>
                                    <div id="registerGeneralSuccess" class="msg msg-success general-message"></div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <script>
        // Partículas
        function createParticles() {
            const container = document.getElementById('particles');
            for (let i = 0; i < 20; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 8 + 's';
                particle.style.animationDuration = (Math.random() * 4 + 6) + 's';
                container.appendChild(particle);
            }
        }
        createParticles();

        // Tabs
        const card = document.querySelector('.card');
        const tabBtns = document.querySelectorAll('.tab-btn');

        tabBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const tab = btn.dataset.tab;
                card.dataset.active = tab;

                tabBtns.forEach(b => b.classList.remove('is-active'));
                btn.classList.add('is-active');
            });
        });

        // Toggle password
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('.toggle-password');
            if (!btn) return;

            const targetId = btn.dataset.target;
            const input = document.getElementById(targetId);
            if (!input) return;

            const icon = btn.querySelector('i');
            const isPassword = input.type === 'password';

            input.type = isPassword ? 'text' : 'password';
            icon.classList.toggle('fa-eye', !isPassword);
            icon.classList.toggle('fa-eye-slash', isPassword);
        });

        // Helpers de erro
        function showError(inputId, errorId, message) {
            const input = document.getElementById(inputId);
            const error = document.getElementById(errorId);
            if (error) error.textContent = message;
            if (input) {
                input.style.borderColor = 'var(--error)';
                input.addEventListener('input', () => {
                    input.style.borderColor = 'transparent';
                    if (error) error.textContent = '';
                }, {
                    once: true
                });
            }
        }

        function clearErrors(form) {
            form.querySelectorAll('.field-error').forEach(el => el.textContent = '');
            form.querySelectorAll('input').forEach(el => el.style.borderColor = 'transparent');
            form.querySelectorAll('.general-message').forEach(el => {
                el.textContent = '';
                el.classList.remove('show');
            });
        }

        // ======================
        // LOGIN REAL COM AJAX
        // ======================
        const loginForm = document.getElementById('loginForm');

        if (loginForm) {
            loginForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                clearErrors(loginForm);

                const emailVal = document.getElementById('email').value.trim();
                const passwordVal = document.getElementById('password').value;

                let hasError = false;

                if (!emailVal) {
                    showError('email', 'emailError', 'Digite seu e-mail');
                    hasError = true;
                } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailVal)) {
                    showError('email', 'emailError', 'E-mail inválido');
                    hasError = true;
                }

                if (!passwordVal) {
                    showError('password', 'passwordError', 'Digite sua senha');
                    hasError = true;
                }

                if (hasError) return;

                const btn = loginForm.querySelector('.btn-primary');
                const originalBtnHtml = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<span>Entrando...</span>';

                const generalError = document.getElementById('generalError');
                const generalSuccess = document.getElementById('generalSuccess');

                try {
                    const formData = new FormData(loginForm);

                    const response = await fetch(loginForm.action, {
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
                    } catch (e) {
                        // Se não veio JSON, tratamos como erro genérico
                    }

                    const payload = (data && typeof data.data === 'object') ? data.data : {};
                    const success = data && (data.success === true || data.status === 'success');

                    if (!response.ok || !success) {
                        const message =
                            (data && data.message) ||
                            (response.status === 429 ?
                                'Muitas tentativas. Aguarde um pouco e tente novamente.' :
                                'E-mail ou senha inválidos ou erro ao processar login.');

                        if (generalError) {
                            generalError.textContent = message;
                            generalError.classList.add('show');
                        }

                        // Exibir erros de campos, se a API mandar
                        if (data && data.errors && typeof data.errors === 'object') {
                            if (data.errors.email) {
                                const msg = Array.isArray(data.errors.email) ?
                                    data.errors.email[0] :
                                    data.errors.email;
                                showError('email', 'emailError', msg);
                            }
                            if (data.errors.password) {
                                const msg = Array.isArray(data.errors.password) ?
                                    data.errors.password[0] :
                                    data.errors.password;
                                showError('password', 'passwordError', msg);
                            }
                        }

                        btn.disabled = false;
                        btn.innerHTML = originalBtnHtml;
                        return;
                    }

                    // Sucesso
                    if (generalSuccess) {
                        generalSuccess.textContent = data.message || 'Login realizado com sucesso!';
                        generalSuccess.classList.add('show');
                    }

                    const redirectUrl = (data && data.redirect) ? data.redirect : '<?= BASE_URL ?>dashboard';

                    setTimeout(() => {
                        window.location.href = redirectUrl;
                    }, 800);

                } catch (error) {
                    console.error('Erro na requisição de login:', error);
                    if (generalError) {
                        generalError.textContent =
                            'Não foi possível realizar o login. Tente novamente em instantes.';
                        generalError.classList.add('show');
                    }
                    btn.disabled = false;
                    btn.innerHTML = originalBtnHtml;
                }
            });
        }

        // ======================
        // REGISTER (VALIDA E ENVIA NORMAL)
        // ======================
        const registerForm = document.getElementById('registerForm');

        if (registerForm) {
            registerForm.addEventListener('submit', (e) => {
                e.preventDefault();
                clearErrors(registerForm);

                const name = document.getElementById('name').value.trim();
                const email = document.getElementById('reg_email').value.trim();
                const password = document.getElementById('reg_password').value;
                const confirm = document.getElementById('reg_password_confirm').value;

                let hasError = false;

                if (!name) {
                    showError('name', 'nameError', 'Digite seu nome completo');
                    hasError = true;
                }

                if (!email) {
                    showError('reg_email', 'regEmailError', 'Digite seu e-mail');
                    hasError = true;
                } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                    showError('reg_email', 'regEmailError', 'E-mail inválido');
                    hasError = true;
                }

                if (!password) {
                    showError('reg_password', 'regPasswordError', 'Digite sua senha');
                    hasError = true;
                } else if (password.length < 8) {
                    showError('reg_password', 'regPasswordError', 'Senha deve ter no mínimo 8 caracteres');
                    hasError = true;
                }

                if (!confirm) {
                    showError('reg_password_confirm', 'regPasswordConfirmError', 'Confirme sua senha');
                    hasError = true;
                } else if (password !== confirm) {
                    showError('reg_password_confirm', 'regPasswordConfirmError', 'As senhas não coincidem');
                    hasError = true;
                }

                if (hasError) return;

                // Se passou pela validação, envia normalmente para o backend tratar
                registerForm.submit();
            });
        }

        // Confete de celebração (você pode usar depois no cadastro se quiser)
        function createConfetti() {
            const colors = ['#e67e22', '#f39c12', '#79e6a0', '#7aa7ff'];
            for (let i = 0; i < 40; i++) {
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
    </script>

</body>

</html>