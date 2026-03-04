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
    <?php loadPageCss('admin-auth-login'); ?>

    <!-- Cloudflare Turnstile (carrega só se configurado) -->
    <?php if (!empty($turnstile_site_key)): ?>
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit" async defer></script>
        <meta name="turnstile-site-key" content="<?= htmlspecialchars($turnstile_site_key, ENT_QUOTES, 'UTF-8') ?>">
        <meta name="turnstile-required" content="<?= !empty($require_captcha) ? '1' : '0' ?>">
        <style>
            .turnstile-wrapper {
                margin: 12px 0;
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 6px;
                animation: fadeInCaptcha 0.3s ease;
            }

            @keyframes fadeInCaptcha {
                from {
                    opacity: 0;
                    transform: translateY(-8px);
                }

                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
        </style>
    <?php endif; ?>

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
                                        <input type="email" id="email" name="email" placeholder="E-mail"
                                            aria-label="E-mail" required>
                                        <small class="field-error" id="emailError"></small>
                                    </div>

                                    <div class="field">
                                        <input type="password" id="password" name="password" placeholder="Senha"
                                            aria-label="Senha" required>
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

                                    <!-- Turnstile CAPTCHA (progressivo — oculto até atingir threshold de falhas) -->
                                    <div id="loginTurnstileWrapper" class="turnstile-wrapper" style="display:none;">
                                        <div id="loginTurnstileWidget"></div>
                                        <small class="field-error" id="captchaError"></small>
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
                                        <input type="text" id="name" name="name" placeholder="Nome completo"
                                            aria-label="Nome completo" required>
                                        <small class="field-error" id="nameError"></small>
                                    </div>

                                    <div class="field">
                                        <input type="email" id="reg_email" name="email" placeholder="E-mail"
                                            aria-label="E-mail" required>
                                        <small class="field-error" id="regEmailError"></small>
                                    </div>

                                    <div class="field">
                                        <input type="password" id="reg_password" name="password" placeholder="Senha"
                                            aria-label="Senha" required>
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
                                                <div class="pwd-req" id="req-length"><span class="req-icon"></span> 8+
                                                    caracteres</div>
                                                <div class="pwd-req" id="req-lower"><span class="req-icon"></span> Letra
                                                    minúscula</div>
                                                <div class="pwd-req" id="req-upper"><span class="req-icon"></span> Letra
                                                    maiúscula</div>
                                                <div class="pwd-req" id="req-number"><span class="req-icon"></span>
                                                    Número</div>
                                                <div class="pwd-req" id="req-special"><span class="req-icon"></span>
                                                    Caractere especial</div>
                                            </div>
                                        </div>
                                    </div>
                                    <small class="field-error" id="regPasswordError"></small>

                                    <div class="field">
                                        <input type="password" id="reg_password_confirm" name="password_confirmation"
                                            placeholder="Confirmar senha" aria-label="Confirmar senha" required>
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

                                    <!-- Turnstile CAPTCHA no registro (sempre visível se configurado) -->
                                    <div id="registerTurnstileWrapper" class="turnstile-wrapper" style="display:none;">
                                        <div id="registerTurnstileWidget"></div>
                                        <small class="field-error" id="regCaptchaError"></small>
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
    <?= vite_scripts('admin/auth/login/index.js') ?>
</body>

</html>