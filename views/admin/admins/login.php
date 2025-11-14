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

</style>



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

                            <button type="button" class="toggle-password" aria-label="Mostrar senha" data-target="password" title="Mostrar/ocultar senha">

                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">

                                    <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z" />

                                    <circle cx="12" cy="12" r="3" />

                                </svg>

                            </button>

                            <small class="field-error" id="passwordError"></small>

                        </div>



                        <button type="submit" id="submitBtn" class="btn-primary"><span

                                class="btn-text">Entrar</span></button>

                        
                        <div class="auth-separator">
                            <span>ou</span>
                        </div>

                        <div class="google-sign-in-container">
                            <a href="<?= BASE_URL ?>auth/google/register" class="google-sign-in-button">
                                <img src="https://fonts.gstatic.com/s/i/productlogos/googleg/v6/24px.svg" alt="Google logo" class="google-icon">
                                <span class="button-text">Entrar com Google</span>
                            </a>
                        </div>

                        <p class="extra-link">

                            <a href="<?= BASE_URL ?>recuperar-senha">Esqueceu a senha?</a>

                        </p>



                        <div id="generalError" class="msg msg-error general-message" aria-live="polite"></div>

                        <div id="generalSuccess" class="msg msg-success general-message" aria-live="polite"></div>

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

                            <button type="button" class="toggle-password" aria-label="Mostrar senha" data-target="reg_password" title="Mostrar/ocultar senha">

                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">

                                    <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z" />

                                    <circle cx="12" cy="12" r="3" />

                                </svg>

                            </button>

                            <small class="field-error" id="regPasswordError"></small>

                        </div>



                        <div class="field">

                            <input type="password" id="reg_password_confirm" name="password_confirmation"

                                placeholder="Confirmar senha" autocomplete="new-password" required />

                            <button type="button" class="toggle-password" aria-label="Mostrar senha" data-target="reg_password_confirm" title="Mostrar/ocultar senha">

                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">

                                    <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z" />

                                    <circle cx="12" cy="12" r="3" />

                                </svg>

                            </button>

                            <small class="field-error" id="regPasswordConfirmError"></small>

                        </div>



                        <button type="submit" class="btn-primary"><span class="btn-text">Criar conta</span></button>



                        <div class="auth-separator">

                            <span>ou</span>

                        </div>



                        <div class="google-sign-in-container">

                            <a href="<?= BASE_URL ?>auth/google/register" class="google-sign-in-button">

                                <img src="https://fonts.gstatic.com/s/i/productlogos/googleg/v6/24px.svg" alt="Google logo" class="google-icon">

                                <span class="button-text">Entrar com Google</span>

                            </a>

                        </div>



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

<script src="<?= BASE_URL ?>/assets/js/admin-admins-login.js" defer></script>



<script>

    window.BASE_URL = <?= json_encode(rtrim(BASE_URL, '/') . '/') ?>;

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