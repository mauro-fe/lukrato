// Toggle password visibility (lógica mantida, animação via CSS)
function togglePassword() {
    const passwordField = document.getElementById('password');
    const toggleIcon = document.getElementById('toggleIcon');

    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
        toggleIcon.style.color = '#12453E';
        toggleIcon.title = 'Ocultar senha';
    } else {
        passwordField.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
        toggleIcon.style.color = '#888';
        toggleIcon.title = 'Mostrar senha';
    }

    toggleIcon.style.transform = 'translateY(-50%) scale(1.2)';
    setTimeout(() => {
        toggleIcon.style.transform = 'translateY(-50%) scale(1)';
    }, 150);
}

// Exibir erro no campo
function showFieldError(fieldId, message) {
    const field = document.getElementById(fieldId);
    const errorDiv = document.getElementById(fieldId + 'Error');

    if (field && errorDiv) {
        field.classList.add('error');
        errorDiv.textContent = message;
        errorDiv.classList.add('show');

        field.addEventListener('animationend', () => {
            field.classList.remove('error-animation');
        }, { once: true });

        field.addEventListener('input', function () {
            field.classList.remove('error');
            errorDiv.classList.remove('show');
        }, { once: true });
    }
}

function clearFieldErrors() {
    document.querySelectorAll('.form-control').forEach(field => field.classList.remove('error'));
    document.querySelectorAll('.field-error').forEach(error => error.classList.remove('show'));
}

// Mensagens gerais
function showMessage(type, message) {
    const messageDiv = document.getElementById('general' + (type === 'error' ? 'Error' : 'Success'));
    if (!messageDiv) return;

    document.querySelectorAll('.general-message').forEach(div => div.classList.remove('show'));
    messageDiv.textContent = message;

    setTimeout(() => messageDiv.classList.add('show'), 10);
    setTimeout(() => messageDiv.classList.remove('show'), 5000);
}

// Estado de carregamento no botão
function setLoadingState(loading) {
    const submitBtn = document.getElementById('submitBtn');
    if (!submitBtn) return;
    if (loading) {
        submitBtn.classList.add('loading');
        submitBtn.disabled = true;
    } else {
        submitBtn.classList.remove('loading');
        submitBtn.disabled = false;
    }
}

// Submissão via AJAX (ajustada p/ e-mail)
document.getElementById('loginForm')?.addEventListener('submit', async function (e) {
    e.preventDefault();

    const emailField = document.getElementById('email');
    const passwordField = document.getElementById('password');

    clearFieldErrors();

    let hasErrors = false;

    // Validação simples de e-mail
    const emailVal = emailField.value.trim().toLowerCase();
    if (!emailVal) {
        showFieldError('email', 'Este campo é obrigatório.');
        hasErrors = true;
    } else {
        // HTML5 já valida, mas fazemos um check extra leve
        const emailOk = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailVal);
        if (!emailOk) {
            showFieldError('email', 'Informe um e-mail válido.');
            hasErrors = true;
        }
    }

    if (!passwordField.value.trim()) {
        showFieldError('password', 'Este campo é obrigatório.');
        hasErrors = true;
    } else if (passwordField.value.length < 4) {
        showFieldError('password', 'Senha deve ter pelo menos 4 caracteres.');
        hasErrors = true;
    }

    if (hasErrors) return;

    setLoadingState(true);

    const formData = new FormData(this);
    const csrfTokenInput = document.querySelector('input[name="csrf_token"]');

    if (csrfTokenInput) {
        formData.set('csrf_token', csrfTokenInput.value);
    } else {
        showMessage('error', 'Erro de segurança (CSRF). Recarregue a página.');
        setLoadingState(false);
        return;
    }

    try {
        const response = await fetch(this.action, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        const jsonResponse = await response.json().catch(() => null);

        if (!jsonResponse) {
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: 'Ocorreu um erro inesperado no servidor. Tente novamente.',
            });
            setLoadingState(false);
            return;
        }

        if (jsonResponse.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'Login bem-sucedido!',
                text: 'Você será redirecionado em breve.',
                timer: 1500,
                showConfirmButton: false,
                allowOutsideClick: false,
            });

            setTimeout(() => {
                window.location.href = jsonResponse.redirect;
            }, 1500);

        } else {
            // Agora esperamos field === 'email' ou 'password'
            if (jsonResponse.field === 'password') {
                showFieldError('password', jsonResponse.message || 'Senha incorreta.');
                passwordField.focus();
            } else if (jsonResponse.field === 'email') {
                showFieldError('email', jsonResponse.message || 'E-mail não encontrado.');
                emailField.focus();
            } else {
                showMessage('error', jsonResponse.message || 'E-mail ou senha incorretos.');
            }
            setLoadingState(false);
        }
    } catch (err) {
        Swal.fire({
            icon: 'error',
            title: 'Erro de Conexão',
            text: 'Não foi possível conectar ao servidor. Verifique sua internet.',
        });
        setLoadingState(false);
    }
});

// Atalho: Ctrl + Enter envia formulário
document.addEventListener('keydown', e => {
    if (e.ctrlKey && e.key === 'Enter') {
        const submitBtn = document.getElementById('submitBtn');
        if (!submitBtn.disabled) {
            document.getElementById('loginForm')?.requestSubmit();
        }
    }
});

// Classe 'filled' dinâmica
document.querySelectorAll('.form-floating-group input').forEach(input => {
    const updateFilled = () => {
        if (input.value.trim() !== '') input.classList.add('filled');
        else input.classList.remove('filled');
    };
    input.addEventListener('input', updateFilled);
    input.addEventListener('blur', updateFilled);
    updateFilled();
});