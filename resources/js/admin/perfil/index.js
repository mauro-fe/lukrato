import '../../../css/admin/perfil/index.css';
import { apiDelete, apiFetch, apiGet, getBaseUrl, getErrorMessage } from '../shared/api.js';
import { initCustomize } from './customize.js';

/**
 * ============================================================================
 * LUKRATO â€” Perfil Page (Vite Module)
 * ============================================================================
 * ExtraÃ­do de views/admin/perfil/index.php (2 inline <script> blocks)
 *
 * Tabs, profile CRUD, referral, password strength, account deletion.
 * ============================================================================
 */

(() => {
    'use strict';
    initCustomize();

    const BASE = getBaseUrl();

    const API = `${BASE}api/`;
    const form = document.getElementById('profileForm');

    // ============================================
    // TAB SWITCHING
    // ============================================
    const tabs = document.querySelectorAll('.profile-tab');
    const panels = document.querySelectorAll('.profile-tab-panel');
    const isConfigPage = window.location.pathname.toLowerCase().includes('/config');
    const tabStorageKey = isConfigPage ? 'configuracoes_tab' : 'perfil_tab';
    const availableTabs = Array.from(tabs)
        .map((tab) => tab.dataset.tab || '')
        .filter(Boolean);

    function switchTab(tabId) {
        tabs.forEach(t => {
            const isActive = t.dataset.tab === tabId;
            t.classList.toggle('active', isActive);
            t.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });
        panels.forEach(p => {
            p.classList.toggle('active', p.id === `panel-${tabId}`);
        });
        // Persist
        try {
            localStorage.setItem(tabStorageKey, tabId);
        } catch (e) { }
        // Update hash without scroll
        history.replaceState(null, '', `#${tabId}`);
    }

    tabs.forEach(tab => {
        tab.addEventListener('click', () => switchTab(tab.dataset.tab));
    });

    // Restore tab from hash or localStorage
    (() => {
        if (availableTabs.length === 0) return;

        const hash = location.hash.replace('#', '');
        let initial = availableTabs[0];

        if (hash && availableTabs.includes(hash)) {
            initial = hash;
        } else {
            try {
                const stored = localStorage.getItem(tabStorageKey);
                if (stored && availableTabs.includes(stored)) initial = stored;
            } catch (e) { }
        }

        switchTab(initial);
    })();

    // Campos do formulÃ¡rio
    const fieldNome = document.getElementById('nome');
    const fieldEmail = document.getElementById('email');
    const fieldCpf = document.getElementById('cpf');
    const fieldData = document.getElementById('data_nascimento');
    const fieldTelefone = document.getElementById('telefone');
    const fieldSexo = document.getElementById('sexo');
    const fieldCep = document.getElementById('end_cep');
    const fieldRua = document.getElementById('end_rua');
    const fieldNumero = document.getElementById('end_numero');
    const fieldComplemento = document.getElementById('end_complemento');
    const fieldBairro = document.getElementById('end_bairro');
    const fieldCidade = document.getElementById('end_cidade');
    const fieldEstado = document.getElementById('end_estado');
    const emailPendingNoticeId = 'email-pending-notice';

    function updateEmailPendingNotice(user = {}) {
        if (!fieldEmail) return;

        const currentEmail = String(user.email || '').trim();
        const pendingEmail = String(user.pending_email || '').trim();
        const hasPending = Boolean(user.email_change_pending) && pendingEmail !== '';

        fieldEmail.value = hasPending ? pendingEmail : currentEmail;

        const group = fieldEmail.closest('.form-group');
        if (!group) return;

        let note = document.getElementById(emailPendingNoticeId);
        if (!hasPending) {
            if (note) note.remove();
            return;
        }

        if (!note) {
            note = document.createElement('small');
            note.id = emailPendingNoticeId;
            note.style.display = 'block';
            note.style.marginTop = '6px';
            note.style.color = '#d97706';
            group.appendChild(note);
        }

        note.textContent = `Novo e-mail pendente de confirmacao: ${pendingEmail}. O login continua com o e-mail atual ate a confirmacao.`;
    }

    function maskCEP(value) {
        const digits = value.replace(/\D/g, '');
        if (digits.length <= 5) return digits;
        return digits.substring(0, 5) + '-' + digits.substring(5, 8);
    }

    // ============================================
    // AVATAR UPLOAD / REMOVE
    // ============================================
    const avatarImg = document.getElementById('avatarImg');
    const avatarInitials = document.getElementById('avatarInitials');
    const avatarEditBtn = document.getElementById('avatarEditBtn');
    const avatarInput = document.getElementById('avatarInput');

    if (avatarImg) {
        avatarImg.addEventListener('error', () => {
            avatarImg.style.display = 'none';
            if (avatarInitials) avatarInitials.style.display = '';
        });
    }

    function updateAvatarDisplay(avatarUrl, nome) {
        if (avatarUrl) {
            if (avatarImg) {
                avatarImg.src = avatarUrl;
                avatarImg.style.display = 'block';
            }
            if (avatarInitials) avatarInitials.style.display = 'none';
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

    function getCsrf() {
        return form?.querySelector('input[name="csrf_token"]')?.value
            || document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    if (avatarEditBtn && avatarInput) {
        avatarEditBtn.addEventListener('click', () => {
            // Se jÃ¡ tem avatar, pergunta se quer trocar ou remover
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
                            removeAvatar();
                        }
                    });
                } else {
                    avatarInput.click();
                }
            } else {
                avatarInput.click();
            }
        });

        avatarInput.addEventListener('change', async () => {
            const file = avatarInput.files?.[0];
            if (!file) return;

            // ValidaÃ§Ã£o client-side
            const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                if (window.Swal) Swal.fire({ icon: 'error', title: 'Tipo invÃ¡lido', text: 'Use JPEG, PNG ou WebP.', confirmButtonColor: '#e74c3c' });
                avatarInput.value = '';
                return;
            }
            if (file.size > 2 * 1024 * 1024) {
                if (window.Swal) Swal.fire({ icon: 'error', title: 'Arquivo muito grande', text: 'O tamanho mÃ¡ximo Ã© 2MB.', confirmButtonColor: '#e74c3c' });
                avatarInput.value = '';
                return;
            }

            const fd = new FormData();
            fd.append('avatar', file);
            fd.append('csrf_token', getCsrf());

            // Loading state
            if (avatarEditBtn) avatarEditBtn.disabled = true;

            try {
                const j = await apiFetch(`${API}perfil/avatar`, {
                    method: 'POST',
                    credentials: 'include',
                    body: fd,
                });

                if (j?.success === false) {
                    throw new Error(getErrorMessage({ data: j }, 'Falha ao enviar foto.'));
                }

                updateAvatarDisplay(j.data?.avatar, fieldNome?.value);

                if (window.Swal) {
                    Swal.fire({ icon: 'success', title: 'Foto atualizada!', timer: 1500, showConfirmButton: false });
                }
            } catch (err) {
                console.error('Erro ao enviar avatar:', err);
                if (window.Swal) Swal.fire({ icon: 'error', title: 'Erro', text: getErrorMessage(err, 'Falha ao enviar foto.'), confirmButtonColor: '#e74c3c' });
            } finally {
                avatarInput.value = '';
                if (avatarEditBtn) avatarEditBtn.disabled = false;
            }
        });
    }

    async function removeAvatar() {
        if (avatarEditBtn) avatarEditBtn.disabled = true;
        try {
            const j = await apiDelete(`${API}perfil/avatar`);

            if (j?.success === false) {
                throw new Error(getErrorMessage({ data: j }, 'Falha ao remover foto.'));
            }

            updateAvatarDisplay('', fieldNome?.value);

            if (window.Swal) {
                Swal.fire({ icon: 'success', title: 'Foto removida', timer: 1500, showConfirmButton: false });
            }
        } catch (err) {
            console.error('Erro ao remover avatar:', err);
            if (window.Swal) Swal.fire({ icon: 'error', title: 'Erro', text: getErrorMessage(err, 'Falha ao remover foto.'), confirmButtonColor: '#e74c3c' });
        } finally {
            if (avatarEditBtn) avatarEditBtn.disabled = false;
        }
    }

    async function loadProfile() {
        if (!form) return;
        try {
            const j = await apiGet(`${API}perfil`);
            if (j?.success === false) {
                throw new Error(getErrorMessage({ data: j }, 'Falha ao carregar perfil.'));
            }

            const user = j?.data?.user || {};

            if (fieldNome) fieldNome.value = user.nome || '';
            updateEmailPendingNotice(user);

            // Avatar
            updateAvatarDisplay(user.avatar, user.nome);

            // CÃ³digo de suporte
            const supportCodeField = document.getElementById('support_code');
            if (supportCodeField) supportCodeField.value = user.support_code || '-';

            if (fieldCpf) fieldCpf.value = user.cpf || '';
            if (fieldData) fieldData.value = user.data_nascimento || '';
            if (fieldTelefone) fieldTelefone.value = user.telefone || '';
            if (fieldSexo) fieldSexo.value = user.sexo || '';

            const endereco = user.endereco || {};
            if (fieldCep) fieldCep.value = maskCEP(endereco.cep || '');
            if (fieldRua) fieldRua.value = endereco.rua || '';
            if (fieldNumero) fieldNumero.value = endereco.numero || '';
            if (fieldComplemento) fieldComplemento.value = endereco.complemento || '';
            if (fieldBairro) fieldBairro.value = endereco.bairro || '';
            if (fieldCidade) fieldCidade.value = endereco.cidade || '';
            if (fieldEstado) fieldEstado.value = endereco.estado || '';

        } catch (err) {
            console.error('Erro ao carregar perfil:', err);
            if (window.Swal) {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro ao carregar',
                    text: getErrorMessage(err, 'NÃ£o foi possÃ­vel carregar o perfil.'),
                    confirmButtonColor: '#e74c3c'
                });
            }
        }
    }

    form?.addEventListener('submit', async (e) => {
        e.preventDefault();
        form.classList.add('form-loading');

        const submitBtn = e.submitter || form.querySelector('.btn-save');
        const originalContent = submitBtn?.innerHTML || '';
        if (submitBtn) {
            submitBtn.innerHTML = '<span class="spinner"></span><span>Salvando...</span>';
            submitBtn.disabled = true;
        }

        // Detect which tab panel the submit button belongs to
        const panel = submitBtn?.closest('.profile-tab-panel');
        const panelId = panel?.id || '';

        // If security tab â†’ password change endpoint
        if (panelId === 'panel-seguranca') {
            const senhaAtual = document.getElementById('senha_atual')?.value || '';
            const novaSenha = document.getElementById('nova_senha')?.value || '';
            const confSenha = document.getElementById('conf_senha')?.value || '';

            // â”€â”€ Client-side validation (mirrors backend rules) â”€â”€
            const pwdErrors = [];
            if (!senhaAtual || !novaSenha || !confSenha) {
                pwdErrors.push('Todos os campos de senha sÃ£o obrigatÃ³rios.');
            }
            if (novaSenha.length < 8) pwdErrors.push('A senha deve ter no mÃ­nimo 8 caracteres.');
            if (!/[a-z]/.test(novaSenha)) pwdErrors.push('A senha deve conter pelo menos uma letra minÃºscula.');
            if (!/[A-Z]/.test(novaSenha)) pwdErrors.push('A senha deve conter pelo menos uma letra maiÃºscula.');
            if (!/[0-9]/.test(novaSenha)) pwdErrors.push('A senha deve conter pelo menos um nÃºmero.');
            if (!/[^a-zA-Z0-9]/.test(novaSenha)) pwdErrors.push('A senha deve conter pelo menos um caractere especial.');
            if (novaSenha && confSenha && novaSenha !== confSenha) {
                pwdErrors.push('As senhas nÃ£o coincidem.');
            }

            if (pwdErrors.length > 0) {
                // Highlight failing indicators
                const strengthPanel = document.getElementById('pwdStrengthProfile');
                if (strengthPanel) strengthPanel.classList.add('visible');

                if (window.Swal) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Senha nÃ£o atende aos requisitos',
                        html: '<ul style="text-align:left;margin:0;padding-left:1.2em">' +
                            pwdErrors.map(e => '<li>' + e + '</li>').join('') + '</ul>',
                        confirmButtonColor: '#e67e22'
                    });
                }
                form.classList.remove('form-loading');
                if (submitBtn) { submitBtn.innerHTML = originalContent; submitBtn.disabled = false; }
                return;
            }

            const fd = new FormData();
            fd.append('senha_atual', senhaAtual);
            fd.append('nova_senha', novaSenha);
            fd.append('conf_senha', confSenha);

            // Include CSRF token
            const csrfInput = form.querySelector('input[name="csrf_token"]') || form.querySelector('input[name="_token"]');
            if (csrfInput) fd.append(csrfInput.name, csrfInput.value);

            try {
                const j = await apiFetch(`${API}perfil/senha`, {
                    method: 'POST',
                    credentials: 'include',
                    body: fd,
                });

                if (j?.success === false) {
                    throw new Error(getErrorMessage({ data: j }, 'Falha ao alterar senha.'));
                }

                // Clear password fields
                document.getElementById('senha_atual').value = '';
                document.getElementById('nova_senha').value = '';
                document.getElementById('conf_senha').value = '';

                // Hide strength panel
                const strengthPanel = document.getElementById('pwdStrengthProfile');
                if (strengthPanel) strengthPanel.classList.remove('visible');
                const matchPanel = document.getElementById('pwdMatchProfile');
                if (matchPanel) matchPanel.classList.remove('visible');

                if (window.Swal) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Senha alterada!',
                        text: 'Sua senha foi atualizada com sucesso.',
                        confirmButtonColor: '#e67e22',
                        timer: 2000
                    });
                }
            } catch (err) {
                console.error('Erro ao alterar senha:', err);
                if (window.Swal) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro ao alterar senha',
                        text: getErrorMessage(err, 'Erro ao alterar senha.'),
                        confirmButtonColor: '#e74c3c'
                    });
                }
            } finally {
                form.classList.remove('form-loading');
                if (submitBtn) {
                    submitBtn.innerHTML = originalContent;
                    submitBtn.disabled = false;
                }
            }
            return;
        }

        // For dados/endereco tabs â†’ send only relevant fields
        const fd = new FormData();

        // Always include CSRF token
        const csrfInput = form.querySelector('input[name="csrf_token"]') || form.querySelector('input[name="_token"]');
        if (csrfInput) fd.append(csrfInput.name, csrfInput.value);

        // Collect only fields from the active panel (or all non-password fields)
        const activeFields = panel
            ? panel.querySelectorAll('input[name]:not([type="password"]):not([name^="_fake"]), select[name], textarea[name]')
            : form.querySelectorAll('input[name]:not([type="password"]):not([name^="_fake"]), select[name], textarea[name]');

        activeFields.forEach(field => {
            if (field.name && !field.name.startsWith('_fake')) {
                fd.append(field.name, field.value);
            }
        });

        // For endereco tab, also include personal data fields (API expects all profile fields)
        // For dados tab, also include endereco fields (API expects all profile fields)
        // Since the API replaces all fields, we need to send everything from both tabs
        if (panelId === 'panel-dados' || panelId === 'panel-endereco') {
            const otherPanelId = panelId === 'panel-dados' ? 'panel-endereco' : 'panel-dados';
            const otherPanel = document.getElementById(otherPanelId);
            if (otherPanel) {
                const otherFields = otherPanel.querySelectorAll('input[name]:not([type="password"]):not([name^="_fake"]), select[name], textarea[name]');
                otherFields.forEach(field => {
                    if (field.name && !field.name.startsWith('_fake') && !fd.has(field.name)) {
                        fd.append(field.name, field.value);
                    }
                });
            }
        }

        try {
            const j = await apiFetch(`${API}perfil`, {
                method: 'POST',
                credentials: 'include',
                body: fd,
            });

            if (j?.success === false) {
                throw new Error(getErrorMessage({ data: j }, 'Falha ao salvar.'));
            }

            // GAMIFICAÃ‡ÃƒO: Exibir conquistas se houver
            if (j?.data?.new_achievements && Array.isArray(j.data.new_achievements)) {
                if (typeof window.notifyMultipleAchievements === 'function') {
                    window.notifyMultipleAchievements(j.data.new_achievements);
                }
            }

            if (window.Swal) {
                const emailChangePending = Boolean(j?.data?.email_change_pending);
                const emailVerificationSent = Boolean(j?.data?.email_verification_sent);

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
                    timer: 2200
                });
            }

            const saveStatus = document.getElementById('save-status');
            if (saveStatus) {
                saveStatus.innerHTML = 'âœ“ Tudo salvo';
                saveStatus.style.color = '#27ae60';
            }

            await loadProfile();

        } catch (err) {
            console.error('Erro ao salvar:', err);
            if (window.Swal) {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro ao salvar',
                    text: getErrorMessage(err, 'Erro ao salvar perfil.'),
                    confirmButtonColor: '#e74c3c'
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

    // BotÃ£o de excluir conta
    const btnDelete = document.getElementById('btn-delete-account');
    if (btnDelete) {
        btnDelete.addEventListener('click', async () => {
            if (!window.Swal) {
                if (!confirm(
                    'ATENÃ‡ÃƒO: Esta aÃ§Ã£o Ã© irreversÃ­vel! Deseja realmente excluir sua conta e todos os dados?'
                )) return;
            } else {
                const result = await Swal.fire({
                    title: 'Confirmar ExclusÃ£o de Conta',
                    html: `
                        <div style="text-align: left; padding: 1rem;">
                            <p style="font-size: 1.1rem; margin-bottom: 1rem;"><strong>Esta aÃ§Ã£o Ã© permanente e irreversÃ­vel!</strong></p>
                            <p style="margin-bottom: 0.5rem;">Ao confirmar, os seguintes dados serÃ£o <strong>permanentemente deletados</strong>:</p>
                            <ul style="margin: 1rem 0; padding-left: 1.5rem;">
                                <li>Todos os lanÃ§amentos e histÃ³rico financeiro</li>
                                <li>Contas e cartÃµes cadastrados</li>
                                <li>Categorias personalizadas</li>
                                <li>Metas e agendamentos</li>
                                <li>InformaÃ§Ãµes pessoais</li>
                                <li>Plano PRO (serÃ¡ cancelado automaticamente)</li>
                            </ul>
                            <p style="color: #e74c3c; font-weight: bold; margin-top: 1rem;">NÃ£o serÃ¡ possÃ­vel recuperar estes dados!</p>
                            <p style="color: #7f8c8d; font-size: 0.9rem; margin-top: 1rem;">ApÃ³s a exclusÃ£o, vocÃª precisarÃ¡ aguardar <strong>90 dias</strong> para criar uma nova conta com o mesmo email.</p>
                        </div>
                    `,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e74c3c',
                    cancelButtonColor: '#95a5a6',
                    confirmButtonText: 'Sim, excluir minha conta',
                    cancelButtonText: 'Cancelar',
                    focusCancel: true
                });

                if (!result.isConfirmed) return;

                // Segunda confirmaÃ§Ã£o
                const finalConfirm = await Swal.fire({
                    title: 'Ãšltima confirmaÃ§Ã£o',
                    text: 'Digite "EXCLUIR" para confirmar a exclusÃ£o definitiva da sua conta',
                    input: 'text',
                    inputPlaceholder: 'Digite: EXCLUIR',
                    showCancelButton: true,
                    confirmButtonColor: '#e74c3c',
                    cancelButtonColor: '#95a5a6',
                    confirmButtonText: 'Confirmar ExclusÃ£o',
                    cancelButtonText: 'Cancelar',
                    inputValidator: (value) => {
                        if (value !== 'EXCLUIR') {
                            return 'VocÃª precisa digitar "EXCLUIR" para confirmar';
                        }
                    }
                });

                if (!finalConfirm.isConfirmed) return;
            }

            try {
                btnDelete.disabled = true;
                Swal.fire({
                    title: 'Excluindo conta...',
                    text: 'Por favor aguarde',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                const data = await apiDelete(`${API}perfil/delete`);

                if (data?.success === false) {
                    throw new Error(getErrorMessage({ data }, 'Erro ao excluir conta'));
                }

                await Swal.fire({
                    icon: 'success',
                    title: 'Conta excluÃ­da!',
                    html: `
                            <div style="text-align: center;">
                                <p style="font-size: 1.1rem; margin-bottom: 0.5rem;">Sua conta foi excluÃ­da com sucesso.</p>
                                <p style="color: #666;">VocÃª serÃ¡ redirecionado para a pÃ¡gina inicial...</p>
                            </div>
                        `,
                    timer: 3000,
                    timerProgressBar: true,
                    showConfirmButton: false,
                    allowOutsideClick: false
                });

                window.location.href = BASE + 'logout';

            } catch (err) {
                console.error('Erro ao excluir conta:', err);
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: getErrorMessage(err, 'NÃ£o foi possÃ­vel excluir a conta. Tente novamente.')
                });
            } finally {
                btnDelete.disabled = false;
            }
        });
    }

    // ============================================
    // SISTEMA DE INDICAÃ‡ÃƒO
    // ============================================

    async function loadReferralStats() {
        try {
            const data = await apiGet(`${API}referral/stats`);

            if (data?.data) {
                const stats = data.data;

                // Atualiza cÃ³digo e link
                const codeInput = document.getElementById('referral-code');
                const linkInput = document.getElementById('referral-link');

                if (codeInput) codeInput.value = stats.referral_code || '';
                if (linkInput) linkInput.value = stats.referral_link || '';

                // Atualiza estatÃ­sticas
                document.getElementById('stat-total').textContent = stats.total_indicacoes || 0;
                document.getElementById('stat-completed').textContent = stats.indicacoes_completadas || 0;
                document.getElementById('stat-days').textContent = stats.dias_ganhos || 0;

                // Atualiza barra de limite mensal
                const current = stats.indicacoes_mes || 0;
                const max = stats.limite_mensal || 5;
                const remaining = stats.indicacoes_restantes ?? max;
                const percentage = Math.min((current / max) * 100, 100);

                document.getElementById('limit-current').textContent = current;
                document.getElementById('limit-max').textContent = max;

                const barFill = document.getElementById('limit-bar-fill');
                const barHint = document.getElementById('limit-bar-hint');
                const limitBar = document.getElementById('referral-limit-bar');

                if (barFill) {
                    barFill.style.width = percentage + '%';

                    // Muda cor conforme enche
                    if (percentage >= 100) {
                        barFill.classList.add('full');
                        barFill.classList.remove('warning');
                    } else if (percentage >= 80) {
                        barFill.classList.add('warning');
                        barFill.classList.remove('full');
                    } else {
                        barFill.classList.remove('warning', 'full');
                    }
                }

                if (barHint) {
                    if (remaining === 0) {
                        barHint.textContent = 'Limite atingido! Renova no prÃ³ximo mÃªs';
                        barHint.classList.add('limit-reached');
                    } else if (remaining === 1) {
                        barHint.textContent = 'Ãšltima indicaÃ§Ã£o disponÃ­vel este mÃªs';
                        barHint.classList.remove('limit-reached');
                    } else {
                        barHint.textContent = `VocÃª pode indicar mais ${remaining} amigos este mÃªs`;
                        barHint.classList.remove('limit-reached');
                    }
                }
            }
        } catch (err) {
            console.error('Erro ao carregar estatÃ­sticas de indicaÃ§Ã£o:', err);
        }
    }

    function copyToClipboard(text, button) {
        navigator.clipboard.writeText(text).then(() => {
            const originalIcon = button.innerHTML;
            button.innerHTML = '<i data-lucide="check"></i>';
            button.classList.add('copied');
            if (window.lucide) lucide.createIcons();

            setTimeout(() => {
                button.innerHTML = originalIcon;
                button.classList.remove('copied');
                if (window.lucide) lucide.createIcons();
            }, 2000);
        }).catch(err => {
            console.error('Erro ao copiar:', err);
        });
    }

    // BotÃµes de copiar
    document.getElementById('btn-copy-code')?.addEventListener('click', () => {
        const code = document.getElementById('referral-code')?.value;
        if (code) copyToClipboard(code, document.getElementById('btn-copy-code'));
    });

    document.getElementById('btn-copy-link')?.addEventListener('click', () => {
        const link = document.getElementById('referral-link')?.value;
        if (link) copyToClipboard(link, document.getElementById('btn-copy-link'));
    });

    // BotÃµes de compartilhamento
    document.getElementById('btn-share-whatsapp')?.addEventListener('click', () => {
        const link = document.getElementById('referral-link')?.value;
        const text = encodeURIComponent(
            `ðŸŽ Use meu cÃ³digo e ganhe 7 dias de PRO grÃ¡tis no Lukrato!\n\n${link}`);
        window.open(`https://wa.me/?text=${text}`, '_blank');
    });

    document.getElementById('btn-share-telegram')?.addEventListener('click', () => {
        const link = document.getElementById('referral-link')?.value;
        const text = encodeURIComponent(`ðŸŽ Use meu cÃ³digo e ganhe 7 dias de PRO grÃ¡tis no Lukrato!`);
        window.open(`https://t.me/share/url?url=${encodeURIComponent(link)}&text=${text}`, '_blank');
    });

    document.getElementById('btn-share-instagram')?.addEventListener('click', () => {
        const link = document.getElementById('referral-link')?.value;
        navigator.clipboard.writeText(link).then(() => {
            if (window.Swal) {
                Swal.fire({
                    icon: 'success',
                    title: 'Link copiado!',
                    html: 'Cole nos seus Stories ou Direct do Instagram!',
                    confirmButtonColor: '#e67e22',
                    timer: 3000,
                    timerProgressBar: true
                });
            } else {
                alert('Link copiado! Cole nos seus Stories ou Direct do Instagram.');
            }
        });
    });

    // Carregar estatÃ­sticas de indicaÃ§Ã£o
    if (document.getElementById('referral-stats')) {
        loadReferralStats();
    }

    // ============================================
    // INTEGRAÃ‡Ã•ES â€” WhatsApp & Telegram
    // ============================================

    const csrfToken = document.querySelector('input[name="csrf_token"]')?.value
        || document.querySelector('meta[name="csrf-token"]')?.content || '';

    function buildIntegrationFormData(fields = {}) {
        const formData = new FormData();
        Object.entries(fields).forEach(([key, value]) => {
            if (value !== undefined && value !== null) {
                formData.append(key, value);
            }
        });
        formData.append('csrf_token', csrfToken);
        return formData;
    }

    // --- WhatsApp ---
    async function loadWhatsAppStatus() {
        try {
            const json = await apiGet(`${API}whatsapp/status`);
            const data = json.data || {};
            const statusEl = document.getElementById('whatsapp-status');
            if (data.linked) {
                statusEl.innerHTML = '<span class="status-indicator linked"></span><span class="status-text">Vinculado</span>';
                document.getElementById('whatsapp-not-linked').style.display = 'none';
                document.getElementById('whatsapp-verify').style.display = 'none';
                document.getElementById('whatsapp-linked').style.display = '';
                document.getElementById('whatsapp-masked-phone').textContent = data.phone || '';
            } else {
                statusEl.innerHTML = '<span class="status-indicator not-linked"></span><span class="status-text">NÃ£o vinculado</span>';
                document.getElementById('whatsapp-not-linked').style.display = '';
                document.getElementById('whatsapp-verify').style.display = 'none';
                document.getElementById('whatsapp-linked').style.display = 'none';
            }
        } catch (e) { /* silent */ }
    }

    document.getElementById('btn-whatsapp-link')?.addEventListener('click', async () => {
        const phone = document.getElementById('whatsapp-phone')?.value?.trim();
        if (!phone) return;
        const btn = document.getElementById('btn-whatsapp-link');
        btn.disabled = true;
        try {
            const json = await apiFetch(`${API}whatsapp/link`, {
                method: 'POST',
                credentials: 'same-origin',
                body: buildIntegrationFormData({ phone }),
            });
            if (json.success) {
                document.getElementById('whatsapp-not-linked').style.display = 'none';
                document.getElementById('whatsapp-verify').style.display = '';
                document.getElementById('whatsapp-verify-msg').textContent = json.message;
            } else if (window.Swal) {
                Swal.fire({ icon: 'error', title: 'Erro', text: getErrorMessage({ data: json }, 'Erro desconhecido'), confirmButtonColor: '#e67e22' });
            }
        } catch (e) {
            if (window.Swal) Swal.fire({ icon: 'error', title: 'Erro', text: getErrorMessage(e, 'Erro de conexÃ£o'), confirmButtonColor: '#e67e22' });
        } finally {
            btn.disabled = false;
        }
    });

    document.getElementById('btn-whatsapp-verify')?.addEventListener('click', async () => {
        const phone = document.getElementById('whatsapp-phone')?.value?.trim();
        const code = document.getElementById('whatsapp-code')?.value?.trim();
        if (!phone || !code) return;
        const btn = document.getElementById('btn-whatsapp-verify');
        btn.disabled = true;
        try {
            const json = await apiFetch(`${API}whatsapp/verify`, {
                method: 'POST',
                credentials: 'same-origin',
                body: buildIntegrationFormData({ phone, code }),
            });
            if (json.success) {
                if (window.Swal) Swal.fire({ icon: 'success', title: 'Vinculado!', text: json.message, confirmButtonColor: '#e67e22', timer: 2500, timerProgressBar: true });
                loadWhatsAppStatus();
            } else if (window.Swal) {
                Swal.fire({ icon: 'error', title: 'Erro', text: getErrorMessage({ data: json }, 'Erro desconhecido'), confirmButtonColor: '#e67e22' });
            }
        } catch (e) {
            if (window.Swal) Swal.fire({ icon: 'error', title: 'Erro', text: getErrorMessage(e, 'Erro de conexÃ£o'), confirmButtonColor: '#e67e22' });
        } finally {
            btn.disabled = false;
        }
    });

    document.getElementById('btn-whatsapp-unlink')?.addEventListener('click', async () => {
        const result = window.Swal ? await Swal.fire({
            icon: 'warning', title: 'Desvincular WhatsApp?',
            text: 'VocÃª nÃ£o poderÃ¡ mais enviar lanÃ§amentos pelo WhatsApp.',
            showCancelButton: true, confirmButtonText: 'Desvincular', cancelButtonText: 'Cancelar',
            confirmButtonColor: '#ef4444',
        }) : { isConfirmed: confirm('Desvincular WhatsApp?') };
        if (!result.isConfirmed) return;
        try {
            const json = await apiFetch(`${API}whatsapp/unlink`, {
                method: 'POST',
                credentials: 'same-origin',
                body: buildIntegrationFormData(),
            });
            if (json.success) loadWhatsAppStatus();
        } catch (e) { /* silent */ }
    });

    // --- Telegram ---
    let telegramLinkPending = false;
    let telegramLinkPollTimer = null;
    let telegramLinkCountdownTimer = null;
    let telegramLinkExpiresAt = 0;
    let telegramLinkSuccessToastShown = false;

    function clearTelegramLinkTimers() {
        if (telegramLinkPollTimer) clearInterval(telegramLinkPollTimer);
        if (telegramLinkCountdownTimer) clearInterval(telegramLinkCountdownTimer);
        telegramLinkPollTimer = null;
        telegramLinkCountdownTimer = null;
        telegramLinkExpiresAt = 0;
    }

    function setTelegramLinkMeta(statusText = '', countdownText = '') {
        const statusCopyEl = document.getElementById('telegram-link-status-copy');
        const countdownEl = document.getElementById('telegram-link-countdown');
        if (statusCopyEl) statusCopyEl.textContent = statusText;
        if (countdownEl) countdownEl.textContent = countdownText;
    }

    function renderTelegramQr(data = {}) {
        const qrWrapper = document.getElementById('telegram-qr-wrapper');
        const qrImage = document.getElementById('telegram-qr-image');
        if (!qrWrapper || !qrImage) return;

        if (data.qr_code_data_uri) {
            qrImage.src = data.qr_code_data_uri;
            qrWrapper.classList.add('is-visible');
            return;
        }

        qrImage.removeAttribute('src');
        qrWrapper.classList.remove('is-visible');
    }

    function renderTelegramAwaitingState(data = {}) {
        telegramLinkPending = true;
        telegramLinkSuccessToastShown = false;

        const statusEl = document.getElementById('telegram-status');
        if (statusEl) {
            statusEl.innerHTML = '<span class="status-indicator pending"></span><span class="status-text">Aguardando confirmacao</span>';
        }

        document.getElementById('telegram-not-linked').style.display = 'none';
        document.getElementById('telegram-code-generated').style.display = '';
        document.getElementById('telegram-linked').style.display = 'none';
        document.getElementById('telegram-code-display').value = data.code || '';
        document.getElementById('telegram-bot-link').href = data.bot_url || '#';
        setTelegramLinkMeta('Abra o bot, envie o codigo e aguarde a confirmacao.', '');
        renderTelegramQr(data);
    }

    function startTelegramLinkTracking(expiresIn = 600) {
        clearTelegramLinkTimers();
        telegramLinkExpiresAt = Date.now() + Math.max(1, expiresIn) * 1000;

        const updateCountdown = () => {
            const remainingMs = telegramLinkExpiresAt - Date.now();
            if (remainingMs <= 0) {
                clearTelegramLinkTimers();
                telegramLinkPending = false;
                setTelegramLinkMeta('Codigo expirado. Gere um novo para continuar.', '');
                const statusEl = document.getElementById('telegram-status');
                if (statusEl) {
                    statusEl.innerHTML = '<span class="status-indicator not-linked"></span><span class="status-text">Codigo expirado</span>';
                }
                return;
            }

            const totalSeconds = Math.ceil(remainingMs / 1000);
            const minutes = String(Math.floor(totalSeconds / 60)).padStart(2, '0');
            const seconds = String(totalSeconds % 60).padStart(2, '0');
            setTelegramLinkMeta('Abra o bot, envie o codigo e aguarde a confirmacao.', `Expira em ${minutes}:${seconds}`);
        };

        updateCountdown();
        telegramLinkCountdownTimer = setInterval(updateCountdown, 1000);
        telegramLinkPollTimer = setInterval(() => {
            loadTelegramStatus({ preservePending: true, showToast: true });
        }, 5000);
    }

    async function loadTelegramStatus(options = {}) {
        const { preservePending = false, showToast = false } = options;
        try {
            const json = await apiGet(`${API}telegram/status`);
            const data = json.data || {};
            const statusEl = document.getElementById('telegram-status');
            if (data.linked) {
                telegramLinkPending = false;
                clearTelegramLinkTimers();
                statusEl.innerHTML = '<span class="status-indicator linked"></span><span class="status-text">Vinculado</span>';
                document.getElementById('telegram-not-linked').style.display = 'none';
                document.getElementById('telegram-code-generated').style.display = 'none';
                document.getElementById('telegram-linked').style.display = '';
                const linkedHandle = document.getElementById('telegram-linked-handle');
                if (linkedHandle) linkedHandle.textContent = data.username ? `Chat ${data.username}` : 'Pronto para uso';
                setTelegramLinkMeta('', '');
                renderTelegramQr();
                if (showToast && !telegramLinkSuccessToastShown && window.Swal) {
                    telegramLinkSuccessToastShown = true;
                    Swal.fire({
                        icon: 'success',
                        title: 'Telegram vinculado',
                        text: 'Agora vocÃª pode usar o bot normalmente.',
                        confirmButtonColor: '#0ea5e9',
                        timer: 2500,
                        timerProgressBar: true,
                    });
                }
            } else {
                if (preservePending && telegramLinkPending) {
                    statusEl.innerHTML = '<span class="status-indicator pending"></span><span class="status-text">Aguardando confirmacao</span>';
                    document.getElementById('telegram-not-linked').style.display = 'none';
                    document.getElementById('telegram-code-generated').style.display = '';
                    document.getElementById('telegram-linked').style.display = 'none';
                    return;
                }

                telegramLinkPending = false;
                clearTelegramLinkTimers();
                statusEl.innerHTML = '<span class="status-indicator not-linked"></span><span class="status-text">NÃ£o vinculado</span>';
                document.getElementById('telegram-not-linked').style.display = '';
                document.getElementById('telegram-code-generated').style.display = 'none';
                document.getElementById('telegram-linked').style.display = 'none';
                const linkedHandle = document.getElementById('telegram-linked-handle');
                if (linkedHandle) linkedHandle.textContent = '';
                setTelegramLinkMeta('', '');
                renderTelegramQr();
            }
        } catch (e) { /* silent */ }
    }

    document.getElementById('btn-telegram-link')?.addEventListener('click', async () => {
        const btn = document.getElementById('btn-telegram-link');
        const regenerateBtn = document.getElementById('btn-telegram-regenerate');
        btn.disabled = true;
        if (regenerateBtn) regenerateBtn.disabled = true;
        try {
            const json = await apiFetch(`${API}telegram/link`, {
                method: 'POST',
                credentials: 'same-origin',
                body: buildIntegrationFormData(),
            });
            if (json.success) {
                renderTelegramAwaitingState(json.data || {});
                startTelegramLinkTracking(json.data?.expires_in || 600);

                document.getElementById('telegram-not-linked').style.display = 'none';
                document.getElementById('telegram-code-generated').style.display = '';
                document.getElementById('telegram-code-display').value = json.data.code;
                document.getElementById('telegram-bot-link').href = json.data.bot_url;
                const qrWrapper = document.getElementById('telegram-qr-wrapper');
                const qrImage = document.getElementById('telegram-qr-image');
                if (qrWrapper && qrImage) {
                    if (json.data.qr_code_data_uri) {
                        qrImage.src = json.data.qr_code_data_uri;
                        qrWrapper.classList.add('is-visible');
                    } else {
                        qrImage.removeAttribute('src');
                        qrWrapper.classList.remove('is-visible');
                    }
                }
            } else if (window.Swal) {
                Swal.fire({ icon: 'error', title: 'Erro', text: getErrorMessage({ data: json }, 'Erro desconhecido'), confirmButtonColor: '#e67e22' });
            }
        } catch (e) {
            if (window.Swal) Swal.fire({ icon: 'error', title: 'Erro', text: getErrorMessage(e, 'Erro de conexÃ£o'), confirmButtonColor: '#e67e22' });
        } finally {
            btn.disabled = false;
            if (regenerateBtn) regenerateBtn.disabled = false;
        }
    });

    document.getElementById('btn-telegram-regenerate')?.addEventListener('click', () => {
        document.getElementById('btn-telegram-link')?.click();
    });

    document.getElementById('btn-copy-telegram-code')?.addEventListener('click', () => {
        const input = document.getElementById('telegram-code-display');
        const btn = document.getElementById('btn-copy-telegram-code');
        if (!input?.value || !btn) return;
        const orig = btn.innerHTML;
        navigator.clipboard.writeText(input.value).then(() => {
            btn.innerHTML = '<i data-lucide="check"></i>';
            btn.style.color = '#22c55e';
            if (window.lucide) lucide.createIcons();
            setTimeout(() => { btn.innerHTML = orig; btn.style.color = ''; if (window.lucide) lucide.createIcons(); }, 2000);
        }).catch(() => {
            input.select();
            document.execCommand('copy');
        });
    });

    document.getElementById('btn-telegram-unlink')?.addEventListener('click', async () => {
        const result = window.Swal ? await Swal.fire({
            icon: 'warning', title: 'Desvincular Telegram?',
            text: 'VocÃª nÃ£o poderÃ¡ mais enviar lanÃ§amentos pelo Telegram.',
            showCancelButton: true, confirmButtonText: 'Desvincular', cancelButtonText: 'Cancelar',
            confirmButtonColor: '#ef4444',
        }) : { isConfirmed: confirm('Desvincular Telegram?') };
        if (!result.isConfirmed) return;
        try {
            const json = await apiFetch(`${API}telegram/unlink`, {
                method: 'POST',
                credentials: 'same-origin',
                body: buildIntegrationFormData(),
            });
            if (json.success) {
                telegramLinkPending = false;
                clearTelegramLinkTimers();
                loadTelegramStatus();
            }
        } catch (e) { /* silent */ }
    });

    // Load integration statuses
    if (document.getElementById('whatsapp-card')) {
        loadWhatsAppStatus();
    }
    if (document.getElementById('telegram-card')) {
        loadTelegramStatus();
    }
    loadProfile();
})();

function copySupportCode() {
    const input = document.getElementById('support_code');
    const btn = document.getElementById('btn-copy-support');
    if (!input || !input.value || !btn) return;

    const originalIcon = btn.innerHTML;
    const showCheck = () => {
        btn.innerHTML = '<i data-lucide="check"></i>';
        btn.style.color = '#22c55e';
        if (window.lucide) lucide.createIcons();
        setTimeout(() => {
            btn.innerHTML = originalIcon;
            btn.style.color = '';
            if (window.lucide) lucide.createIcons();
        }, 2000);
    };

    navigator.clipboard.writeText(input.value).then(showCheck).catch(() => {
        input.select();
        document.execCommand('copy');
        showCheck();
    });
}

// Real-time password strength + confirm match (Profile)
(function () {
    var pwd = document.getElementById('nova_senha');
    var confirm = document.getElementById('conf_senha');
    var panel = document.getElementById('pwdStrengthProfile');
    var barFill = document.getElementById('pwdBarFillProfile');
    var levelEl = document.getElementById('pwdLevelProfile');
    var matchEl = document.getElementById('pwdMatchProfile');
    if (!pwd || !confirm || !panel) return;

    var rules = [{
        id: 'prof-req-length',
        test: function (v) {
            return v.length >= 8;
        }
    },
    {
        id: 'prof-req-lower',
        test: function (v) {
            return /[a-z]/.test(v);
        }
    },
    {
        id: 'prof-req-upper',
        test: function (v) {
            return /[A-Z]/.test(v);
        }
    },
    {
        id: 'prof-req-number',
        test: function (v) {
            return /[0-9]/.test(v);
        }
    },
    {
        id: 'prof-req-special',
        test: function (v) {
            return /[^a-zA-Z0-9]/.test(v);
        }
    }
    ];

    var levels = [{
        cls: '',
        label: ''
    },
    {
        cls: 's1',
        label: 'Muito fraca'
    },
    {
        cls: 's2',
        label: 'Fraca'
    },
    {
        cls: 's3',
        label: 'RazoÃ¡vel'
    },
    {
        cls: 's4',
        label: 'Boa'
    },
    {
        cls: 's5',
        label: 'Forte'
    }
    ];

    var saveBtn = document.getElementById('btn-save-seguranca');
    var senhaAtualInput = document.getElementById('senha_atual');

    function updateSaveBtn() {
        if (!saveBtn) return;
        var val = pwd.value;
        var allRulesPass = val && rules.every(function (rule) { return rule.test(val); });
        var match = val && confirm.value && val === confirm.value;
        var currentFilled = senhaAtualInput && senhaAtualInput.value.length > 0;
        saveBtn.disabled = !(allRulesPass && match && currentFilled);
    }

    // Start with button disabled
    if (saveBtn) saveBtn.disabled = true;

    pwd.addEventListener('focus', function () {
        panel.classList.add('visible');
    });

    pwd.addEventListener('input', function () {
        var val = pwd.value;
        var score = 0;

        if (!val) {
            panel.classList.remove('visible');
            barFill.className = 'pwd-bar-fill';
            levelEl.className = 'pwd-level';
            levelEl.textContent = '';
            rules.forEach(function (rule) {
                var el = document.getElementById(rule.id);
                if (el) el.classList.remove('pass');
            });
            updateSaveBtn();
            return;
        }

        panel.classList.add('visible');

        rules.forEach(function (rule) {
            var el = document.getElementById(rule.id);
            var passed = rule.test(val);
            if (el) el.classList.toggle('pass', passed);
            if (passed) score++;
        });

        barFill.className = 'pwd-bar-fill' + (score > 0 ? ' s' + score : '');
        levelEl.className = 'pwd-level' + (score > 0 ? ' s' + score : '');
        levelEl.textContent = levels[score].label;

        if (confirm.value) checkMatch();
        updateSaveBtn();
    });

    function checkMatch() {
        var pVal = pwd.value;
        var cVal = confirm.value;
        if (!cVal) {
            matchEl.classList.remove('visible');
            updateSaveBtn();
            return;
        }
        matchEl.classList.add('visible');
        var ok = pVal === cVal;
        matchEl.classList.toggle('match', ok);
        matchEl.classList.toggle('no-match', !ok);
        var icon = matchEl.querySelector('.match-icon');
        var text = matchEl.querySelector('.match-text');
        icon.innerHTML = ok ? '<i data-lucide="check"></i>' : '<i data-lucide="x"></i>';
        text.textContent = ok ? 'Senhas coincidem' : 'Senhas nÃ£o coincidem';
        updateSaveBtn();
    }

    confirm.addEventListener('input', checkMatch);
    pwd.addEventListener('input', function () {
        if (confirm.value) checkMatch();
    });

    // Also track current password field
    if (senhaAtualInput) {
        senhaAtualInput.addEventListener('input', updateSaveBtn);
    }
})();
