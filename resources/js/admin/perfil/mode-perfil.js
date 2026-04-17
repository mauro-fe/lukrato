import { apiFetch, getErrorMessage } from '../shared/api.js';
import { loadProfile } from './profile-common.js';

function bindSupportCodeCopy() {
    window.copySupportCode = () => {
        const input = document.getElementById('support_code');
        const button = document.getElementById('btn-copy-support');

        if (!input || !input.value || !button) {
            return;
        }

        const originalIcon = button.innerHTML;
        const showCheck = () => {
            button.innerHTML = '<i data-lucide="check"></i>';
            button.style.color = '#22c55e';
            window.lucide?.createIcons?.();

            window.setTimeout(() => {
                button.innerHTML = originalIcon;
                button.style.color = '';
                window.lucide?.createIcons?.();
            }, 2000);
        };

        navigator.clipboard.writeText(input.value).then(showCheck).catch(() => {
            input.select();
            document.execCommand('copy');
            showCheck();
        });
    };
}

function appendPanelFields(formData, panel) {
    const fields = panel.querySelectorAll('input[name]:not([type="password"]):not([name^="_fake"]), select[name], textarea[name]');
    fields.forEach((field) => {
        if (field.name && !field.name.startsWith('_fake') && !formData.has(field.name)) {
            formData.append(field.name, field.value);
        }
    });
}

function bindProfileSubmit(context) {
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

        if (panelId !== 'panel-dados' && panelId !== 'panel-endereco') {
            form.classList.remove('form-loading');
            if (submitBtn) {
                submitBtn.innerHTML = originalContent;
                submitBtn.disabled = false;
            }
            return;
        }

        const formData = new FormData();
        const csrfInput = form.querySelector('input[name="csrf_token"]') || form.querySelector('input[name="_token"]');
        if (csrfInput) {
            formData.append(csrfInput.name, csrfInput.value);
        }

        if (panel) {
            appendPanelFields(formData, panel);
        }

        const otherPanelId = panelId === 'panel-dados' ? 'panel-endereco' : 'panel-dados';
        const otherPanel = document.getElementById(otherPanelId);
        if (otherPanel) {
            appendPanelFields(formData, otherPanel);
        }

        try {
            const response = await apiFetch(context.endpoints.profile, {
                method: 'POST',
                credentials: 'include',
                body: formData,
            });

            if (response?.success === false) {
                throw new Error(getErrorMessage({ data: response }, 'Falha ao salvar.'));
            }

            if (response?.data?.new_achievements && Array.isArray(response.data.new_achievements)) {
                if (typeof window.notifyMultipleAchievements === 'function') {
                    window.notifyMultipleAchievements(response.data.new_achievements);
                }
            }

            if (window.Swal) {
                const emailChangePending = Boolean(response?.data?.email_change_pending);
                const emailVerificationSent = Boolean(response?.data?.email_verification_sent);

                let icon = 'success';
                let title = 'Perfil atualizado!';
                let text = 'Suas informacoes foram salvas com sucesso.';

                if (emailChangePending && emailVerificationSent) {
                    text = 'Novo e-mail pendente de confirmacao. Enviamos um link para validar o novo endereco.';
                } else if (emailChangePending) {
                    icon = 'info';
                    text = 'Existe um novo e-mail pendente de confirmacao.';
                }

                Swal.fire({
                    icon,
                    title,
                    text,
                    confirmButtonColor: '#e67e22',
                    timer: 2200,
                });
            }

            const saveStatus = document.getElementById('save-status');
            if (saveStatus) {
                saveStatus.innerHTML = 'Tudo salvo';
                saveStatus.style.color = '#27ae60';
            }

            await loadProfile(context);
        } catch (error) {
            console.error('Erro ao salvar:', error);

            if (window.Swal) {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro ao salvar',
                    text: getErrorMessage(error, 'Erro ao salvar perfil.'),
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

export function initPerfilMode(context) {
    bindSupportCodeCopy();
    bindProfileSubmit(context);
    return loadProfile(context);
}
