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
                <h2>Definir nova senha</h2>
                <p>Escolha uma senha forte e memorável para manter sua conta segura.</p>
            </header>
        </section>

        <!-- RIGHT: Form Card -->
        <section class="login-right">
            <div class="card">
                <h3 class="card-title">Nova senha</h3>

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

                <?php if (!empty($success)): ?>
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'Senha alterada com sucesso!',
                        text: 'Redirecionando…',
                        timer: 2500,
                        showConfirmButton: false,
                    });
                });
                </script>
                <?php endif; ?>


                <form action="<?= BASE_URL ?>resetar-senha" method="POST" novalidate id="resetForm">
                    <?= csrf_input('reset_form') ?>

                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                    <div class="field">
                        <input type="password" name="password" id="password"
                            placeholder="Nova senha (mínimo 8 caracteres)" autocomplete="new-password" required
                            minlength="8" aria-label="Nova senha">
                        <div class="password-strength" id="strengthBar">
                            <div class="password-strength-bar"></div>
                        </div>
                        <small
                            style="font-size: var(--font-size-xs); color: var(--color-text-muted); margin-top: var(--spacing-1);">
                            Use letras, números e símbolos para uma senha forte
                        </small>
                    </div>

                    <div class="field">
                        <input type="password" name="password_confirmation" id="password_confirmation"
                            placeholder="Confirmar nova senha" autocomplete="new-password" required minlength="8"
                            aria-label="Confirmar senha">
                    </div>

                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <span>Redefinir senha</span>
                    </button>

                    <p class="extra-link">
                        <a href="<?= BASE_URL ?>login">← Voltar para o login</a>
                    </p>
                </form>
            </div>
        </section>
    </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Password strength indicator
const passwordInput = document.getElementById('password');
const strengthBar = document.getElementById('strengthBar');
const strengthBarInner = strengthBar?.querySelector('.password-strength-bar');

if (passwordInput && strengthBar && strengthBarInner) {
    passwordInput.addEventListener('input', function() {
        const password = this.value;

        if (password.length === 0) {
            strengthBar.style.display = 'none';
            return;
        }

        strengthBar.style.display = 'block';

        let strength = 0;

        // Critérios de força
        if (password.length >= 8) strength++;
        if (password.length >= 12) strength++;
        if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
        if (/\d/.test(password)) strength++;
        if (/[^a-zA-Z0-9]/.test(password)) strength++;

        // Remove classes anteriores
        strengthBarInner.classList.remove('weak', 'medium', 'strong');

        // Aplica nova classe
        if (strength <= 2) {
            strengthBarInner.classList.add('weak');
        } else if (strength <= 4) {
            strengthBarInner.classList.add('medium');
        } else {
            strengthBarInner.classList.add('strong');
        }
    });
}

// Validação de confirmação de senha
const confirmInput = document.getElementById('password_confirmation');
if (confirmInput && passwordInput) {
    confirmInput.addEventListener('input', function() {
        if (this.value && this.value !== passwordInput.value) {
            this.setCustomValidity('As senhas não coincidem');
        } else {
            this.setCustomValidity('');
        }
    });
}

// Loading state no botão
document.getElementById('resetForm')?.addEventListener('submit', function(e) {
    const password = passwordInput.value;
    const confirmation = confirmInput.value;

    if (password !== confirmation) {
        e.preventDefault();
        confirmInput.setCustomValidity('As senhas não coincidem');
        confirmInput.reportValidity();
        return;
    }

    const btn = document.getElementById('submitBtn');
    if (btn) {
        btn.classList.add('loading');
        btn.disabled = true;
    }
});
</script>

<?php loadPageJs(); ?>