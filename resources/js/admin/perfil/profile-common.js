import { apiDelete, apiFetch, apiGet, getErrorMessage } from '../shared/api.js';

export function maskCep(value) {
    const digits = String(value || '').replace(/\D/g, '');
    if (digits.length <= 5) {
        return digits;
    }

    return `${digits.substring(0, 5)}-${digits.substring(5, 8)}`;
}

export function getCsrfToken(context) {
    return context.form?.querySelector('input[name="csrf_token"]')?.value
        || document.querySelector('meta[name="csrf-token"]')?.content
        || '';
}

export function updateAvatarDisplay(context, avatarUrl, nome) {
    const avatarImg = context.avatar.image;
    const avatarInitials = context.avatar.initials;

    if (avatarUrl) {
        if (avatarImg) {
            avatarImg.src = avatarUrl;
            avatarImg.style.display = 'block';
        }

        if (avatarInitials) {
            avatarInitials.style.display = 'none';
        }
    } else {
        if (avatarImg) {
            avatarImg.style.display = 'none';
            avatarImg.removeAttribute('src');
        }

        if (avatarInitials) {
            avatarInitials.textContent = (nome || 'U').charAt(0).toUpperCase();
            avatarInitials.style.display = '';
        }
    }

    if (window.__LK_updateGlobalAvatars) {
        window.__LK_updateGlobalAvatars(avatarUrl);
    }
}

export function updateEmailPendingNotice(context, user = {}) {
    const fieldEmail = context.fields.email;
    if (!fieldEmail) {
        return;
    }

    const currentEmail = String(user.email || '').trim();
    const pendingEmail = String(user.pending_email || '').trim();
    const hasPending = Boolean(user.email_change_pending) && pendingEmail !== '';

    fieldEmail.value = hasPending ? pendingEmail : currentEmail;

    const group = fieldEmail.closest('.form-group');
    if (!group) {
        return;
    }

    let note = document.getElementById(context.emailPendingNoticeId);
    if (!hasPending) {
        if (note) {
            note.remove();
        }

        return;
    }

    if (!note) {
        note = document.createElement('small');
        note.id = context.emailPendingNoticeId;
        note.style.display = 'block';
        note.style.marginTop = '6px';
        note.style.color = '#d97706';
        group.appendChild(note);
    }

    note.textContent = `Novo e-mail pendente de confirmacao: ${pendingEmail}. O login continua com o e-mail atual ate a confirmacao.`;
}

async function removeAvatar(context) {
    const avatarEditBtn = context.avatar.editButton;
    const fieldNome = context.fields.nome;

    if (avatarEditBtn) {
        avatarEditBtn.disabled = true;
    }

    try {
        const response = await apiDelete(`${context.API}perfil/avatar`);

        if (response?.success === false) {
            throw new Error(getErrorMessage({ data: response }, 'Falha ao remover foto.'));
        }

        updateAvatarDisplay(context, '', fieldNome?.value);

        if (window.Swal) {
            Swal.fire({
                icon: 'success',
                title: 'Foto removida',
                timer: 1500,
                showConfirmButton: false,
            });
        }
    } catch (error) {
        console.error('Erro ao remover avatar:', error);

        if (window.Swal) {
            Swal.fire({
                icon: 'error',
                title: 'Erro',
                text: getErrorMessage(error, 'Falha ao remover foto.'),
                confirmButtonColor: '#e74c3c',
            });
        }
    } finally {
        if (avatarEditBtn) {
            avatarEditBtn.disabled = false;
        }
    }
}

export function setupAvatarHandlers(context) {
    const avatarImg = context.avatar.image;
    const avatarInitials = context.avatar.initials;
    const avatarEditBtn = context.avatar.editButton;
    const avatarInput = context.avatar.input;
    const fieldNome = context.fields.nome;

    if (avatarImg) {
        avatarImg.addEventListener('error', () => {
            avatarImg.style.display = 'none';
            if (avatarInitials) {
                avatarInitials.style.display = '';
            }
        });
    }

    if (!avatarEditBtn || !avatarInput) {
        return;
    }

    avatarEditBtn.addEventListener('click', () => {
        if (avatarImg && avatarImg.style.display !== 'none' && avatarImg.src) {
            if (window.Swal) {
                Swal.fire({
                    title: 'Foto de perfil',
                    text: 'O que deseja fazer?',
                    showDenyButton: true,
                    showCancelButton: true,
                    confirmButtonText: 'Trocar foto',
                    denyButtonText: 'Remover foto',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#e67e22',
                    denyButtonColor: '#ef4444',
                }).then((result) => {
                    if (result.isConfirmed) {
                        avatarInput.click();
                    } else if (result.isDenied) {
                        void removeAvatar(context);
                    }
                });
            } else {
                avatarInput.click();
            }

            return;
        }

        avatarInput.click();
    });

    avatarInput.addEventListener('change', async () => {
        const file = avatarInput.files?.[0];
        if (!file) {
            return;
        }

        const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            if (window.Swal) {
                Swal.fire({
                    icon: 'error',
                    title: 'Tipo invalido',
                    text: 'Use JPEG, PNG ou WebP.',
                    confirmButtonColor: '#e74c3c',
                });
            }

            avatarInput.value = '';
            return;
        }

        if (file.size > 2 * 1024 * 1024) {
            if (window.Swal) {
                Swal.fire({
                    icon: 'error',
                    title: 'Arquivo muito grande',
                    text: 'O tamanho maximo e 2MB.',
                    confirmButtonColor: '#e74c3c',
                });
            }

            avatarInput.value = '';
            return;
        }

        const formData = new FormData();
        formData.append('avatar', file);
        formData.append('csrf_token', getCsrfToken(context));

        avatarEditBtn.disabled = true;

        try {
            const response = await apiFetch(`${context.API}perfil/avatar`, {
                method: 'POST',
                credentials: 'include',
                body: formData,
            });

            if (response?.success === false) {
                throw new Error(getErrorMessage({ data: response }, 'Falha ao enviar foto.'));
            }

            updateAvatarDisplay(context, response.data?.avatar, fieldNome?.value);

            if (window.Swal) {
                Swal.fire({
                    icon: 'success',
                    title: 'Foto atualizada!',
                    timer: 1500,
                    showConfirmButton: false,
                });
            }
        } catch (error) {
            console.error('Erro ao enviar avatar:', error);

            if (window.Swal) {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: getErrorMessage(error, 'Falha ao enviar foto.'),
                    confirmButtonColor: '#e74c3c',
                });
            }
        } finally {
            avatarInput.value = '';
            avatarEditBtn.disabled = false;
        }
    });
}

export async function loadProfile(context) {
    if (!context.form) {
        return;
    }

    try {
        const response = await apiGet(`${context.API}perfil`);
        if (response?.success === false) {
            throw new Error(getErrorMessage({ data: response }, 'Falha ao carregar perfil.'));
        }

        const user = response?.data?.user || {};
        const field = context.fields;

        if (field.nome) {
            field.nome.value = user.nome || '';
        }

        updateEmailPendingNotice(context, user);
        updateAvatarDisplay(context, user.avatar, user.nome);

        const supportCodeField = document.getElementById('support_code');
        if (supportCodeField) {
            supportCodeField.value = user.support_code || '-';
        }

        if (field.cpf) {
            field.cpf.value = user.cpf || '';
        }

        if (field.dataNascimento) {
            field.dataNascimento.value = user.data_nascimento || '';
        }

        if (field.telefone) {
            field.telefone.value = user.telefone || '';
        }

        if (field.sexo) {
            field.sexo.value = user.sexo || '';
        }

        const endereco = user.endereco || {};

        if (field.cep) {
            field.cep.value = maskCep(endereco.cep || '');
        }

        if (field.rua) {
            field.rua.value = endereco.rua || '';
        }

        if (field.numero) {
            field.numero.value = endereco.numero || '';
        }

        if (field.complemento) {
            field.complemento.value = endereco.complemento || '';
        }

        if (field.bairro) {
            field.bairro.value = endereco.bairro || '';
        }

        if (field.cidade) {
            field.cidade.value = endereco.cidade || '';
        }

        if (field.estado) {
            field.estado.value = endereco.estado || '';
        }
    } catch (error) {
        console.error('Erro ao carregar perfil:', error);

        if (window.Swal) {
            Swal.fire({
                icon: 'error',
                title: 'Erro ao carregar',
                text: getErrorMessage(error, 'Nao foi possivel carregar o perfil.'),
                confirmButtonColor: '#e74c3c',
            });
        }
    }
}
