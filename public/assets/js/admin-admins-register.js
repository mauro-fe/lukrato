// Funções de máscara em JavaScript puro
function applyMask(input, mask) {
    let value = input.value.replace(/\D/g, '');
    let maskedValue = '';
    let valueIndex = 0;

    for (let i = 0; i < mask.length && valueIndex < value.length; i++) {
        if (mask[i] === '0') {
            maskedValue += value[valueIndex];
            valueIndex++;
        } else {
            maskedValue += mask[i];
        }
    }

    input.value = maskedValue;
}

function applyCpfCnpjMask(input) {
    const value = input.value.replace(/\D/g, '');
    if (value.length > 11) {
        applyMask(input, '00.000.000/0000-00');
    } else {
        applyMask(input, '000.000.000-00');
    }
}

function applyPhoneMask(input) {
    applyMask(input, '(00) 00000-0000');
}

document.addEventListener('DOMContentLoaded', function () {
    // Máscara para CPF/CNPJ
    const cpfCnpjInput = document.getElementById('cpf_cnpj');
    if (cpfCnpjInput) {
        cpfCnpjInput.addEventListener('input', function () {
            applyCpfCnpjMask(this);
        });
        applyCpfCnpjMask(cpfCnpjInput); // Aplicar máscara inicial
    }

    // Máscara para telefone
    const telefoneInput = document.getElementById('telefone');
    if (telefoneInput) {
        telefoneInput.addEventListener('input', function () {
            applyPhoneMask(this);
        });
    }

    // Validação de senha em tempo real
    const passwordInput = document.getElementById('password');
    if (passwordInput) {
        passwordInput.addEventListener('input', function () {
            checkPasswordStrength(this.value);
        });
    }

    // Validação de confirmação de senha
    const confirmPasswordInput = document.getElementById('confirm_password');
    if (confirmPasswordInput) {
        confirmPasswordInput.addEventListener('input', function () {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            const formGroup = this.closest('.form-group');

            // Remove feedback anterior
            const existingFeedback = formGroup.querySelector('.invalid-feedback');
            if (existingFeedback) {
                existingFeedback.remove();
            }

            if (confirmPassword && password !== confirmPassword) {
                formGroup.classList.add('is-invalid');
                formGroup.classList.remove('is-valid');

                const feedback = document.createElement('div');
                feedback.className = 'invalid-feedback';
                feedback.textContent = 'As senhas não coincidem';
                this.parentNode.parentNode.appendChild(feedback);
            } else {
                formGroup.classList.remove('is-invalid');
                if (confirmPassword && password === confirmPassword) {
                    formGroup.classList.add('is-valid');
                } else {
                    formGroup.classList.remove('is-valid');
                }
            }
        });
    }

    // Validação do formulário
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = new FormData(registerForm);
            const submitBtn = this.querySelector('.submit-btn');

            submitBtn.classList.add('loading');
            submitBtn.disabled = true;

            fetch(registerForm.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Sucesso!',
                            text: data.message || 'Administrador registrado com sucesso!',
                        }).then(() => {
                            if (data.data && data.data.redirect) {
                                window.location.href = data.data.redirect;
                            } else {
                                window.location.reload();
                            }
                        });
                    } else {
                        let msg = '';

                        if (data.message) {
                            msg += `${data.message}\n`;
                        }

                        if (data.errors) {
                            for (let key in data.errors) {
                                msg += `- ${data.errors[key]}\n`;
                            }
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'Erro',
                            text: msg.trim() || 'Algo deu errado. Tente novamente.'
                        });
                    }
                })

                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro inesperado',
                        text: error.message || 'Algo deu errado. Tente novamente.'
                    });
                })
                .finally(() => {
                    submitBtn.classList.remove('loading');
                    submitBtn.disabled = false;
                });
        });
    }


    // Animação nos inputs
    const formControls = document.querySelectorAll('.form-control');
    formControls.forEach(control => {
        control.addEventListener('focus', function () {
            const label = this.closest('.form-group').querySelector('.form-label');
            if (label) {
                label.style.color = 'var(--primary)';
            }
        });

        control.addEventListener('blur', function () {
            const label = this.closest('.form-group').querySelector('.form-label');
            if (label) {
                label.style.color = 'var(--dark)';
            }
        });
    });
});

// Função para alternar visibilidade da senha
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const toggleBtn = field.nextElementSibling;
    const icon = toggleBtn.querySelector('i');

    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Função para verificar força da senha
function checkPasswordStrength(password) {
    const strengthBar = document.getElementById('passwordStrengthBar');
    let strength = 0;

    if (password.length >= 8) strength++;
    if (/[a-z]/.test(password)) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[^A-Za-z0-9]/.test(password)) strength++;

    strengthBar.className = 'password-strength-bar';

    switch (strength) {
        case 0:
        case 1:
            strengthBar.classList.add('strength-weak');
            break;
        case 2:
        case 3:
            strengthBar.classList.add('strength-fair');
            break;
        case 4:
            strengthBar.classList.add('strength-good');
            break;
        case 5:
            strengthBar.classList.add('strength-strong');
            break;
    }
}

// Efeito de paralaxe nas formas flutuantes
document.addEventListener('mousemove', (e) => {
    const shapes = document.querySelectorAll('.shape');
    const x = e.clientX / window.innerWidth;
    const y = e.clientY / window.innerHeight;

    shapes.forEach((shape, index) => {
        const speed = (index + 1) * 0.5;
        const xPos = (x - 0.5) * speed * 50;
        const yPos = (y - 0.5) * speed * 50;

        shape.style.transform = `translate(${xPos}px, ${yPos}px)`;
    });
});

// Animação de entrada suave
window.addEventListener('load', () => {
    document.body.style.opacity = '1';
});

// Validação em tempo real dos campos
const inputs = document.querySelectorAll('.form-control');

inputs.forEach(input => {
    input.addEventListener('blur', function () {
        validateField(this);
    });

    input.addEventListener('input', function () {
        if (this.classList.contains('is-invalid')) {
            validateField(this);
        }
    });
});

function validateField(field) {
    const value = field.value.trim();
    const fieldName = field.name;
    let isValid = true;
    let message = '';

    // Limpar validações anteriores
    field.closest('.form-group').classList.remove('is-invalid', 'is-valid');
    const existingFeedback = field.parentNode.parentNode.querySelector('.invalid-feedback');
    if (existingFeedback) {
        existingFeedback.remove();
    }

    // Validações específicas
    switch (fieldName) {
        case 'nome_completo':
            if (value.length < 3) {
                isValid = false;
                message = 'Nome deve ter pelo menos 3 caracteres';
            }
            break;

        case 'username':
            if (value.length < 3) {
                isValid = false;
                message = 'Username deve ter pelo menos 3 caracteres';
            } else if (!/^[a-zA-Z0-9_]+$/.test(value)) {
                isValid = false;
                message = 'Username deve conter apenas letras, números e _';
            }
            break;

        case 'email':
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                isValid = false;
                message = 'Digite um e-mail válido';
            }
            break;

        case 'telefone':
            const phoneRegex = /^\(\d{2}\) \d{4,5}-\d{4}$/;
            if (!phoneRegex.test(value)) {
                isValid = false;
                message = 'Digite um telefone válido';
            }
            break;

        case 'cpf_cnpj':
            const numbers = value.replace(/\D/g, '');
            if (numbers.length !== 11 && numbers.length !== 14) {
                isValid = false;
                message = 'Digite um CPF (11 dígitos) ou CNPJ (14 dígitos) válido';
            }
            break;

        case 'password':
            const strongRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*#?&.]).{8,}$/;
            if (!strongRegex.test(value)) {
                isValid = false;
                message = 'A senha deve ter ao menos 8 caracteres, uma letra maiúscula, uma minúscula, um número e um caractere especial.';
            }

            break;
    }

    // Aplicar resultado da validação
    if (value && !isValid) {
        field.closest('.form-group').classList.add('is-invalid');
        const feedback = document.createElement('div');
        feedback.className = 'invalid-feedback';
        feedback.textContent = message;
        field.parentNode.parentNode.appendChild(feedback);
    } else if (value) {
        field.closest('.form-group').classList.add('is-valid');
    }
}

// Efeito de ripple nos botões
document.querySelectorAll('.submit-btn').forEach(button => {
    button.addEventListener('click', function (e) {
        const ripple = document.createElement('span');
        const rect = this.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = e.clientX - rect.left - size / 2;
        const y = e.clientY - rect.top - size / 2;

        ripple.style.width = ripple.style.height = size + 'px';
        ripple.style.left = x + 'px';
        ripple.style.top = y + 'px';
        ripple.classList.add('ripple');

        this.appendChild(ripple);

        setTimeout(() => {
            ripple.remove();
        }, 600);
    });
});