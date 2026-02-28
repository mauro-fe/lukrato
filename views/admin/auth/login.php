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
    <meta name="csrf-token-register"
        content="<?= htmlspecialchars(csrf_token('register_form'), ENT_QUOTES, 'UTF-8') ?>">

    <title>Login / Cadastro - Lukrato</title>
    <!-- Lucide Icons + FA Brands -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/brands.min.css">
    <link rel="stylesheet" href="<?= rtrim(BASE_URL, '/') ?>/assets/css/vendor/lucide-compat.css">
    <script src="<?= rtrim(BASE_URL, '/') ?>/assets/js/lucide.min.js"></script>
    <?php loadPageCss('admin-admins-login'); ?>
    <style>
        /* ── Password Strength Panel ── */
        .pwd-strength {
            display: none;
            margin-top: 10px;
            border-radius: 12px;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.04) 0%, rgba(255, 255, 255, 0.015) 100%);
            border: 1px solid rgba(255, 255, 255, 0.07);
            padding: 14px 16px 12px;
            animation: pwd-fade-in 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            margin-bottom: 6px;
        }

        @keyframes pwd-fade-in {
            from {
                opacity: 0;
                transform: translateY(-6px) scale(0.98);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .pwd-strength.visible {
            display: block;
        }

        /* Strength bar */
        .pwd-bar-label {
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 0.6px;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .pwd-bar-label span:first-child {
            color: rgba(255, 255, 255, 0.3);
            text-transform: uppercase;
        }

        .pwd-bar-label .pwd-level {
            transition: color 0.3s;
            font-weight: 700;
        }

        .pwd-level.s1 {
            color: #ef4444;
        }

        .pwd-level.s2 {
            color: #f97316;
        }

        .pwd-level.s3 {
            color: #eab308;
        }

        .pwd-level.s4 {
            color: #22d3ee;
        }

        .pwd-level.s5 {
            color: #22c55e;
        }

        .pwd-bar-wrap {
            height: 6px;
            border-radius: 3px;
            background: rgba(255, 255, 255, 0.06);
            margin-bottom: 14px;
            overflow: hidden;
        }

        .pwd-bar-fill {
            height: 100%;
            border-radius: 3px;
            width: 0%;
            transition: width 0.5s cubic-bezier(0.16, 1, 0.3, 1), background 0.4s ease, box-shadow 0.4s ease;
        }

        .pwd-bar-fill.s1 {
            width: 20%;
            background: #ef4444;
            box-shadow: 0 0 8px rgba(239, 68, 68, 0.4);
        }

        .pwd-bar-fill.s2 {
            width: 40%;
            background: #f97316;
            box-shadow: 0 0 8px rgba(249, 115, 22, 0.4);
        }

        .pwd-bar-fill.s3 {
            width: 60%;
            background: #eab308;
            box-shadow: 0 0 8px rgba(234, 179, 8, 0.3);
        }

        .pwd-bar-fill.s4 {
            width: 80%;
            background: #22d3ee;
            box-shadow: 0 0 8px rgba(34, 211, 238, 0.3);
        }

        .pwd-bar-fill.s5 {
            width: 100%;
            background: linear-gradient(90deg, #22d3ee, #22c55e);
            box-shadow: 0 0 12px rgba(34, 197, 94, 0.4);
        }

        /* Divider line */
        .pwd-divider {
            height: 1px;
            background: rgba(255, 255, 255, 0.06);
            margin-bottom: 12px;
        }

        /* Requirements grid */
        .pwd-reqs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px 16px;
        }

        .pwd-req {
            font-size: 0.7rem;
            color: rgba(255, 255, 255, 0.3);
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            padding: 3px 0;
        }

        .pwd-req .req-icon {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            border: 1.5px solid rgba(255, 255, 255, 0.12);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
        }

        .pwd-req.pass {
            color: rgba(255, 255, 255, 0.7);
        }

        .pwd-req.pass .req-icon {
            background: #22c55e;
            border-color: #22c55e;
            box-shadow: 0 0 8px rgba(34, 197, 94, 0.35);
            transform: scale(1.05);
        }

        .pwd-req.pass .req-icon::after {
            content: '';
            display: block;
            width: 5px;
            height: 8px;
            border-right: 1.5px solid #fff;
            border-bottom: 1.5px solid #fff;
            transform: rotate(45deg);
            margin-top: -1px;
        }

        /* ── Confirm match indicator ── */
        .pwd-match {
            display: none;
            align-items: center;
            gap: 8px;
            margin-top: 8px;
            padding: 6px 10px;
            border-radius: 8px;
            font-size: 0.72rem;
            font-weight: 600;
            animation: pwd-fade-in 0.25s ease;
        }

        .pwd-match.visible {
            display: flex;
        }

        .pwd-match .match-icon {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.55rem;
            flex-shrink: 0;
        }

        .pwd-match.match {
            color: #22c55e;
            background: rgba(34, 197, 94, 0.08);
            border: 1px solid rgba(34, 197, 94, 0.15);
        }

        .pwd-match.match .match-icon {
            background: #22c55e;
            color: #fff;
            box-shadow: 0 0 6px rgba(34, 197, 94, 0.3);
        }

        .pwd-match.no-match {
            color: #f87171;
            background: rgba(239, 68, 68, 0.08);
            border: 1px solid rgba(239, 68, 68, 0.15);
        }

        .pwd-match.no-match .match-icon {
            background: #ef4444;
            color: #fff;
            box-shadow: 0 0 6px rgba(239, 68, 68, 0.3);
        }

        @media (max-width: 400px) {
            .pwd-reqs {
                grid-template-columns: 1fr;
            }

            .pwd-strength {
                padding: 12px;
            }
        }
    </style>
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
                                            <i data-lucide="eye"></i>
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
                                            placeholder="Senha" required>
                                        <button type="button" class="toggle-password" data-target="reg_password">
                                            <i data-lucide="eye"></i>
                                        </button>
                                        <div class="pwd-strength" id="pwdStrength">
                                            <div class="pwd-bar-label">
                                                <span>Força da senha</span>
                                                <span class="pwd-level" id="pwdLevel"></span>
                                            </div>
                                            <div class="pwd-bar-wrap">
                                                <div class="pwd-bar-fill" id="pwdBarFill"></div>
                                            </div>
                                            <div class="pwd-divider"></div>
                                            <div class="pwd-reqs">
                                                <div class="pwd-req" id="req-length"><span class="req-icon"></span> 8+ caracteres</div>
                                                <div class="pwd-req" id="req-lower"><span class="req-icon"></span> Letra minúscula</div>
                                                <div class="pwd-req" id="req-upper"><span class="req-icon"></span> Letra maiúscula</div>
                                                <div class="pwd-req" id="req-number"><span class="req-icon"></span> Número</div>
                                                <div class="pwd-req" id="req-special"><span class="req-icon"></span> Caractere especial</div>
                                            </div>
                                        </div>
                                    </div>
                                    <small class="field-error" id="regPasswordError"></small>

                                    <div class="field">
                                        <input type="password" id="reg_password_confirm" name="password_confirmation"
                                            placeholder="Confirmar senha" required>
                                        <button type="button" class="toggle-password"
                                            data-target="reg_password_confirm">
                                            <i data-lucide="eye"></i>
                                        </button>
                                        <div class="pwd-match" id="pwdMatch">
                                            <span class="match-icon"><i data-lucide="check"></i></span>
                                            <span class="match-text"></span>
                                        </div>
                                        <small class="field-error" id="regPasswordConfirmError"></small>

                                    </div>

                                    <div class="field referral-field">
                                        <div class="input-with-icon">
                                            <i data-lucide="gift" class="referral-icon"></i>
                                            <input type="text" id="referral_code" name="referral_code"
                                                placeholder="Código de indicação (opcional)" maxlength="8"
                                                style="text-transform: uppercase;">
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

    <!-- CSRF renovação automática (integrado ao csrf-manager.js) -->

    <!-- Renovação extra de CSRF para página de login -->
    <script>
        (function() {
            'use strict';

            // Renovação mais frequente na página de login (5 minutos)
            const LOGIN_CSRF_REFRESH_INTERVAL = 5 * 60 * 1000;

            // Renovar token quando usuário foca em campo do formulário
            let lastRefresh = Date.now();
            const MIN_REFRESH_GAP = 30000; // 30 segundos mínimo entre renovações

            function maybeRefreshToken() {
                const now = Date.now();
                if (now - lastRefresh > MIN_REFRESH_GAP) {
                    lastRefresh = now;
                    if (typeof window.refreshCsrfToken === 'function') {
                        window.refreshCsrfToken();
                    }
                }
            }

            // Renovar ao focar nos campos
            document.querySelectorAll('#loginForm input, #registerForm input').forEach(input => {
                input.addEventListener('focus', maybeRefreshToken, {
                    passive: true
                });
            });

            // Timer extra de renovação para login
            setInterval(() => {
                if (typeof window.refreshCsrfToken === 'function') {
                    window.refreshCsrfToken();
                }
            }, LOGIN_CSRF_REFRESH_INTERVAL);

            // Renovar imediatamente quando a aba fica visível
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible') {
                    maybeRefreshToken();
                }
            });
        })();
    </script>

    <script>
        // Real-time password strength + confirm match
        (function() {
            var pwd = document.getElementById('reg_password');
            var confirm = document.getElementById('reg_password_confirm');
            var panel = document.getElementById('pwdStrength');
            var barFill = document.getElementById('pwdBarFill');
            var levelEl = document.getElementById('pwdLevel');
            var matchEl = document.getElementById('pwdMatch');
            if (!pwd || !confirm || !panel) return;

            var rules = [{
                    id: 'req-length',
                    test: function(v) {
                        return v.length >= 8;
                    }
                },
                {
                    id: 'req-lower',
                    test: function(v) {
                        return /[a-z]/.test(v);
                    }
                },
                {
                    id: 'req-upper',
                    test: function(v) {
                        return /[A-Z]/.test(v);
                    }
                },
                {
                    id: 'req-number',
                    test: function(v) {
                        return /[0-9]/.test(v);
                    }
                },
                {
                    id: 'req-special',
                    test: function(v) {
                        return /[^a-zA-Z0-9]/.test(v);
                    }
                }
            ];

            var levels = [{
                    cls: '',
                    label: ''
                },
                {
                    cls: 's1',
                    label: 'Muito fraca'
                },
                {
                    cls: 's2',
                    label: 'Fraca'
                },
                {
                    cls: 's3',
                    label: 'Razoável'
                },
                {
                    cls: 's4',
                    label: 'Boa'
                },
                {
                    cls: 's5',
                    label: 'Forte'
                }
            ];

            pwd.addEventListener('focus', function() {
                panel.classList.add('visible');
            });

            pwd.addEventListener('input', function() {
                var val = pwd.value;
                var score = 0;

                rules.forEach(function(rule) {
                    var el = document.getElementById(rule.id);
                    var passed = rule.test(val);
                    if (el) el.classList.toggle('pass', passed);
                    if (passed) score++;
                });

                // Update bar
                barFill.className = 'pwd-bar-fill' + (score > 0 ? ' s' + score : '');
                levelEl.className = 'pwd-level' + (score > 0 ? ' s' + score : '');
                levelEl.textContent = levels[score].label;

                // Also update confirm match if confirm has value
                if (confirm.value) checkMatch();
            });

            function checkMatch() {
                var pVal = pwd.value;
                var cVal = confirm.value;
                if (!cVal) {
                    matchEl.classList.remove('visible');
                    return;
                }
                matchEl.classList.add('visible');
                var ok = pVal === cVal;
                matchEl.classList.toggle('match', ok);
                matchEl.classList.toggle('no-match', !ok);
                var icon = matchEl.querySelector('.match-icon');
                var text = matchEl.querySelector('.match-text');
                icon.innerHTML = ok ? '<i data-lucide="check"></i>' : '<i data-lucide="x"></i>';
                text.textContent = ok ? 'Senhas coincidem' : 'Senhas não coincidem';
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }

            confirm.addEventListener('input', checkMatch);
            pwd.addEventListener('input', function() {
                if (confirm.value) checkMatch();
            });
        })();
    </script>

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
                updateGoogleRegisterLink();

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

        // Atualiza o link do Google Register com o código de indicação
        function updateGoogleRegisterLink() {
            const googleRegisterBtn = document.querySelector('a[href*="auth/google/register"]');
            if (!googleRegisterBtn) return;
            const base = '<?= BASE_URL ?>auth/google/register';
            const code = referralInput ? referralInput.value.trim() : '';
            googleRegisterBtn.href = code ? `${base}?ref=${encodeURIComponent(code)}` : base;
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
                    referralHint.innerHTML =
                        `<i data-lucide="check"></i> Indicado por <strong>${data.data.referrer_name}</strong> - Você ganha ${data.data.reward_days} dias de PRO!`;
                    referralHint.className = 'field-hint valid';
                    referralError.textContent = '';
                    validatedReferralCode = code;
                    if (typeof lucide !== 'undefined') lucide.createIcons();
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
                updateGoogleRegisterLink();

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

            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';

            const oldIcon = btn.querySelector('svg, i');
            if (oldIcon) {
                const newIcon = document.createElement('i');
                newIcon.setAttribute('data-lucide', isPassword ? 'eye-off' : 'eye');
                oldIcon.replaceWith(newIcon);
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }
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
            // Renovar CSRF antes de submeter para evitar token expirado
            loginForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                clearErrors(loginForm);

                // Sempre renovar o token CSRF antes de submeter
                try {
                    await refreshCsrfForForm('login_form');
                } catch (err) {
                    console.warn('Não foi possível renovar CSRF antes do submit, continuando...', err);
                }

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
                } else {
                    var pwdErrors = [];
                    if (password.length < 8) pwdErrors.push('mínimo 8 caracteres');
                    if (!/[a-z]/.test(password)) pwdErrors.push('uma letra minúscula');
                    if (!/[A-Z]/.test(password)) pwdErrors.push('uma letra maiúscula');
                    if (!/[0-9]/.test(password)) pwdErrors.push('um número');
                    if (!/[^a-zA-Z0-9]/.test(password)) pwdErrors.push('um caractere especial');
                    if (pwdErrors.length) {
                        showError('reg_password', 'regPasswordError', 'Falta: ' + pwdErrors.join(', '));
                        hasError = true;
                    }
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

                        // Erros de validação (422) — mostra erros específicos nos campos
                        if (response.status === 422 && data?.errors) {
                            if (data.errors.email) {
                                const emailMsg = Array.isArray(data.errors.email) ? data.errors.email[0] : data
                                    .errors.email;
                                showError('reg_email', 'regEmailError', emailMsg);
                            }
                            if (data.errors.name) {
                                const nameMsg = Array.isArray(data.errors.name) ? data.errors.name[0] : data
                                    .errors.name;
                                showError('name', 'nameError', nameMsg);
                            }
                            if (data.errors.password) {
                                const passMsg = Array.isArray(data.errors.password) ? data.errors.password[0] :
                                    data.errors.password;
                                showError('reg_password', 'regPasswordError', passMsg);
                            }
                            if (data.errors.password_confirmation) {
                                const confirmMsg = Array.isArray(data.errors.password_confirmation) ? data
                                    .errors.password_confirmation[0] : data.errors.password_confirmation;
                                showError('reg_password_confirm', 'regPasswordConfirmError', confirmMsg);
                            }

                            // Título contextual para o SweetAlert
                            const apiMessage = data.message || '';
                            const isEmailDuplicate = apiMessage.toLowerCase().includes('cadastrado') ||
                                apiMessage.toLowerCase().includes('já existe') ||
                                (data.errors.email && String(data.errors.email).toLowerCase().includes(
                                    'cadastrado'));

                            Swal.fire({
                                icon: isEmailDuplicate ? 'warning' : 'error',
                                title: isEmailDuplicate ? 'E-mail já cadastrado' : 'Erro no cadastro',
                                text: isEmailDuplicate ?
                                    'Já existe uma conta com este e-mail. Tente fazer login ou use outro e-mail.' : (apiMessage || 'Corrija os campos destacados e tente novamente.'),
                            });

                            btn.disabled = false;
                            btn.innerHTML = originalBtnHtml;
                            return;
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
                    if (message.toLowerCase().includes('sessão expirada') || message.toLowerCase().includes(
                            'csrf')) {
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

    <script src="<?= rtrim(BASE_URL, '/') ?>/assets/js/lucide-init.js"></script>
</body>

</html>