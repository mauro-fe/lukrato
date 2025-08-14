document.addEventListener("DOMContentLoaded", function () {
    // Aplicar máscara para telefone
    const telefoneInput = document.getElementById("telefone");
    if (telefoneInput) {
        telefoneInput.addEventListener("input", function (e) {
            let value = e.target.value.replace(/\D/g, "");
            if (value.length > 11) value = value.slice(0, 11);

            if (value.length > 2) {
                value = '(' + value.substring(0, 2) + ') ' + value.substring(2);
            }
            if (value.length > 10) {
                value = value.substring(0, 10) + '-' + value.substring(10);
            }

            e.target.value = value;
        });
    }

    // Aplicar máscara para CNPJ
    // Aplicar máscara dinâmica para CPF ou CNPJ
    const cpfCnpjInput = document.getElementById("cpf_cnpj");
    if (cpfCnpjInput) {
        cpfCnpjInput.addEventListener("input", function (e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 14) value = value.slice(0, 14);

            if (value.length <= 11) {
                // Máscara de CPF: 000.000.000-00
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            } else {
                // Máscara de CNPJ: 00.000.000/0000-00
                value = value.replace(/^(\d{2})(\d)/, "$1.$2");
                value = value.replace(/^(\d{2})\.(\d{3})(\d)/, "$1.$2.$3");
                value = value.replace(/\.(\d{3})(\d)/, ".$1/$2");
                value = value.replace(/(\d{4})(\d)/, "$1-$2");
            }

            e.target.value = value;
        });
    }


    // Validação do formulário
    const formProfile = document.getElementById("form-profile");
    if (formProfile) {
        formProfile.addEventListener("submit", function (e) {
            e.preventDefault();

            if (!this.checkValidity()) {
                e.stopPropagation();
                this.classList.add("was-validated");

                Swal.fire({
                    icon: 'warning',
                    title: 'Dados inválidos',
                    text: 'Por favor, corrija os campos destacados em vermelho.',
                    confirmButtonColor: '#f39c12'
                });
                return;
            }

            // Confirmar alterações
            Swal.fire({
                title: 'Confirmar alterações',
                text: 'Deseja salvar as alterações no seu perfil?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sim, salvar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Mostrar indicador de carregamento
                    const btnSave = document.getElementById("btn-save");
                    const originalText = btnSave.innerHTML;
                    btnSave.innerHTML =
                        '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Salvando...';
                    btnSave.disabled = true;

                    // Enviar formulário via AJAX
                    const formData = new FormData(formProfile);

                    fetch(formProfile.action, {
                        method: 'POST',
                        body: formData
                    })
                        .then(async response => {
                            const rawText = await response.text();
                            try {
                                return JSON.parse(rawText);
                            } catch (err) {
                                console.error("Erro ao interpretar JSON:", rawText);
                                throw new Error("Resposta inválida do servidor.");
                            }
                        })
                        .then(data => {
                            if (data.status === 'success') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Sucesso!',
                                    text: data.message,
                                    confirmButtonColor: '#28a745'
                                }).then(() => {
                                    if (data.redirect) {
                                        window.location.href = data.redirect;
                                    } else {
                                        window.location.reload();
                                    }
                                });
                            } else if (data.errors) {
                                const errorList = Object.values(data.errors)
                                    .map(msg => `<li>${msg}</li>`)
                                    .join('');

                                Swal.fire({
                                    icon: 'error',
                                    title: 'Erro de Validação',
                                    html: `<ul style="text-align:left">${errorList}</ul>`,
                                    confirmButtonColor: '#e74a3b'
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Erro!',
                                    text: data.message || 'Erro ao salvar alterações.',
                                    confirmButtonColor: '#e74a3b'
                                });
                            }
                        })
                        .catch(error => {
                            console.error('Erro:', error);
                            Swal.fire({
                                icon: 'error',
                                title: 'Erro!',
                                text: 'Erro de conexão. Tente novamente.',
                                confirmButtonColor: '#e74a3b'
                            });
                        })
                        .finally(() => {
                            // Restaurar botão
                            btnSave.innerHTML = originalText;
                            btnSave.disabled = false;
                        });
                }
            });
        });
    }

    // Remover feedback de erro ao digitar
    const inputs = document.querySelectorAll('.form-control');
    inputs.forEach(input => {
        input.addEventListener('input', function () {
            this.classList.remove('is-invalid');
        });
    });
});