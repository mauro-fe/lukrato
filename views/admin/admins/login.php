<main class="main-content position-relative border-radius-lg">
    <div class="login-wrapper">
        <div class="login-left">
            <img src="https://cdn-icons-png.flaticon.com/512/3771/3771423.png" alt="ClinForm" />
            <h2>Bem-vindo ao ClinForm</h2>
            <p>Preencha seus dados para acessar o sistema de anamnese e gerenciar suas fichas de forma profissional e
                segura.</p>
        </div>

        <div class="login-right">
            <div class="logo">
                <h1>ClinForm</h1>
            </div>

            <!-- SUGESTÃO DE ROTA NOVA: /login/entrar (ajuste conforme seu Router) -->
            <form action="<?= BASE_URL ?>login/entrar" method="POST" id="loginForm">
                <?= csrf_input('login_form') ?>

                <div class="form-floating-group">
                    <!-- login -> email -->
                    <input type="email" id="email" name="email" class="form-control" autocomplete="email"
                        inputmode="email" required />
                    <label for="email">E-mail</label>
                    <div class="field-error" id="emailError"></div>
                </div>

                <div class="form-floating-group">
                    <input type="password" id="password" name="password" class="form-control" required
                        autocomplete="off" />
                    <label for="password">Senha</label>
                    <i class="fas fa-eye input-icon" id="toggleIcon" onclick="togglePassword()"
                        title="Mostrar senha"></i>
                    <div class="field-error" id="passwordError"></div>
                </div>

                <button type="submit" class="btn" id="submitBtn">
                    <span class="btn-text">Entrar</span>
                </button>

                <div class="extra-links">
                    <a href="#">Esqueceu a senha?</a>
                </div>

                <!-- Mensagens gerais (se usar) -->
                <div id="generalError" class="general-message error"></div>
                <div id="generalSuccess" class="general-message success"></div>
            </form>
        </div>
    </div>