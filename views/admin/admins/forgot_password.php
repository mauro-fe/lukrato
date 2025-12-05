<?php loadPageCss(); ?>
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/variables.css">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin-admins-login.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">


<main class="lukrato-auth">
    <div class="login-wrapper">
        <!-- LEFT: Branding & Welcome -->
        <section class="login-left">
            <div class="brand">
                <div class="imagem-logo" aria-hidden="true">
                    <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="Lukrato - Sistema de Gestão" />
                </div>
            </div>

            <header class="welcome">
                <h2>Recuperar senha</h2>
                <p>Não se preocupe! Digite seu e-mail e enviaremos um link seguro para redefinir sua senha.</p>
            </header>
        </section>

        <!-- RIGHT: Form Card -->
        <section class="login-right">
            <div class="card">
                <h3 class="card-title">Esqueceu sua senha?</h3>

                <?php if (!empty($error)): ?>
                <div class="msg msg-error" role="alert">
                    <?= htmlspecialchars($error) ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                <div class="msg msg-success" role="status">
                    <?= htmlspecialchars($success) ?>
                </div>
                <?php endif; ?>

                <form action="<?= BASE_URL ?>recuperar-senha" method="POST" novalidate id="recoverForm">
                    <?= csrf_input('forgot_form') ?>

                    <div class="field">
                        <input type="email" name="email" id="email" placeholder="Digite seu e-mail" autocomplete="email"
                            required aria-label="E-mail">
                    </div>

                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <span>Enviar link de recuperação</span>
                    </button>

                    <p class="extra-link">
                        <a href="<?= BASE_URL ?>login">← Voltar para o login</a>
                    </p>

                    <p class="extra-link">
                        <small>
                            <strong>Dica:</strong> Se você se cadastrou com o Google, use o botão "Entrar com Google" na
                            página de login.
                        </small>
                    </p>
                </form>
            </div>
        </section>
    </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Loading state no botão
document.getElementById('recoverForm')?.addEventListener('submit', function() {
    const btn = document.getElementById('submitBtn');
    if (btn) {
        btn.classList.add('loading');
        btn.disabled = true;
    }
});

// Validação de e-mail em tempo real
const emailInput = document.getElementById('email');
if (emailInput) {
    emailInput.addEventListener('blur', function() {
        const email = this.value.trim();
        if (email && !email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
            this.setCustomValidity('Por favor, insira um e-mail válido');
            this.reportValidity();
        } else {
            this.setCustomValidity('');
        }
    });
}
</script>

<?php loadPageJs(); ?>