import { apiFetch, getErrorMessage } from '../shared/api.js';

function collectPasswordPayload(form) {
    const senhaAtual = document.getElementById('senha_atual')?.value || '';
    const novaSenha = document.getElementById('nova_senha')?.value || '';
    const confSenha = document.getElementById('conf_senha')?.value || '';

    const formData = new FormData();
    formData.append('senha_atual', senhaAtual);
    formData.append('nova_senha', novaSenha);
    formData.append('conf_senha', confSenha);

    const csrfInput = form.querySelector('input[name="csrf_token"]') || form.querySelector('input[name="_token"]');
    if (csrfInput) {
        formData.append(csrfInput.name, csrfInput.value);
    }

    return { senhaAtual, novaSenha, confSenha, formData };
}

function getPasswordValidationErrors(senhaAtual, novaSenha, confSenha) {
    const errors = [];

    if (!senhaAtual || !novaSenha || !confSenha) {
        errors.push('Todos os campos de senha sao obrigatorios.');
    }
    if (novaSenha.length < 8) errors.push('A senha deve ter no minimo 8 caracteres.');
    if (!/[a-z]/.test(novaSenha)) errors.push('A senha deve conter pelo menos uma letra minuscula.');
    if (!/[A-Z]/.test(novaSenha)) errors.push('A senha deve conter pelo menos uma letra maiuscula.');
    if (!/[0-9]/.test(novaSenha)) errors.push('A senha deve conter pelo menos um numero.');
    if (!/[^a-zA-Z0-9]/.test(novaSenha)) errors.push('A senha deve conter pelo menos um caractere especial.');
    if (novaSenha && confSenha && novaSenha !== confSenha) errors.push('As senhas nao coincidem.');

    return errors;
}

function showPasswordValidationErrors(errors) {
    const strengthPanel = document.getElementById('pwdStrengthProfile');
    if (strengthPanel) {
        strengthPanel.classList.add('visible');
    }

    if (!window.Swal) {
        return;
    }

    Swal.fire({
        icon: 'warning',
        title: 'Senha nao atende aos requisitos',
        html: `<ul style="text-align:left;margin:0;padding-left:1.2em">${errors.map((item) => `<li>${item}</li>`).join('')}</ul>`,
        confirmButtonColor: '#e67e22',
    });
}

function resetPasswordFormUi() {
    const senhaAtual = document.getElementById('senha_atual');
    const novaSenha = document.getElementById('nova_senha');
    const confSenha = document.getElementById('conf_senha');
    const strengthPanel = document.getElementById('pwdStrengthProfile');
    const matchPanel = document.getElementById('pwdMatchProfile');

    if (senhaAtual) senhaAtual.value = '';
    if (novaSenha) novaSenha.value = '';
    if (confSenha) confSenha.value = '';
    if (strengthPanel) strengthPanel.classList.remove('visible');
    if (matchPanel) matchPanel.classList.remove('visible');
}

export function initConfigSecurity(context) {
    const form = context.form;
    if (!form) {
        return;
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        form.classList.add('form-loading');

        const submitBtn = event.submitter || form.querySelector('.btn-save');
        const originalContent = submitBtn?.innerHTML || '';
        if (submitBtn) {
            submitBtn.innerHTML = '<span class="spinner"></span><span>Salvando...</span>';
            submitBtn.disabled = true;
        }

        const panel = submitBtn?.closest('.profile-tab-panel');
        const panelId = panel?.id || '';

        if (panelId !== 'panel-seguranca') {
            form.classList.remove('form-loading');
            if (submitBtn) {
                submitBtn.innerHTML = originalContent;
                submitBtn.disabled = false;
            }
            return;
        }

        const { senhaAtual, novaSenha, confSenha, formData } = collectPasswordPayload(form);
        const errors = getPasswordValidationErrors(senhaAtual, novaSenha, confSenha);
        if (errors.length > 0) {
            showPasswordValidationErrors(errors);
            form.classList.remove('form-loading');
            if (submitBtn) {
                submitBtn.innerHTML = originalContent;
                submitBtn.disabled = false;
            }
            return;
        }

        try {
            const response = await apiFetch(`${context.API}perfil/senha`, {
                method: 'POST',
                credentials: 'include',
                body: formData,
            });

            if (response?.success === false) {
                throw new Error(getErrorMessage({ data: response }, 'Falha ao alterar senha.'));
            }

            resetPasswordFormUi();

            if (window.Swal) {
                Swal.fire({
                    icon: 'success',
                    title: 'Senha alterada!',
                    text: 'Sua senha foi atualizada com sucesso.',
                    confirmButtonColor: '#e67e22',
                    timer: 2000,
                });
            }
        } catch (error) {
            console.error('Erro ao alterar senha:', error);

            if (window.Swal) {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro ao alterar senha',
                    text: getErrorMessage(error, 'Erro ao alterar senha.'),
                    confirmButtonColor: '#e74c3c',
                });
            }
        } finally {
            form.classList.remove('form-loading');
            if (submitBtn) {
                submitBtn.innerHTML = originalContent;
                submitBtn.disabled = false;
            }
        }
    });
}
