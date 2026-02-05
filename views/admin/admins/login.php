<!DOCTYPE html>
<html lang="pt-BR">
<?php
$activeTab = isset($activeTab) && $activeTab === 'register' ? 'register' : 'login';
$registerErrorMessage = $registerErrorMessage ?? '';
$favicon        = rtrim(BASE_URL, '/') . '/assets/img/icone.png?v=1';

?>

<head>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= $favicon ?>">
    <link rel="shortcut icon" type="image/png" sizes="32x32" href="<?= $favicon ?>">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base-url" content="<?= rtrim(BASE_URL, '/') . '/' ?>">

    <!-- CSRF Meta Tags para renovação automática -->
    <?= csrf_meta('login_form') ?>
    <meta name="csrf-token-register" content="<?= htmlspecialchars(csrf_token('register_form'), ENT_QUOTES, 'UTF-8') ?>">

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
                        <img src="<?= ASSETS_URL ?>assets/img/logo-top.png" alt="Lukrato">
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
                <div class="card" data-active="<?= $activeTab ?>">
                    <div class="tabs">
                        <button class="tab-btn <?= $activeTab === 'login' ? 'is-active' : '' ?>" data-tab="login"
                            type="button">
                            Entrar
                        </button>
                        <button class="tab-btn <?= $activeTab === 'register' ? 'is-active' : '' ?>" data-tab="register"
                            type="button">
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

                                    <div class="remember-me">
                                        <label class="checkbox-container">
                                            <input type="checkbox" id="remember" name="remember" value="1">
                                            <span class="checkmark"></span>
                                            <span class="checkbox-label">Lembrar de mim</span>
                                        </label>
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

                                    <div class="field referral-field">
                                        <div class="input-with-icon">
                                            <i class="fa-solid fa-gift referral-icon"></i>
                                            <input type="text" id="referral_code" name="referral_code"
                                                placeholder="Código de indicação (opcional)"
                                                maxlength="8" style="text-transform: uppercase;">
                                        </div>
                                        <small class="field-hint" id="referralHint"></small>
                                        <small class="field-error" id="referralError"></small>
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

                                    <div id="registerGeneralError"
                                        class="msg msg-error general-message <?= !empty($registerErrorMessage) ? 'show' : '' ?>">
                                        <?= !empty($registerErrorMessage) ? htmlspecialchars($registerErrorMessage, ENT_QUOTES, 'UTF-8') : '' ?>
                                    </div>
                                    <div id="registerGeneralSuccess" class="msg msg-success general-message"></div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Scripts de CSRF para renovação automática -->
    <script src="<?= BASE_URL ?>assets/js/csrf-keep-alive.js"></script>

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

        // ======================
        // CÓDIGO DE INDICAÇÃO
        // ======================

        const referralInput = document.getElementById('referral_code');
        const referralHint = document.getElementById('referralHint');
        const referralError = document.getElementById('referralError');
        let referralValidationTimeout = null;
        let validatedReferralCode = null;

        // Captura o código da URL se existir (?ref=XXXXXXXX)
        function initReferralCode() {
            const urlParams = new URLSearchParams(window.location.search);
            const refCode = urlParams.get('ref');

            if (refCode && referralInput) {
                referralInput.value = refCode.toUpperCase();
                validateReferralCode(refCode);

                // Ativa a aba de cadastro automaticamente se veio com código
                const card = document.querySelector('.card');
                const registerBtn = document.querySelector('.tab-btn[data-tab="register"]');
                if (card && registerBtn) {
                    card.dataset.active = 'register';
                    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('is-active'));
                    registerBtn.classList.add('is-active');
                }
            }
        }

        // Valida o código de indicação via API
        async function validateReferralCode(code) {
            if (!code || code.length < 4) {
                referralHint.textContent = '';
                referralHint.className = 'field-hint';
                referralError.textContent = '';
                validatedReferralCode = null;
                return;
            }

            try {
                const base = document.querySelector('meta[name="base-url"]')?.content || '<?= BASE_URL ?>';
                const response = await fetch(`${base}api/referral/validate?code=${encodeURIComponent(code)}`, {
                    headers: {
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    referralHint.innerHTML = `<i class="fa-solid fa-check"></i> Indicado por <strong>${data.data.referrer_name}</strong> - Você ganha ${data.data.reward_days} dias de PRO!`;
                    referralHint.className = 'field-hint valid';
                    referralError.textContent = '';
                    validatedReferralCode = code;
                } else {
                    referralHint.textContent = '';
                    referralHint.className = 'field-hint';
                    referralError.textContent = data.message || 'Código inválido';
                    validatedReferralCode = null;
                }
            } catch (err) {
                referralHint.textContent = '';
                referralHint.className = 'field-hint';
                referralError.textContent = 'Erro ao validar código';
                validatedReferralCode = null;
            }
        }

        // Evento de input no campo de código
        if (referralInput) {
            referralInput.addEventListener('input', (e) => {
                // Força uppercase
                e.target.value = e.target.value.toUpperCase();

                // Debounce para não fazer muitas requisições
                clearTimeout(referralValidationTimeout);
                referralValidationTimeout = setTimeout(() => {
                    validateReferralCode(e.target.value.trim());
                }, 500);
            });

            // Inicializa se veio código na URL
            initReferralCode();
        }

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
        // MODAL DE VERIFICAÇÃO DE EMAIL
        // ======================
        async function showEmailVerificationModal(email) {
            const result = await Swal.fire({
                icon: 'warning',
                title: 'Verifique seu e-mail',
                html: `
                    <div style="text-align: left;">
                        <p style="margin-bottom: 16px; color: #4b5563;">
                            Para sua segurança, precisamos confirmar que este e-mail é seu antes de liberar o acesso.
                        </p>
                        <div style="background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 8px; padding: 12px 16px; margin-bottom: 16px;">
                            <p style="font-size: 13px; color: #0369a1; margin: 0 0 4px 0;">Enviamos um link para:</p>
                            <p style="font-weight: 600; color: #0c4a6e; margin: 0; word-break: break-all;">${email}</p>
                        </div>
                        <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 12px 16px;">
                            <p style="font-size: 13px; color: #92400e; margin: 0 0 8px 0; font-weight: 600;">💡 Não encontrou?</p>
                            <ul style="font-size: 13px; color: #78350f; margin: 0; padding-left: 16px;">
                                <li>Verifique a pasta de spam</li>
                                <li>Aguarde alguns segundos</li>
                            </ul>
                        </div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: '📧 Reenviar e-mail',
                cancelButtonText: 'Fechar',
                confirmButtonColor: '#e67e22',
                cancelButtonColor: '#6b7280',
                customClass: {
                    popup: 'swal-wide'
                }
            });

            if (result.isConfirmed) {
                await resendVerificationEmail(email);
            }
        }

        async function resendVerificationEmail(email) {
            Swal.fire({
                title: 'Enviando...',
                text: 'Aguarde enquanto reenviamos o e-mail de verificação.',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => Swal.showLoading()
            });

            try {
                const base = document.querySelector('meta[name="base-url"]')?.content || '<?= BASE_URL ?>';
                const resp = await fetch(`${base}verificar-email/reenviar`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: new URLSearchParams({ email })
                });

                const data = await resp.json();

                if (resp.ok) {
                    Swal.fire({
                        icon: 'success',
                        title: 'E-mail enviado! ✉️',
                        html: `
                            <p>Verifique sua caixa de entrada e clique no link para ativar sua conta.</p>
                            <p style="font-size: 13px; color: #666; margin-top: 12px;">O link expira em 24 horas.</p>
                        `,
                        confirmButtonColor: '#27ae60'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Não foi possível enviar',
                        text: data.message || 'Tente novamente em alguns instantes.',
                        confirmButtonColor: '#e74c3c'
                    });
                }
            } catch (err) {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro de conexão',
                    text: 'Não foi possível conectar ao servidor. Tente novamente.',
                    confirmButtonColor: '#e74c3c'
                });
            }
        }

        // ======================
        // FUNÇÕES DE CSRF
        // ======================

        /**
         * Renova o token CSRF para um formulário específico
         */
        async function refreshCsrfForForm(tokenId) {
            try {
                const base = document.querySelector('meta[name="base-url"]')?.content || '<?= BASE_URL ?>';
                const response = await fetch(`${base}api/csrf/refresh`, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        token_id: tokenId
                    })
                });

                const data = await response.json();

                if (data.token) {
                    // Atualizar meta tag
                    const metaTag = document.querySelector('meta[name="csrf-token"]');
                    if (metaTag) metaTag.content = data.token;

                    // Atualizar inputs hidden do formulário
                    document.querySelectorAll(`input[name="csrf_token"]`).forEach(input => {
                        // Verificar se o input pertence ao formulário correto
                        const formId = input.closest('form')?.id;
                        if (tokenId === 'login_form' && formId === 'loginForm') {
                            input.value = data.token;
                        } else if (tokenId === 'register_form' && formId === 'registerForm') {
                            input.value = data.token;
                        }
                    });

                    return data.token;
                }
                throw new Error('Token não recebido');
            } catch (err) {
                console.error('Erro ao renovar CSRF:', err);
                throw err;
            }
        }

        /**
         * Verifica se o erro é relacionado a CSRF expirado
         */
        function isCsrfError(response, data) {
            if (response.status === 419) return true;
            if (response.status === 403 && data?.errors?.csrf_token) return true;
            if (data?.csrf_expired === true) return true;
            const msg = String(data?.message || '').toLowerCase();
            return msg.includes('csrf') || msg.includes('token');
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

                // Flag para evitar loop infinito de retry
                let hasRetried = false;

                async function attemptLogin() {
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

                    // Se for erro de CSRF e ainda não tentamos renovar, renova e tenta de novo
                    if (isCsrfError(response, data) && !hasRetried) {
                        hasRetried = true;
                        console.log('[Login] Token CSRF expirado, renovando...');
                        try {
                            await refreshCsrfForForm('login_form');
                            // Tenta novamente após renovar
                            return attemptLogin();
                        } catch (refreshErr) {
                            // Falha na renovação, mostra erro de sessão
                            return {
                                response,
                                data: {
                                    success: false,
                                    message: 'Sessão expirada. Por favor, recarregue a página e tente novamente.'
                                }
                            };
                        }
                    }

                    return {
                        response,
                        data
                    };
                }

                try {
                    const {
                        response,
                        data
                    } = await attemptLogin();

                    const payload = (data && typeof data.data === 'object') ? data.data : {};
                    const success = data && (data.success === true || data.status === 'success');

                    if (!response.ok || !success) {
                        // Verifica se é erro de email não verificado
                        if (data && data.errors && data.errors.email_not_verified) {
                            const userEmail = data.errors.user_email || emailVal;
                            showEmailVerificationModal(userEmail);
                            btn.disabled = false;
                            btn.innerHTML = originalBtnHtml;
                            return;
                        }

                        // Mensagem especial para erro de CSRF após retry
                        let message;
                        if (isCsrfError(response, data)) {
                            message = 'Sessão expirada. A página será recarregada...';
                            setTimeout(() => window.location.reload(), 1500);
                        } else {
                            message = (data && data.message) ||
                                (response.status === 429 ?
                                    'Muitas tentativas. Aguarde um pouco e tente novamente.' :
                                    'E-mail ou senha inválidos.');
                        }

                        if (generalError) {
                            generalError.textContent = message;
                            generalError.classList.add('show');
                        }

                        // Não exibe erro de campo se for erro de email não verificado
                        // (já foi tratado acima com o modal)
                        if (data && data.errors && typeof data.errors === 'object' && !data.errors.email_not_verified) {
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
            registerForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                clearErrors(registerForm);

                const name = document.getElementById('name').value.trim();
                const email = document.getElementById('reg_email').value.trim();
                const password = document.getElementById('reg_password').value;
                const confirm = document.getElementById('reg_password_confirm').value;

                let hasError = false;

                // Validações
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

                const btn = registerForm.querySelector('.btn-primary');
                const originalBtnHtml = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<span>Criando conta...</span>';

                // Flag para evitar loop infinito de retry
                let hasRetried = false;

                async function attemptRegister() {
                    const formData = new FormData(registerForm);

                    const response = await fetch(registerForm.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    let data = null;
                    try {
                        data = await response.json();
                    } catch (e) {
                        // Se não veio JSON
                    }

                    // Se for erro de CSRF e ainda não tentamos renovar, renova e tenta de novo
                    if (isCsrfError(response, data) && !hasRetried) {
                        hasRetried = true;
                        console.log('[Register] Token CSRF expirado, renovando...');
                        try {
                            await refreshCsrfForForm('register_form');
                            return attemptRegister();
                        } catch (refreshErr) {
                            throw new Error('Sessão expirada. Por favor, recarregue a página.');
                        }
                    }

                    return {
                        response,
                        data
                    };
                }

                try {
                    const {
                        response,
                        data
                    } = await attemptRegister();

                    if (!response.ok || data?.success !== true) {
                        // Mensagem especial para erro de CSRF
                        if (isCsrfError(response, data)) {
                            throw new Error('Sessão expirada. A página será recarregada...');
                        }
                        throw new Error(data?.message || 'Erro ao criar conta.');
                    }

                    Swal.fire({
                        icon: 'success',
                        title: 'Conta criada com sucesso!',
                        text: data.message || 'Agora você pode fazer login.',
                        timer: 2000,
                        showConfirmButton: false
                    });

                    setTimeout(() => {
                        window.location.href = data.redirect || '<?= BASE_URL ?>login';
                    }, 2000);

                } catch (err) {
                    const message = err.message || 'Erro ao criar conta.';

                    // Se a mensagem indicar CSRF expirado, recarrega a página
                    if (message.toLowerCase().includes('sessão expirada') || message.toLowerCase().includes('csrf')) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Sessão expirada',
                            text: 'A página será recarregada...',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        setTimeout(() => window.location.reload(), 1500);
                        return;
                    }

                    // Se a mensagem indicar sucesso, mostre o modal de sucesso
                    if (message.toLowerCase().includes('sucesso')) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Conta criada com sucesso!',
                            text: message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                        setTimeout(() => {
                            window.location.href = '<?= BASE_URL ?>login';
                        }, 2000);
                        return;
                    }

                    Swal.fire({
                        icon: 'error',
                        title: 'Não foi possível criar a conta',
                        text: message
                    });
                } finally {
                    btn.disabled = false;
                    btn.innerHTML = originalBtnHtml;
                }

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