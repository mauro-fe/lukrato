<?php loadPageCss(); ?>
<style>
.lukrato-auth {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 32px 16px;
}

.login-wrapper {
    width: min(1100px, 100%);
    display: grid;
    grid-template-columns: 1.05fr 0.95fr;
    gap: 32px;
    border-radius: 20px;
    box-shadow: 0 25px 80px rgba(12, 24, 64, 0.08);
    overflow: hidden;
}

.login-left {
    padding: 32px;
    background: linear-gradient(135deg, #0f172a, #1d253a);
    color: #fff;
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 24px;
}

.login-left .imagem-logo img {
    width: 180px;
    max-width: 70%;
    height: auto;
}

.login-right {
    padding: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.auth-tabs-card {
    width: 100%;
}

.tabs {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    border-radius: 12px;
    padding: 6px;
}

.tabs .tab-btn {
    border: none;
    background: transparent;
    padding: 12px;
    border-radius: 10px;
    font-weight: 600;
    color: #4b5563;
    transition: all 0.2s ease;
}

.tabs .tab-btn.is-active {
    background: #fff;
    color: #111827;
    box-shadow: 0 6px 16px rgba(15, 23, 42, 0.1);
}

.tab-panel {
    margin-top: 18px;
}

.field input {
    width: 100%;
    height: 46px;
    border-radius: 10px;
    border: 1px solid #d6d9e0;
    padding: 0 14px;
    font-size: 15px;
}

.field+.field {
    margin-top: 14px;
}

.btn.btn-primary {
    width: 100%;
    justify-content: center;
    gap: 6px;
    height: 48px;
    font-size: 15px;
}

.extra-link {
    margin-top: 12px;
    text-align: center;
}

.general-message,
#registerGeneralError,
#registerGeneralSuccess {
    margin-top: 12px;
}

/* Mobile-first refinements */
@media (max-width: 960px) {
    .lukrato-auth {
        padding: 20px 12px 28px;
    }

    .login-wrapper {
        grid-template-columns: 1fr;
        border-radius: 16px;
    }

    .login-left {
        padding: 20px;
        gap: 12px;
    }

    .login-left .welcome p {
        font-size: 0.95rem;
        line-height: 1.45;
        opacity: 0.9;
    }

    .login-right {
        padding: 20px;
    }

    .tabs .tab-btn {
        font-size: 0.95rem;
    }
}

@media (max-width: 600px) {
    .lukrato-auth {
        padding: 12px;
    }

    .login-left {
        display: none;
    }

    .login-wrapper {
        box-shadow: 0 14px 36px rgba(15, 23, 42, 0.12);
        border: 1px solid #e5e7eb;
    }

    .login-right {
        padding: 18px;
    }

    .field input {
        height: 44px;
        font-size: 14px;
    }

    .btn.btn-primary {
        height: 46px;
    }
}

.field {
    position: relative;
}

.field .toggle-password {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    background: transparent;
    border: none;
    padding: 4px;
    cursor: pointer;
    color: #666;
}

.field .toggle-password:focus {
    outline: 2px solid #7aa7ff;
    outline-offset: 2px;
}

/* Ensure room for the icon inside the input */
.field input[type="password"],
.field input[type="text"].is-password-visible {
    padding-right: 40px;
}

.field .toggle-password svg {
    width: 20px;
    height: 20px;
    display: block;
}
</style>
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/variables.css">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/components.css">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/main-styles.css">

<main class="lukrato-auth">
    <div class="login-wrapper">
        <section class="login-left">
            <div class="brand">
                <div class="imagem-logo" aria-hidden="true">
                    <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="Lukrato" />
                </div>
            </div>

            <header class="welcome">
                <h2>Bem-vindo ao Lukrato</h2>
                <p>
                    Acompanhe saldos, investimentos e objetivos em um único lugar.
                    Entre para gerenciar suas finanças com segurança e performance.
                </p>
            </header>
        </section>

        <section class="login-right">
            <div class="card auth-tabs-card" data-active="login">
                <div class="tabs">
                    <button class="tab-btn is-active" data-tab="login" type="button">Entrar</button>
                    <button class="tab-btn" data-tab="register" type="button">Cadastrar</button>
                </div>

                <div class="tab-panel" id="tab-login" role="tabpanel" aria-labelledby="btn-login">
                    <h3 class="card-title">Entrar</h3>

                    <form action="<?= BASE_URL ?>login/entrar" method="POST" id="loginForm" novalidate>
                        <?= csrf_input('login_form') ?>

                        <div class="field">
                            <input type="email" id="email" name="email" placeholder="E-mail" autocomplete="email"
                                required />
                            <small class="field-error" id="emailError"></small>
                        </div>

                        <div class="field">
                            <input type="password" id="password" name="password" placeholder="Senha"
                                autocomplete="current-password" required>
                            <button type="button" class="toggle-password" aria-label="Mostrar senha"
                                data-target="password" title="Mostrar/ocultar senha">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                            </button>
                            <small class="field-error" id="passwordError"></small>
                        </div>

                        <button type="submit" id="submitBtn" class="btn btn-primary ">
                            <span">Entrar</span>
                        </button>

                        <p class=" extra-link">
                            <a href="<?= BASE_URL ?>recuperar-senha">Esqueceu a senha?</a>
                        </p>

                        <div id="generalError" class="msg msg-error general-message" aria-live="polite"></div>
                        <div id="generalSuccess" class="msg msg-success general-message" aria-live="polite">
                        </div>
                    </form>
                </div>

                <div class="tab-panel is-hidden" id="tab-register" role="tabpanel" aria-labelledby="btn-register">
                    <h3 class="card-title">Cadastrar</h3>

                    <form action="<?= BASE_URL ?>register/criar" method="POST" id="registerForm" novalidate>
                        <?= csrf_input('register_form') ?>

                        <div class="field">
                            <input type="text" id="name" name="name" placeholder="Nome completo" autocomplete="name"
                                required />
                            <small class="field-error" id="nameError"></small>
                        </div>

                        <div class="field">
                            <input type="email" id="reg_email" name="email" placeholder="E-mail" autocomplete="email"
                                required />
                            <small class="field-error" id="regEmailError"></small>
                        </div>

                        <div class="field">
                            <input type="password" id="reg_password" name="password" placeholder="Senha"
                                autocomplete="new-password" required />
                            <button type="button" class="toggle-password" aria-label="Mostrar senha"
                                data-target="reg_password" title="Mostrar/ocultar senha">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                            </button>
                            <small class="field-error" id="regPasswordError"></small>
                        </div>

                        <div class="field">
                            <input type="password" id="reg_password_confirm" name="password_confirmation"
                                placeholder="Confirmar senha" autocomplete="new-password" required />
                            <button type="button" class="toggle-password" aria-label="Mostrar senha"
                                data-target="reg_password_confirm" title="Mostrar/ocultar senha">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                            </button>
                            <small class="field-error" id="regPasswordConfirmError"></small>
                        </div>

                        <button type="submit" class="btn btn-primary "><span>Criar
                                conta</span></button>

                        <div id="registerGeneralError" class="msg msg-error" aria-live="polite"></div>
                        <div id="registerGeneralSuccess" class="msg msg-success" aria-live="polite"></div>
                    </form>
                </div>
            </div>
        </section>
    </div>
</main>

<?php loadPageJs(); ?>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= BASE_URL ?>assets/js/csrf-keep-alive.js" defer></script>
<script src="<?= BASE_URL ?>/assets/js/admin-admins-login.js" defer></script>

<script>
window.BASE_URL = <?= json_encode(rtrim(BASE_URL, '/') . '/') ?>;
window.LK = window.LK || {};
window.LK.csrfTtl = <?= (int) \Application\Middlewares\CsrfMiddleware::TOKEN_TTL ?>;
</script>
<script>
(function() {
    function toggleVisibility(input) {
        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        input.classList.toggle('is-password-visible', isPassword);
    }

    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.toggle-password');
        if (!btn) return;
        const targetId = btn.getAttribute('data-target');
        const input = document.getElementById(targetId);
        if (!input) return;
        toggleVisibility(input);
    });
})();
</script>