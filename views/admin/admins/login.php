    <?php loadPageCss(); ?>


    <main class="lukrato-auth">
        <div class="login-wrapper">
            <!-- Esquerda -->
            <section class="login-left">
                <div class="brand">
                    <div class="imagem-logo" aria-hidden="true">
                        <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="Lukrato">
                    </div>

                </div>

                <header class="welcome">
                    <h2>Bem‑vindo ao Lukrato</h2>
                    <p>
                        Acompanhe saldos, investimentos e objetivos em um único lugar.
                        Entre para gerenciar suas finanças com segurança e performance.
                    </p>
                </header>
            </section>

            <!-- Direita -->
            <section class="login-right">
                <div class="card">
                    <h3 class="card-title">Entrar</h3>

                    <form action="<?= BASE_URL ?>login/entrar" method="POST" id="loginForm" novalidate>
                        <?= csrf_input('login_form') ?>

                        <div class="field">
                            <input type="email" id="email" name="email" placeholder="E‑mail" autocomplete="email"
                                required>
                            <small class="field-error" id="emailError"></small>
                        </div>

                        <div class="field">
                            <input type="password" id="password" name="password" placeholder="Senha"
                                autocomplete="current-password" required>
                            <small class="field-error" id="passwordError"></small>
                        </div>

                        <button type="submit" class="btn-primary"><span class="btn-text">Entrar</span></button>

                        <p class="extra-link">
                            <a href="<?= BASE_URL ?>recuperar-senha">Esqueceu a senha?</a>
                        </p>

                        <div id="generalError" class="msg msg-error" aria-live="polite"></div>
                        <div id="generalSuccess" class="msg msg-success" aria-live="polite"></div>
                    </form>
                </div>
            </section>
        </div>
    </main>