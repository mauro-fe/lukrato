<?php loadPageCss(); ?>

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
                                autocomplete="current-password" required />
                            <small class="field-error" id="passwordError"></small>
                        </div>

                        <button type="submit" id="submitBtn" class="btn-primary"><span
                                class="btn-text">Entrar</span></button>

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
                            <small class="field-error" id="regPasswordError"></small>
                        </div>

                        <div class="field">
                            <input type="password" id="reg_password_confirm" name="password_confirmation"
                                placeholder="Confirmar senha" autocomplete="new-password" required />
                            <small class="field-error" id="regPasswordConfirmError"></small>
                        </div>

                        <button type="submit" class="btn-primary"><span class="btn-text">Criar conta</span></button>

                        <div id="registerGeneralError" class="msg msg-error" aria-live="polite"></div>
                        <div id="registerGeneralSuccess" class="msg msg-success" aria-live="polite"></div>
                    </form>
                </div>
            </div>
        </section>
    </div>
</main>

<?php loadPageJs(); ?>
<script src="<?= BASE_URL ?>/assets/js/admin-admins-login.js" defer></script>