<?php loadPageCss(); ?>
<style>
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

/* Aproxima o conteúdo do topo em telas pequenas */
@media (max-width: 768px) {
    main.lukrato-auth {
        align-items: flex-start;
        padding: 18px 12px 28px;
    }

    .login-wrapper {
        justify-content: center;
        margin-top: 10px;
        gap: 16px;
    }
}
</style>
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/variables.css">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/components.css">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/main-styles.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css">


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
                <div class="flip-container">
                    <div class="flip-inner">

                        <!-- FACE DA FRENTE = LOGIN -->
                        <div class="flip-face flip-login tab-panel" id="tab-login" role="tabpanel"
                            aria-labelledby="btn-login">
                            <h3 class="card-title">Entrar</h3>

                            <!-- TODO o seu formulário de login permanece igual -->
                            <form action="<?= BASE_URL ?>login/entrar" method="POST" id="loginForm" novalidate">
                                <?= csrf_input('login_form') ?>

                                <div class="field">
                                    <input type="email" id="email" name="email" placeholder="E-mail"
                                        autocomplete="email" required />
                                    <small class="field-error" id="emailError"></small>
                                </div>

                                <div class="field">
                                    <input type="password" id="password" name="password" placeholder="Senha" required>
                                    <button type="button" class="toggle-password" data-target="password">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                    <small class="field-error" id="passwordError"></small>
                                </div>

                                <button type="submit" id="submitBtn" class="btn btn-primary">
                                    <span>Entrar</span>
                                </button>

                                <div class="auth-separator"><span>ou</span></div>

                                <div class="google-sign-in-container">
                                    <a href="<?= BASE_URL ?>auth/google/login" class="google-sign-in-button">
                                        <i class="fa-brands fa-google google-icon"></i>
                                        <span class="button-text">Entrar com Google</span>
                                    </a>
                                </div>

                                <p class="extra-link">
                                    <a href="<?= BASE_URL ?>recuperar-senha">Esqueceu a senha?</a>
                                </p>

                                <div id="generalError" class="msg msg-error general-message"></div>
                                <div id="generalSuccess" class="msg msg-success general-message">
                                    <?php if (!empty($success)): ?>
                                    <?= htmlspecialchars($success) ?>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>

                        <!-- FACE DE TRÁS = CADASTRO -->
                        <div class="flip-face flip-register tab-panel" id="tab-register" role="tabpanel"
                            aria-labelledby="btn-register">
                            <h3 class="card-title">Cadastrar</h3>

                            <!-- TODO o seu formulário de cadastro permanece igual -->
                            <form action="<?= BASE_URL ?>register/criar" method="POST" id="registerForm" novalidate>
                                <?= csrf_input('register_form') ?>

                                <div class="field">
                                    <input type="text" id="name" name="name" placeholder="Nome completo" required />
                                    <small class="field-error" id="nameError"></small>
                                </div>

                                <div class="field">
                                    <input type="email" id="reg_email" name="email" placeholder="E-mail" required />
                                    <small class="field-error" id="regEmailError"></small>
                                </div>

                                <div class="field">
                                    <input type="password" id="reg_password" name="password" placeholder="Senha"
                                        required />

                                    <button type="button" class="toggle-password" data-target="reg_password">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>

                                    <small class="field-error" id="regPasswordError"></small>
                                </div>

                                <div class="field">
                                    <input type="password" id="reg_password_confirm" name="password_confirmation"
                                        placeholder="Confirmar senha" required />

                                    <button type="button" class="toggle-password" data-target="reg_password_confirm">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>

                                    <small class="field-error" id="regPasswordConfirmError"></small>
                                </div>

                                <button type="submit" class="btn btn-primary"><span>Criar conta</span></button>

                                <div class="auth-separator"><span>ou</span></div>

                                <div class="google-sign-in-container">
                                    <a href="<?= BASE_URL ?>auth/google/register" class="google-sign-in-button">
                                        <i class="fa-brands fa-google google-icon"></i>
                                        <span class="button-text">Cadastrar com Google</span>
                                    </a>
                                </div>

                                <div id="registerGeneralError" class="msg msg-error"></div>
                                <div id="registerGeneralSuccess" class="msg msg-success"></div>
                            </form>
                        </div>

                    </div>
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

<?php if (!empty($success)): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
        icon: 'success',
        title: 'Senha alterada com sucesso!',
        text: 'Redirecionando para login...',
        timer: 2200,
        showConfirmButton: false,
    });
});
</script>
<?php endif; ?>

<script>
window.BASE_URL = <?= json_encode(rtrim(BASE_URL, '/') . '/') ?>;

window.LK = window.LK || {};
window.LK.csrfTtl = <?= (int) \Application\Middlewares\CsrfMiddleware::TOKEN_TTL ?>;
window.BASE_URL = <?= json_encode(rtrim(BASE_URL, '/') . '/') ?>;
window.LK = window.LK || {};
window.LK.csrfTtl = <?= (int) \Application\Middlewares\CsrfMiddleware::TOKEN_TTL ?>;
</script>
<script>
(function() {

    function toggleVisibility(input, icon) {
        const isPassword = input.type === 'password';

        // Alterna o tipo
        input.type = isPassword ? 'text' : 'password';
        input.classList.toggle('is-password-visible', isPassword);

        // Troca o ícone Font Awesome
        icon.classList.toggle('fa-eye', !isPassword);
        icon.classList.toggle('fa-eye-slash', isPassword);
    }

    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.toggle-password');
        if (!btn) return;

        const targetId = btn.getAttribute('data-target');
        const input = document.getElementById(targetId);
        if (!input) return;

        const icon = btn.querySelector('i');

        toggleVisibility(input, icon);
    });

})();

document.addEventListener("DOMContentLoaded", () => {
    const card = document.querySelector(".auth-tabs-card");
    const flipInner = document.querySelector(".flip-inner");
    const loginPanel = document.getElementById("tab-login");
    const registerPanel = document.getElementById("tab-register");

    function ajustarAltura() {
        // força ambos a ficarem visíveis para medir
        loginPanel.style.position = "relative";
        registerPanel.style.position = "relative";
        loginPanel.style.visibility = "hidden";
        registerPanel.style.visibility = "hidden";
        loginPanel.classList.remove("is-hidden");
        registerPanel.classList.remove("is-hidden");

        const hLogin = loginPanel.offsetHeight;
        const hRegister = registerPanel.offsetHeight;

        const max = Math.max(hLogin, hRegister);

        flipInner.style.height = max + "px";

        // restaura o que estava antes
        if (card.dataset.active === "login") {
            registerPanel.classList.add("is-hidden");
        } else {
            loginPanel.classList.add("is-hidden");
        }

        loginPanel.style.visibility = "";
        registerPanel.style.visibility = "";
    }

    ajustarAltura();

    // Recalcula ao trocar de aba
    document.querySelectorAll(".tab-btn").forEach(btn => {
        btn.addEventListener("click", () => {
            setTimeout(() => ajustarAltura(), 300);
        });
    });

    // Recalcula ao redimensionar tela
    window.addEventListener("resize", ajustarAltura);
});
</script>