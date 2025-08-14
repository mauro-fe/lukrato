
// Aplicar máscaras e validações
function formatPhoneInputRes(input) {
    const errorElement = input.nextElementSibling;

    input.addEventListener('input', function (e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 2) value = `(${value.slice(0, 2)}) ${value.slice(2)}`;
        if (value.length > 10) value = `${value.slice(0, 10)}-${value.slice(10, 14)}`;
        e.target.value = value.slice(0, 15);

        errorElement.textContent = '';
        errorElement.classList.remove('visible');
    });

    input.addEventListener('blur', function (e) {
        const value = e.target.value;
        if (!/^\(\d{2}\) \d{4,5}-\d{4}$/.test(value)) {
            errorElement.textContent = '* Telefone inválido! *';
            errorElement.classList.add('visible');
        }
    });
}

function formatPhoneInput(input) {
    input.addEventListener('input', function (e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 2) value = `(${value.slice(0, 2)}) ${value.slice(2)}`;
        if (value.length > 10) value = `${value.slice(0, 10)}-${value.slice(10, 14)}`;
        e.target.value = value.slice(0, 15);
    });
}

function validateEmailInput(input) {
    const errorElement = input.nextElementSibling;
    input.addEventListener('blur', function () {
        const value = input.value.trim();
        const re = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        if (value === '' || !re.test(value)) {
            errorElement.textContent = '* E-mail inválido! *';
            errorElement.classList.add('visible');
        }
    });
}

function formatAndValidateCepInput(input) {
    const errorElement = input.nextElementSibling;
    input.addEventListener('input', function () {
        let value = input.value.replace(/\D/g, '');
        if (value.length > 5) {
            value = value.replace(/^(\d{5})(\d)/, '$1-$2');
        }
        input.value = value;
        errorElement.textContent = '';
        errorElement.classList.remove('visible');
    });

    input.addEventListener('blur', async function () {
        const value = input.value.trim();
        if (!/^\d{5}-\d{3}$/.test(value)) {
            errorElement.textContent = 'CEP inválido! Use o formato 00000-000.';
            errorElement.classList.add('visible');
            return;
        }

        try {
            const response = await fetch(`https://viacep.com.br/ws/${value.replace('-', '')}/json/`);
            const data = await response.json();

            if (data.erro) throw new Error('CEP não encontrado');

            document.getElementById('endereco').value = data.logradouro || '';
            document.getElementById('bairro').value = data.bairro || '';
            document.getElementById('cidade').value = data.localidade || '';
            document.getElementById('uf').value = data.uf || '';
        } catch (error) {
            errorElement.textContent = 'CEP não encontrado.';
            errorElement.classList.add('visible');
        }
    });
}

function validarData(dia, mes, ano) {
    if (ano < 1900 || ano > new Date().getFullYear()) return false;
    if (mes < 1 || mes > 12) return false;
    const diasNoMes = new Date(ano, mes, 0).getDate();
    return dia >= 1 && dia <= diasNoMes;
}

document.getElementById('data_nascimento').addEventListener('input', function (e) {
    let input = e.target.value.replace(/\D/g, '');
    if (input.length > 2) input = input.replace(/^(\d{2})(\d)/, '$1/$2');
    if (input.length > 5) input = input.replace(/^(\d{2})\/(\d{2})(\d)/, '$1/$2/$3');
    e.target.value = input;
});

document.getElementById('data_nascimento').addEventListener('blur', function (e) {
    const input = e.target.value;
    const errorElement = e.target.nextElementSibling;
    if (input.length < 10) {
        errorElement.textContent = '* Data incompleta! *';
        errorElement.classList.add('visible');
        return;
    }

    const [dia, mes, ano] = input.split('/').map(Number);
    if (!validarData(dia, mes, ano)) {
        errorElement.textContent = '* Data inválida! *';
        errorElement.classList.add('visible');
    } else {
        errorElement.textContent = '';
        errorElement.classList.remove('visible');
    }
});

document.getElementById('idade').addEventListener('input', function () {
    if (this.value < 0 || !Number(this.value)) this.value = '';
});

document.getElementById('numero').addEventListener('input', function () {
    if (this.value < 0 || !Number(this.value)) this.value = '';
});

// Ir para próxima etapa do formulário
function nextStep(currentStep) {
    const currentStepElement = document.getElementById(`step-${currentStep}`);
    const nextStepElement = document.getElementById(`step-${currentStep + 1}`);
    const requiredInputs = currentStepElement.querySelectorAll('[required]');

    let isValid = true;

    requiredInputs.forEach(input => {
        if (!input.value.trim()) {
            isValid = false;
            input.classList.add('input-error');
            let errorElement = input.nextElementSibling;
            if (!errorElement || !errorElement.classList.contains('error-message')) {
                errorElement = document.createElement('span');
                errorElement.classList.add('error-message');
                errorElement.style.color = 'red';
                input.parentNode.insertBefore(errorElement, input.nextSibling);
            }
            errorElement.textContent = 'Este campo é obrigatório.';
        } else {
            input.classList.remove('input-error');
            const errorElement = input.nextElementSibling;
            if (errorElement && errorElement.classList.contains('error-message')) {
                errorElement.textContent = '';
            }
        }
    });

    if (!isValid) {
        Swal.fire({
            icon: 'warning',
            title: 'Campos obrigatórios!',
            text: 'Por favor, preencha todos os campos obrigatórios.',
            confirmButtonText: 'OK'
        });
        return;
    }

    currentStepElement.style.display = 'none';
    nextStepElement.style.display = 'block';
    const firstInput = nextStepElement.querySelector('input, select, textarea');
    if (firstInput) firstInput.focus();
}

function prevStep(currentStep) {
    document.getElementById(`step-${currentStep}`).style.display = 'none';
    document.getElementById(`step-${currentStep - 1}`).style.display = 'block';
    const prevStepElement = document.getElementById(`step-${currentStep - 1}`);
    const firstInput = prevStepElement.querySelector('input, select, textarea');
    if (firstInput) firstInput.focus();
}

// Aplicar tudo
document.addEventListener('DOMContentLoaded', function () {
    const telefoneInput = document.getElementById('telefone_residencial');
    if (telefoneInput) formatPhoneInputRes(telefoneInput);

    formatPhoneInput(document.getElementById('telefone_emergencia'));
    formatPhoneInput(document.getElementById('telefone_medico'));

    const emailInput = document.getElementById('email');
    if (emailInput) validateEmailInput(emailInput);

    const cepInput = document.getElementById('cep');
    if (cepInput) formatAndValidateCepInput(cepInput);
});


// Enviando dados do formulário

document.querySelector("form").addEventListener("submit", function (e) {
    e.preventDefault(); // Impede o envio padrão

    const form = e.target;
    const formData = new FormData(form);

    // Alerta de carregamento
    Swal.fire({
        title: 'Enviando seus dados...',
        html: 'Por favor, aguarde um momento.',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Envio com fetch
    fetch(form.action, {
        method: "POST",
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.status === "success") {
                Swal.fire({
                    icon: "success",
                    title: "Sucesso!",
                    text: data.message,
                    confirmButtonText: "OK"
                }).then(() => {
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    }
                });
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Erro!",
                    text: data.message || "Houve um erro no envio.",
                    confirmButtonText: "OK"
                });
            }
        })
        .catch(() => {
            Swal.fire({
                icon: "error",
                title: "Erro de conexão",
                text: "Não foi possível enviar os dados. Tente novamente.",
                confirmButtonText: "OK"
            });
        });
});
