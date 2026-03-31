/**
 * ============================================================================
 * LUKRATO — SysAdmin Page (Vite Entry Point)
 * ============================================================================
 * Extraído de views/admin/sysadmin/index.php (1.663 linhas de inline JS)
 *
 * Módulos:
 * - Cache + Maintenance management
 * - User CRUD (table, filters, pagination, view, edit, delete)
 * - Grant/Revoke PRO access
 * - Error logs real-time monitoring
 * - Stats charts (ApexCharts)
 * ============================================================================
 */

import '../../../css/admin/sysadmin/index.css';
import { apiDelete, apiGet, apiPost, apiPut, getErrorMessage } from '../shared/api.js';
import { escapeHtml } from '../shared/utils.js';
import { initCustomize } from './customize.js';

const BASE_URL = (window.LK?.getBase?.() || '/');
const cleanupLogsModalEl = document.getElementById('cleanupLogsModal');
const cleanupLogsDaysEl = document.getElementById('cleanupLogsDays');
const cleanupLogsIncludeUnresolvedEl = document.getElementById('cleanupLogsIncludeUnresolved');
const cleanupLogsHintEl = document.getElementById('cleanupLogsModalHint');
const cleanupLogsConfirmBtn = document.getElementById('cleanupLogsConfirmBtn');
let cleanupLogsModalInstance = null;

function setButtonLoading(button, isLoading) {
    if (!button) return;

    button.disabled = isLoading;
    button.classList.toggle('is-loading', isLoading);
    button.style.pointerEvents = isLoading ? 'none' : '';
}

const escHtml = escapeHtml;

// ============================================================================
// CACHE MANAGEMENT
// ============================================================================

function limparCache() {
    const triggerBtn = document.getElementById('btnClearCache');
    if (window.LKFeedback) {
        LKFeedback.confirm('Isso irá remover todos os arquivos de cache do sistema.', {
            title: 'Limpar Cache?',
            icon: 'question',
            confirmButtonText: 'Sim, limpar!',
            cancelButtonText: 'Cancelar'
        }).then(async (result) => {
            if (result.isConfirmed) {
                setButtonLoading(triggerBtn, true);
                try {
                    const data = await apiPost(`${BASE_URL}api/sysadmin/clear-cache`, {});
                    if (data.success) {
                        LKFeedback.success(data.message || 'Cache limpo com sucesso.', { toast: true });
                    } else {
                        LKFeedback.error(data.message || 'Erro ao limpar cache.');
                    }
                } catch (error) {
                    console.error('Erro ao limpar cache:', error);
                    LKFeedback.error(getErrorMessage(error, 'Erro de conexão ao limpar cache.'));
                } finally {
                    setButtonLoading(triggerBtn, false);
                }
            }
        });
    } else {
        if (confirm('Tem certeza que deseja limpar o cache do sistema?')) {
            alert('Cache limpo com sucesso!');
        }
    }
}

// ============================================================================
// MAINTENANCE MODE
// ============================================================================

let maintenanceActive = false;

async function checkMaintenanceStatus() {
    try {
        const data = await apiGet(`${BASE_URL}api/sysadmin/maintenance`);
        maintenanceActive = data.active || false;
        updateMaintenanceButton();
    } catch (e) {
        console.error('Erro ao verificar manutenção:', e);
    }
}

function updateMaintenanceButton() {
    const btn = document.getElementById('btnMaintenance');
    const icon = document.getElementById('btnMaintenanceIcon');
    const text = document.getElementById('btnMaintenanceText');
    if (!btn) return;

    if (maintenanceActive) {
        btn.className = 'btn-control success';
        icon.setAttribute('data-lucide', 'circle-check');
        icon.removeAttribute('class');
        if (window.lucide) lucide.createIcons({ nodes: [icon] });
        text.textContent = 'Desativar Manutenção (ATIVO)';
    } else {
        btn.className = 'btn-control danger';
        icon.setAttribute('data-lucide', 'wrench');
        icon.removeAttribute('class');
        if (window.lucide) lucide.createIcons({ nodes: [icon] });
        text.textContent = 'Ativar Modo Manutenção';
    }
}

async function toggleMaintenance() {
    const action = maintenanceActive ? 'deactivate' : 'activate';
    const title = maintenanceActive ? 'Desativar Manutenção' : 'Ativar Modo Manutenção';
    const text = maintenanceActive
        ? 'O sistema voltará ao normal para todos os usuários.'
        : 'O site ficará indisponível para usuários (admins continuam acessando).';
    const confirmText = maintenanceActive ? 'Sim, desativar!' : 'Sim, ativar!';
    const icon = maintenanceActive ? 'question' : 'warning';
    const maintenanceBtn = document.getElementById('btnMaintenance');

    const doToggle = async (reason, minutes) => {
        setButtonLoading(maintenanceBtn, true);
        try {
            const data = await window.CsrfManager.fetchJson(`${BASE_URL}api/sysadmin/maintenance`, {
                method: 'POST',
                body: JSON.stringify({
                    action,
                    reason: reason || '',
                    estimated_minutes: minutes || null
                })
            });

            maintenanceActive = data.active;
            updateMaintenanceButton();
            if (window.LKFeedback) {
                LKFeedback.success(data.message, { toast: true });
            } else {
                alert(data.message);
            }
        } catch (e) {
            if (window.LKFeedback) {
                LKFeedback.error(getErrorMessage(e, 'Erro ao atualizar modo de manutenção.'));
            } else {
                alert('Erro: ' + e.message);
            }
        } finally {
            setButtonLoading(maintenanceBtn, false);
        }
    };

    if (window.LKFeedback) {
        if (action === 'activate') {
            const { value: formValues } = await Swal.fire({
                title: title,
                icon: icon,
                html: '<p class="mb-3" style="color:#64748b;font-size:0.9rem;">' + text + '</p>' +
                    '<input id="swal-reason" class="swal2-input" placeholder="Motivo (opcional)" style="font-size:0.9rem;">' +
                    '<input id="swal-minutes" type="number" class="swal2-input" placeholder="Tempo estimado em minutos" min="1" style="font-size:0.9rem;">',
                showCancelButton: true,
                confirmButtonColor: '#e74c3c',
                cancelButtonColor: '#95a5a6',
                confirmButtonText: confirmText,
                cancelButtonText: 'Cancelar',
                preConfirm: () => ({
                    reason: document.getElementById('swal-reason').value,
                    minutes: document.getElementById('swal-minutes').value
                })
            });
            if (formValues) {
                await doToggle(formValues.reason, formValues.minutes ? parseInt(formValues.minutes) : null);
            }
        } else {
            const result = await LKFeedback.confirm(text, {
                title,
                icon,
                confirmButtonText: confirmText,
                cancelButtonText: 'Cancelar'
            });
            if (result.isConfirmed) {
                await doToggle();
            }
        }
    } else {
        if (confirm(text)) {
            await doToggle();
        }
    }
}

// ============================================================================
// USER MANAGEMENT
// ============================================================================

const userTableSection = document.getElementById('userTableSection');
const userFiltersForm = document.getElementById('userFilters');
let currentPage = 1;

function fetchUsers(page = 1) {
    const formData = new FormData(userFiltersForm);
    const params = new URLSearchParams(formData);
    params.set('page', page);
    apiGet(`${BASE_URL}api/sysadmin/users?${params.toString()}`)
        .then(data => {
            if (!data.success) {
                userTableSection.innerHTML = `<div class='error-msg'>Erro ao buscar usuários</div>`;
                return;
            }
            renderUserTable(data.data.users, data.data.total, data.data.page, data.data.perPage);
        })
        .catch(err => {
            console.error('Erro ao buscar usuários:', err);
            userTableSection.innerHTML = `<div class='error-msg'>Erro ao carregar usuários</div>`;
        });
}

function renderUserTable(users, total, page, perPage) {
    let html = `<div class='modern-table-card'><div class='table-responsive'><table class='modern-table'>`;
    html += `<thead><tr><th>ID</th><th>Nome</th><th>Email</th><th>Plano</th><th>Status</th><th>Data de Cadastro</th><th class='text-center'>Ações</th></tr></thead><tbody>`;
    if (users.length === 0) {
        html += `<tr><td colspan='7' class='text-center' style='padding:2rem;'><i data-lucide='inbox' style='font-size:3rem;color:var(--color-text-muted);margin-bottom:1rem;'></i><p style='color:var(--color-text-muted);'>Nenhum usuário encontrado</p></td></tr>`;
    } else {
        users.forEach(u => {
            const planBadge = u.is_pro
                ? `<span class='badge-status pro' style='background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;font-weight:600;'><i data-lucide='crown'></i> Pro</span>`
                : `<span class='badge-status free' style='background:#e5e7eb;color:#6b7280;font-weight:500;'><i data-lucide='user'></i> Free</span>`;
            html += `<tr>
                <td><span class='user-id'>#${u.id}</span></td>
                <td><div class='user-info'><div class='user-avatar'>${u.avatar ? `<img src="${escapeHtml(u.avatar)}" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">` : escapeHtml((u.nome || 'U')[0].toUpperCase())}</div><span class='user-name'>${escapeHtml(u.nome || '-')}</span></div></td>
                <td><span class='user-email'>${escapeHtml(u.email || '-')}</span></td>
                <td>${planBadge}</td>
                <td>${u.is_admin == 1 ? `<span class='badge-status admin'><i data-lucide='shield'></i>Admin</span>` : `<span class='badge-status user'><i data-lucide='user'></i>Usuário</span>`}</td>
                <td><span class='user-date'>${u.created_at ? formatDate(u.created_at) : '-'}</span></td>
                <td class='text-center'><div class='action-buttons'>
                    <button class='btn-action view' title='Ver detalhes' data-action='viewUser' data-user-id='${u.id}'><i data-lucide='eye'></i></button>
                    <button class='btn-action edit' title='Editar usuário' data-action='editUser' data-user-id='${u.id}'><i data-lucide='pencil'></i></button>
                    <button class='btn-action delete' title='Excluir usuário' data-action='deleteUser' data-user-id='${u.id}'><i data-lucide='trash-2'></i></button>
                </div></td>
            </tr>`;
        });
    }
    html += `</tbody></table></div>`;
    html += renderPagination(total, page, perPage);
    html += `</div>`;
    userTableSection.innerHTML = html;
    if (window.lucide) lucide.createIcons();
}

function renderPagination(total, page, perPage) {
    const totalPages = Math.ceil(total / perPage);
    const startItem = ((page - 1) * perPage) + 1;
    const endItem = Math.min(page * perPage, total);

    let html = `<div class='pagination-wrapper'>`;
    html += `<div class='pagination-info'><span>Mostrando <strong>${startItem}</strong> - <strong>${endItem}</strong> de <strong>${total}</strong> usuários</span></div>`;
    html += `<div class='pagination-controls'>`;
    html += `<button class='pagination-btn' ${page <= 1 ? 'disabled' : ''} data-action='goToPage' data-page='1' title='Primeira página'><i data-lucide='chevrons-left'></i></button>`;
    html += `<button class='pagination-btn' ${page <= 1 ? 'disabled' : ''} data-action='goToPage' data-page='${page - 1}' title='Anterior'><i data-lucide='chevron-left'></i></button>`;

    for (let i = Math.max(1, page - 2); i <= Math.min(totalPages, page + 2); i++) {
        html += `<button class='pagination-btn ${i === page ? 'active' : ''}' data-action='goToPage' data-page='${i}'>${i}</button>`;
    }

    html += `<button class='pagination-btn' ${page >= totalPages ? 'disabled' : ''} data-action='goToPage' data-page='${page + 1}' title='Próxima'><i data-lucide='chevron-right'></i></button>`;
    html += `<button class='pagination-btn' ${page >= totalPages ? 'disabled' : ''} data-action='goToPage' data-page='${totalPages}' title='Última página'><i data-lucide='chevrons-right'></i></button>`;
    html += `</div></div>`;
    return html;
}

function goToPage(p) {
    currentPage = p;
    fetchUsers(currentPage);
}

function viewUser(userId) {
    apiGet(`${BASE_URL}api/sysadmin/users/${userId}`)
        .then(response => {
            if (!response.success) {
                LKFeedback.error(response.message || 'Erro ao buscar usuário');
                return;
            }

            const user = response.data;
            const createdAt = user.created_at ? new Date(user.created_at).toLocaleDateString('pt-BR', {
                day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'
            }) : 'N/A';

            const dataNascimento = user.data_nascimento ? new Date(user.data_nascimento).toLocaleDateString('pt-BR') : 'Não informado';

            let enderecoHtml = '';
            if (user.endereco) {
                const end = user.endereco;
                enderecoHtml = `
                    <div class="detail-section">
                        <h4><i data-lucide="map-pin" class="icon-info"></i> Endereço</h4>
                        ${end.rua ? `<div class="detail-row"><span class="detail-label">Logradouro</span><span class="detail-value">${end.rua}${end.numero ? ', ' + end.numero : ''}</span></div>` : ''}
                        ${end.complemento ? `<div class="detail-row"><span class="detail-label">Complemento</span><span class="detail-value">${end.complemento}</span></div>` : ''}
                        ${end.bairro ? `<div class="detail-row"><span class="detail-label">Bairro</span><span class="detail-value">${end.bairro}</span></div>` : ''}
                        ${end.cidade || end.estado ? `<div class="detail-row"><span class="detail-label">Cidade/UF</span><span class="detail-value">${end.cidade || ''}${end.cidade && end.estado ? ' - ' : ''}${end.estado || ''}</span></div>` : ''}
                        ${end.cep ? `<div class="detail-row"><span class="detail-label">CEP</span><span class="detail-value">${end.cep}</span></div>` : ''}
                    </div>
                `;
            } else {
                enderecoHtml = `
                    <div class="detail-section">
                        <h4><i data-lucide="map-pin" class="icon-muted"></i> Endereço</h4>
                        <p class="text-muted text-sm"><i data-lucide="info"></i> Endereço não cadastrado</p>
                    </div>
                `;
            }

            let subscriptionHtml = '';
            if (user.subscription) {
                const expiresAt = user.subscription.renova_em ? new Date(user.subscription.renova_em).toLocaleDateString('pt-BR', {
                    day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'
                }) : 'N/A';

                const statusClass = user.subscription.status === 'active' ? 'success' : 'warning';
                const statusText = user.subscription.status === 'active' ? 'Ativa' : (user.subscription.status === 'canceled' ? 'Cancelada' : user.subscription.status);
                const planoNome = user.subscription.plano_nome || (user.subscription.plano_id == 1 ? 'Free' : (user.subscription.plano_id == 2 ? 'Pro' : 'Plano ' + user.subscription.plano_id));
                const planoBadgeClass = user.subscription.plano_id == 2 ? 'badge-pro' : 'badge-free';

                subscriptionHtml = `
                    <div class="detail-section">
                        <h4><i data-lucide="crown" class="icon-warning"></i> Assinatura</h4>
                        <div class="detail-row"><span class="detail-label">Status</span><span class="detail-value badge-${statusClass}">${statusText}</span></div>
                        <div class="detail-row"><span class="detail-label">Plano</span><span class="detail-value ${planoBadgeClass}">${planoNome}</span></div>
                        <div class="detail-row"><span class="detail-label">Gateway</span><span class="detail-value">${user.subscription.gateway || 'interno'}</span></div>
                        <div class="detail-row"><span class="detail-label">Expira em</span><span class="detail-value">${expiresAt}</span></div>
                    </div>
                `;
            } else {
                subscriptionHtml = `
                    <div class="detail-section">
                        <h4><i data-lucide="crown" class="icon-muted"></i> Assinatura</h4>
                        <p class="text-muted text-sm"><i data-lucide="info"></i> Usuário não possui assinatura PRO</p>
                    </div>
                `;
            }

            Swal.fire({
                title: `<i data-lucide="circle-user"></i> Detalhes do Usuário`,
                html: `
                    <div class="user-details-modal">
                        <div class="user-header-info">
                            <div class="user-avatar-large">${user.avatar ? `<img src="${user.avatar}" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">` : (user.nome || 'U')[0].toUpperCase()}</div>
                            <div class="user-main-info">
                                <h3>${user.nome || 'Sem nome'}</h3>
                                <p>${user.email || 'Sem email'}</p>
                                ${user.is_admin == 1 ? '<span class="badge-admin"><i data-lucide="shield"></i> Administrador</span>' : '<span class="badge-user"><i data-lucide="user"></i> Usuário</span>'}
                            </div>
                        </div>
                        <div class="detail-section">
                            <h4><i data-lucide="info"></i> Informações Gerais</h4>
                            <div class="detail-row"><span class="detail-label">ID</span><span class="detail-value">#${user.id}</span></div>
                            ${user.support_code ? `<div class="detail-row"><span class="detail-label">Código de Suporte</span><span class="detail-value detail-value-support-code">${user.support_code}</span></div>` : ''}
                            <div class="detail-row"><span class="detail-label">Nome</span><span class="detail-value">${user.nome || 'N/A'}</span></div>
                            <div class="detail-row"><span class="detail-label">Email</span><span class="detail-value detail-value-email">${user.email || 'N/A'}</span></div>
                            <div class="detail-row"><span class="detail-label">Data de Nascimento</span><span class="detail-value">${dataNascimento}</span></div>
                            <div class="detail-row"><span class="detail-label">Cadastrado em</span><span class="detail-value">${createdAt}</span></div>
                        </div>
                        ${enderecoHtml}
                        ${subscriptionHtml}
                    </div>
                `,
                customClass: { popup: 'sysadmin-swal user-details-popup' },
                showCloseButton: true,
                showConfirmButton: false,
                width: '600px'
            });
        })
        .catch(err => {
            console.error('Erro ao buscar usuário:', err);
            LKFeedback.error(getErrorMessage(err, 'Erro ao buscar dados do usuário'));
        });
}

function editUser(userId) {
    LKFeedback.hideLoading();
    LKFeedback.loading('Buscando dados do usuário');

    apiGet(`${BASE_URL}api/sysadmin/users/${userId}`)
        .then(response => {
            LKFeedback.hideLoading();
            if (!response.success) {
                LKFeedback.error(response.message || 'Erro ao buscar usuário');
                return;
            }

            const user = response.data;
            const formHtml = `
                <div class="swal-form-layout">
                    <div class="swal-form-group">
                        <label><i data-lucide="user"></i> Nome</label>
                        <input type="text" id="editNome" class="swal2-input" value="${user.nome || ''}">
                    </div>
                    <div class="swal-form-group">
                        <label><i data-lucide="mail"></i> Email</label>
                        <input type="email" id="editEmail" class="swal2-input" value="${user.email || ''}">
                    </div>
                    <div class="swal-form-group">
                        <label><i data-lucide="lock"></i> Nova Senha (deixe em branco para manter)</label>
                        <input type="password" id="editSenha" class="swal2-input" placeholder="••••••">
                    </div>
                    <div class="swal-form-group">
                        <label><i data-lucide="shield"></i> Status de Admin</label>
                        <select id="editIsAdmin" class="swal2-select">
                            <option value="0" ${user.is_admin == 0 ? 'selected' : ''}>Usuário Normal</option>
                            <option value="1" ${user.is_admin == 1 ? 'selected' : ''}>Administrador</option>
                        </select>
                    </div>
                    ${user.subscription ? `<div class="subscription-info-box">
                        <h4><i data-lucide="crown" class="icon-warning"></i> Assinatura Atual</h4>
                        <p><strong>Status:</strong> ${user.subscription.status}<br>
                        <strong>Gateway:</strong> ${user.subscription.gateway || 'N/A'}<br>
                        <strong>Expira em:</strong> ${user.subscription.renova_em ? new Date(user.subscription.renova_em).toLocaleDateString('pt-BR') : 'N/A'}</p>
                    </div>` : ''}
                </div>
            `;

            Swal.fire({
                title: '<i data-lucide="user-pen"></i> Editar Usuário',
                html: formHtml,
                customClass: { popup: 'sysadmin-swal' },
                showCancelButton: true,
                confirmButtonText: '<i data-lucide="save"></i> Salvar',
                cancelButtonText: '<i data-lucide="x"></i> Cancelar',
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#94a3b8',
                width: '500px',
                focusConfirm: false,
                didOpen: () => { document.getElementById('editNome')?.focus(); },
                preConfirm: () => {
                    const nome = document.getElementById('editNome')?.value?.trim() || '';
                    const email = document.getElementById('editEmail')?.value?.trim() || '';
                    const senha = document.getElementById('editSenha')?.value || '';
                    const is_admin = document.getElementById('editIsAdmin')?.value || '0';

                    if (!nome) { Swal.showValidationMessage('Nome é obrigatório'); return false; }
                    if (!email) { Swal.showValidationMessage('Email é obrigatório'); return false; }
                    if (senha && senha.length < 6) { Swal.showValidationMessage('Senha deve ter pelo menos 6 caracteres'); return false; }

                    return { nome, email, senha, is_admin: parseInt(is_admin) };
                }
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    const payload = { nome: result.value.nome, email: result.value.email, is_admin: result.value.is_admin };
                    if (result.value.senha) payload.senha = result.value.senha;

                    LKFeedback.loading('Salvando...');

                    apiPut(`${BASE_URL}api/sysadmin/users/${userId}`, payload)
                        .then(saveResponse => {
                            if (saveResponse.success) {
                                LKFeedback.success(saveResponse.message || 'Usuário atualizado com sucesso', { toast: true });
                                fetchUsers(currentPage);
                            } else {
                                LKFeedback.error(saveResponse.message || 'Erro ao atualizar usuário');
                            }
                        })
                        .catch(err => { console.error('Erro ao salvar:', err); LKFeedback.error(getErrorMessage(err, 'Erro ao salvar alterações')); });
                }
            });
        })
        .catch(err => { console.error('Erro ao buscar usuário:', err); LKFeedback.error(getErrorMessage(err, 'Erro ao buscar dados do usuário')); });
}

function deleteUser(userId) {
    if (!window.LKFeedback) return;
    LKFeedback.confirm('Esta acao nao podera ser desfeita!', {
        title: 'Excluir Usuario?',
        icon: 'warning',
        isDanger: true,
        confirmButtonText: 'Sim, excluir!',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            apiDelete(`${BASE_URL}api/sysadmin/users/${userId}`)
                .then(response => {
                    if (response.success) {
                        LKFeedback.success(response.message || 'Usuário removido com sucesso.', { toast: true });
                        fetchUsers(currentPage);
                    } else {
                        LKFeedback.error(response.message || 'Erro ao excluir usuário');
                    }
                })
                .catch(err => { console.error('Erro ao excluir:', err); LKFeedback.error(getErrorMessage(err, 'Erro ao excluir usuário')); });
        }
    });
}

// ============================================================================
// GRANT / REVOKE PREMIUM ACCESS (Pro / Ultra)
// ============================================================================

function openGrantAccessModal() {
    Swal.fire({
        title: '<i data-lucide="crown"></i> Liberar Acesso Premium',
        html: `
            <div class="swal-form-layout">
                <div class="swal-form-group">
                    <label><i data-lucide="user"></i> Email ou ID do Usuário</label>
                    <input type="text" id="grantUserId" class="swal2-input" placeholder="Digite o email ou ID">
                </div>
                <div class="swal-form-group">
                    <label><i data-lucide="gem"></i> Plano</label>
                    <select id="grantPlanType" class="swal2-select">
                        <option value="pro">Pro (R$ 14,90/mês)</option>
                        <option value="ultra">Ultra (R$ 39,90/mês)</option>
                    </select>
                </div>
                <div class="swal-form-group">
                    <label><i data-lucide="calendar-days"></i> Período</label>
                    <select id="grantPeriod" class="swal2-select">
                        <option value="7">1 Semana (7 dias)</option>
                        <option value="14">2 Semanas (14 dias)</option>
                        <option value="21">3 Semanas (21 dias)</option>
                        <option value="30" selected>1 Mês (30 dias)</option>
                        <option value="60">2 Meses (60 dias)</option>
                        <option value="90">3 Meses (90 dias)</option>
                        <option value="180">6 Meses (180 dias)</option>
                        <option value="365">1 Ano (365 dias)</option>
                        <option value="custom">Personalizado</option>
                    </select>
                </div>
                <div id="customDaysDiv" class="swal-form-group" style="display: none;">
                    <label><i data-lucide="hash"></i> Dias Personalizados</label>
                    <input type="number" id="customDays" class="swal2-input" placeholder="Digite o número de dias" min="1">
                </div>
            </div>
        `,
        customClass: { popup: 'sysadmin-swal' },
        showCancelButton: true,
        confirmButtonText: '<i data-lucide="check"></i> Liberar Acesso',
        cancelButtonText: '<i data-lucide="x"></i> Cancelar',
        confirmButtonColor: '#f97316', cancelButtonColor: '#94a3b8', width: '500px',
        didOpen: () => {
            document.getElementById('grantPeriod').addEventListener('change', function () {
                document.getElementById('customDaysDiv').style.display = this.value === 'custom' ? 'block' : 'none';
            });
        },
        preConfirm: () => {
            const userId = document.getElementById('grantUserId').value.trim();
            const period = document.getElementById('grantPeriod').value;
            const customDays = document.getElementById('customDays').value;

            const planType = document.getElementById('grantPlanType').value;

            if (!userId) { Swal.showValidationMessage('Por favor, informe o email ou ID do usuário'); return false; }
            let days = period;
            if (period === 'custom') {
                if (!customDays || customDays < 1) { Swal.showValidationMessage('Por favor, informe um número válido de dias'); return false; }
                days = customDays;
            }
            return { userId, days, planType };
        }
    }).then((result) => {
        if (result.isConfirmed) grantAccess(result.value.userId, result.value.days, result.value.planType);
    });
}

async function grantAccess(userId, days, planType) {
    try {
        const data = await apiPost(`${BASE_URL}api/sysadmin/grant-access`, { userId, days, planType });

        if (data.success) {
            const planName = data.data.planName || planType.toUpperCase();
            Swal.fire({
                icon: 'success', title: 'Acesso Liberado!',
                html: `<p><strong>${data.data.userName}</strong> agora tem acesso <strong>${planName}</strong> por <strong>${days} dias</strong>.</p><p class="text-muted text-sm">Válido até: <strong>${data.data.expiresAt}</strong></p>`,
                confirmButtonColor: '#f97316'
            }).then(() => location.reload());
        } else {
            LKFeedback.error(data.message || 'Não foi possível liberar o acesso');
        }
    } catch (error) {
        console.error('Erro:', error);
        LKFeedback.error(getErrorMessage(error, 'Ocorreu um erro ao processar a solicitação'));
    }
}

function openRevokeAccessModal() {
    Swal.fire({
        title: '<i data-lucide="ban"></i> Remover Acesso Premium',
        html: `
            <div class="swal-form-layout">
                <div class="swal-form-group">
                    <label><i data-lucide="user"></i> Email ou ID do Usuário</label>
                    <input type="text" id="revokeUserId" class="swal2-input" placeholder="Digite o email ou ID">
                </div>
                <div class="swal-alert-box danger">
                    <p><i data-lucide="triangle-alert"></i> <strong>Atenção:</strong> Esta ação irá cancelar imediatamente o acesso premium do usuário.</p>
                </div>
            </div>
        `,
        customClass: { popup: 'sysadmin-swal' },
        showCancelButton: true,
        confirmButtonText: '<i data-lucide="ban"></i> Remover Acesso',
        cancelButtonText: '<i data-lucide="x"></i> Cancelar',
        confirmButtonColor: '#ef4444', cancelButtonColor: '#94a3b8', width: '500px',
        preConfirm: () => {
            const userId = document.getElementById('revokeUserId').value.trim();
            if (!userId) { Swal.showValidationMessage('Por favor, informe o email ou ID do usuário'); return false; }
            return { userId };
        }
    }).then((result) => {
        if (result.isConfirmed) revokeProAccess(result.value.userId);
    });
}

async function revokeProAccess(userId) {
    try {
        const data = await apiPost(`${BASE_URL}api/sysadmin/revoke-access`, { userId });

        if (data.success) {
            Swal.fire({
                icon: 'success', title: 'Acesso Removido!',
                html: `<p>O acesso premium de <strong>${data.data.userName}</strong> foi removido com sucesso.</p><p class="text-muted text-sm">${data.data.subscriptionsCanceled} assinatura(s) cancelada(s).</p>`,
                confirmButtonColor: '#ef4444'
            }).then(() => location.reload());
        } else {
            LKFeedback.error(data.message || 'Não foi possível remover o acesso');
        }
    } catch (error) {
        console.error('Erro:', error);
        LKFeedback.error(getErrorMessage(error, 'Ocorreu um erro ao processar a solicitação'));
    }
}

// ============================================================================
// ERROR LOGS - REAL-TIME MONITORING
// ============================================================================

let errorLogsCurrentPage = 1;
let errorLogsAutoRefreshTimer = null;
const ERROR_LOGS_REFRESH_INTERVAL = 15000;
let errorLogFiltersLoaded = false;

async function loadErrorLogsSummary() {
    try {
        const data = await apiGet(`${BASE_URL}api/sysadmin/error-logs/summary?hours=24`);
        if (!data.success) return;

        const summary = data.data;

        document.getElementById('stat-error-total').textContent = (summary.total || 0).toLocaleString('pt-BR');
        const unresolvedEl = document.getElementById('stat-error-unresolved');
        const badgeEl = document.getElementById('stat-error-badge');
        if (summary.unresolved > 0) {
            unresolvedEl.textContent = `${summary.unresolved} não resolvido${summary.unresolved > 1 ? 's' : ''}`;
            badgeEl.className = 'stat-badge warning';
        } else {
            unresolvedEl.textContent = 'Tudo limpo!';
            badgeEl.className = 'stat-badge success';
        }

        const byLevel = summary.by_level || {};
        document.getElementById('levelCritical').textContent = byLevel['CRITICAL'] || byLevel['critical'] || 0;
        document.getElementById('levelError').textContent = byLevel['ERROR'] || byLevel['error'] || 0;
        document.getElementById('levelWarning').textContent = byLevel['WARNING'] || byLevel['warning'] || 0;
        document.getElementById('levelInfo').textContent = byLevel['INFO'] || byLevel['info'] || 0;
        document.getElementById('levelUnresolved').textContent = summary.unresolved || 0;

        if (!errorLogFiltersLoaded && data.data.filters) {
            populateErrorLogFilters(data.data.filters);
            errorLogFiltersLoaded = true;
        }
    } catch (e) {
        console.error('Erro ao carregar resumo de logs:', e);
    }
}

function populateErrorLogFilters(filters) {
    const levelSelect = document.getElementById('errorLogLevel');
    const categorySelect = document.getElementById('errorLogCategory');

    if (filters.levels && levelSelect) {
        filters.levels.forEach(l => {
            const opt = document.createElement('option');
            opt.value = l.value;
            opt.textContent = l.label;
            levelSelect.appendChild(opt);
        });
    }
    if (filters.categories && categorySelect) {
        filters.categories.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c.value;
            opt.textContent = c.label;
            categorySelect.appendChild(opt);
        });
    }
}

async function loadErrorLogs(page) {
    if (page !== undefined) errorLogsCurrentPage = page;

    const refreshIcon = document.getElementById('errorLogsRefreshIcon');
    if (refreshIcon) refreshIcon.classList.add('icon-spin');

    try {
        const form = document.getElementById('errorLogsFilters');
        const params = new URLSearchParams(new FormData(form));
        params.set('page', errorLogsCurrentPage);

        const [logsData] = await Promise.all([
            apiGet(`${BASE_URL}api/sysadmin/error-logs?${params.toString()}`),
            loadErrorLogsSummary()
        ]);
        if (refreshIcon) refreshIcon.classList.remove('icon-spin');

        if (!logsData.success) {
            document.getElementById('errorLogsTableWrapper').innerHTML = `<div class='error-logs-empty'><i data-lucide='alert-circle'></i><p>Erro ao carregar logs</p></div>`;
            return;
        }

        renderErrorLogsTable(logsData.data.data, logsData.data.total, logsData.data.page, logsData.data.per_page);
    } catch (e) {
        if (refreshIcon) refreshIcon.classList.remove('icon-spin');
        console.error('Erro ao carregar logs:', e);
        document.getElementById('errorLogsTableWrapper').innerHTML = `<div class='error-logs-empty'><i data-lucide='wifi-off'></i><p>Erro de conexão</p></div>`;
    }
}

function renderErrorLogsTable(logs, total, page, perPage) {
    const wrapper = document.getElementById('errorLogsTableWrapper');
    if (!logs || logs.length === 0) {
        wrapper.innerHTML = `<div class='error-logs-empty'><i data-lucide='check-circle-2' class='icon-success'></i><p>Nenhum log encontrado</p><span>Sistema operando normalmente</span></div>`;
        if (window.lucide) lucide.createIcons();
        return;
    }

    let html = `<div class='error-logs-table-card'><div class='table-responsive'><table class='error-logs-table'>`;
    html += `<thead><tr><th style="width:40px"></th><th>Nível</th><th>Categoria</th><th>Mensagem</th><th>Arquivo</th><th>Usuário</th><th>Data</th><th class='text-center'>Ações</th></tr></thead><tbody>`;

    logs.forEach(log => {
        const levelClass = (log.level || '').toLowerCase();
        const levelLabel = (log.level || 'N/A').toUpperCase();
        const isResolved = !!log.resolved_at;
        const timeAgo = getTimeAgo(log.created_at);
        const shortMsg = truncate(log.message || log.exception_message || '(sem mensagem)', 60);
        const fileName = log.file ? log.file.split('/').pop().split('\\\\').pop() : '–';
        const fileLine = log.line ? `${fileName}:${log.line}` : fileName;

        html += `<tr class='error-log-row ${isResolved ? "resolved" : ""}' data-log-id='${log.id}'>
            <td><button class='btn-expand-log' data-action='toggleLogDetail' data-log-id='${log.id}' title='Ver detalhes'><i data-lucide='chevron-right'></i></button></td>
            <td><span class='log-level-badge ${levelClass}'>${levelLabel}</span></td>
            <td><span class='log-category-badge'>${log.category || '–'}</span></td>
            <td class='log-message-cell' title='${escapeHtml(log.message || '')}'>${escapeHtml(shortMsg)}</td>
            <td class='log-file-cell'><code>${escapeHtml(fileLine)}</code></td>
            <td>${log.user_id ? '<span class="log-user-id">#' + log.user_id + '</span>' : '<span class="log-no-user">–</span>'}</td>
            <td><span class='log-time' title='${log.created_at}'>${timeAgo}</span></td>
            <td class='text-center'>
                ${!isResolved ? `<button class='btn-action resolve' title='Resolver' data-action='resolveErrorLog' data-log-id='${log.id}'><i data-lucide='check'></i></button>` : `<span class='log-resolved-badge'><i data-lucide='check-circle-2'></i></span>`}
            </td>
        </tr>`;

        html += `<tr class='error-log-detail' id='logDetail${log.id}' style='display:none'>
            <td colspan='8'>
                <div class='log-detail-content'>
                    <div class='log-detail-grid'>
                        <div class='log-detail-item'><strong>Mensagem Completa</strong><pre>${escapeHtml(log.message || '–')}</pre></div>
                        ${log.exception_class ? `<div class='log-detail-item'><strong>Exceção</strong><code>${escapeHtml(log.exception_class)}</code>${log.exception_message ? `<pre>${escapeHtml(log.exception_message)}</pre>` : ''}</div>` : ''}
                        <div class='log-detail-item'><strong>Arquivo</strong><code>${escapeHtml(log.file || '–')}${log.line ? ':' + log.line : ''}</code></div>
                        ${log.url ? `<div class='log-detail-item'><strong>URL</strong><code>${escapeHtml(log.method || '')} ${escapeHtml(log.url)}</code></div>` : ''}
                        ${log.ip ? `<div class='log-detail-item'><strong>IP</strong><code>${escapeHtml(log.ip)}</code></div>` : ''}
                        ${log.user_agent ? `<div class='log-detail-item'><strong>User Agent</strong><small>${escapeHtml(log.user_agent)}</small></div>` : ''}
                        ${log.context && Object.keys(log.context).length > 0 ? `<div class='log-detail-item full-width'><strong>Contexto</strong><pre class='log-context-json'>${escapeHtml(JSON.stringify(log.context, null, 2))}</pre></div>` : ''}
                        ${log.stack_trace ? `<div class='log-detail-item full-width'><strong>Stack Trace</strong><pre class='log-stack-trace'>${escapeHtml(log.stack_trace)}</pre></div>` : ''}
                    </div>
                    ${log.resolved_at ? `<div class='log-resolved-info'><i data-lucide='check-circle-2'></i> Resolvido em ${formatDate(log.resolved_at)}${log.resolved_by ? ' por #' + log.resolved_by : ''}</div>` : ''}
                </div>
            </td>
        </tr>`;
    });

    html += `</tbody></table></div>`;
    html += renderErrorLogsPagination(total, page, perPage);
    html += `</div>`;
    wrapper.innerHTML = html;
    if (window.lucide) lucide.createIcons();
}

function renderErrorLogsPagination(total, page, perPage) {
    const totalPages = Math.ceil(total / perPage);
    if (totalPages <= 1) return '';

    const startItem = ((page - 1) * perPage) + 1;
    const endItem = Math.min(page * perPage, total);

    let html = `<div class='pagination-wrapper'>`;
    html += `<div class='pagination-info'><span>Mostrando <strong>${startItem}</strong> - <strong>${endItem}</strong> de <strong>${total}</strong> logs</span></div>`;
    html += `<div class='pagination-controls'>`;
    html += `<button class='pagination-btn' ${page <= 1 ? 'disabled' : ''} data-action='errorLogsGoToPage' data-page='1' title='Primeira'><i data-lucide='chevrons-left'></i></button>`;
    html += `<button class='pagination-btn' ${page <= 1 ? 'disabled' : ''} data-action='errorLogsGoToPage' data-page='${page - 1}' title='Anterior'><i data-lucide='chevron-left'></i></button>`;

    for (let i = Math.max(1, page - 2); i <= Math.min(totalPages, page + 2); i++) {
        html += `<button class='pagination-btn ${i === page ? "active" : ""}' data-action='errorLogsGoToPage' data-page='${i}'>${i}</button>`;
    }

    html += `<button class='pagination-btn' ${page >= totalPages ? 'disabled' : ''} data-action='errorLogsGoToPage' data-page='${page + 1}' title='Próxima'><i data-lucide='chevron-right'></i></button>`;
    html += `<button class='pagination-btn' ${page >= totalPages ? 'disabled' : ''} data-action='errorLogsGoToPage' data-page='${totalPages}' title='Última'><i data-lucide='chevrons-right'></i></button>`;
    html += `</div></div>`;
    return html;
}

function toggleLogDetail(logId) {
    const detail = document.getElementById('logDetail' + logId);
    const row = document.querySelector(`tr[data-log-id="${logId}"]`);
    if (!detail) return;

    const isVisible = detail.style.display !== 'none';
    detail.style.display = isVisible ? 'none' : 'table-row';
    if (row) {
        const btn = row.querySelector('.btn-expand-log i');
        if (btn) {
            btn.setAttribute('data-lucide', isVisible ? 'chevron-right' : 'chevron-down');
            if (window.lucide) lucide.createIcons({ nodes: [btn] });
        }
    }
    if (!isVisible && window.lucide) lucide.createIcons();
}

async function resolveErrorLog(logId) {
    try {
        const data = await window.CsrfManager.fetchJson(`${BASE_URL}api/sysadmin/error-logs/${logId}/resolve`, { method: 'PUT' });
        if (data.success) {
            LKFeedback.success('Log marcado como resolvido.', { toast: true });
            loadErrorLogs();
        } else {
            LKFeedback.error(data.message || 'Erro ao resolver log');
        }
    } catch (e) {
        console.error('Erro ao resolver log:', e);
        LKFeedback.error(getErrorMessage(e, 'Erro ao resolver log'));
    }
}

function confirmCleanupLogs() {
    if (!cleanupLogsModalEl || !window.bootstrap?.Modal) {
        LKFeedback.error('Modal de limpeza indisponível no momento.');
        return;
    }

    cleanupLogsDaysEl.value = '30';
    cleanupLogsIncludeUnresolvedEl.checked = false;
    updateCleanupLogsModalState();

    cleanupLogsModalInstance ??= new window.bootstrap.Modal(cleanupLogsModalEl);
    cleanupLogsModalInstance.show();
}

function updateCleanupLogsModalState() {
    if (!cleanupLogsDaysEl || !cleanupLogsHintEl || !cleanupLogsConfirmBtn) return;

    const days = Math.max(1, parseInt(cleanupLogsDaysEl.value || '30', 10));
    const includeUnresolved = !!cleanupLogsIncludeUnresolvedEl?.checked;
    const label = cleanupLogsConfirmBtn.querySelector('span');

    if (includeUnresolved) {
        cleanupLogsHintEl.innerHTML = `Serão removidos <strong>todos</strong> os logs criados há mais de <strong>${days} dias</strong>, inclusive não resolvidos. Use esta opção só quando quiser podar o histórico completo.`;
        cleanupLogsHintEl.classList.add('is-danger');
        if (label) label.textContent = 'Limpar todos os antigos';
        return;
    }

    cleanupLogsHintEl.innerHTML = `Serão removidos apenas logs <strong>resolvidos</strong> há mais de <strong>${days} dias</strong>. Logs ainda abertos serão preservados.`;
    cleanupLogsHintEl.classList.remove('is-danger');
    if (label) label.textContent = 'Limpar resolvidos antigos';
}

async function submitCleanupLogs() {
    if (!cleanupLogsDaysEl || !cleanupLogsConfirmBtn) return;

    const days = Math.max(1, parseInt(cleanupLogsDaysEl.value || '30', 10));
    const includeUnresolved = !!cleanupLogsIncludeUnresolvedEl?.checked;

    try {
        setButtonLoading(cleanupLogsConfirmBtn, true);

        const data = await window.CsrfManager.fetchJson(`${BASE_URL}api/sysadmin/error-logs/cleanup`, {
            method: 'DELETE',
            body: JSON.stringify({
                days,
                include_unresolved: includeUnresolved,
            })
        });

        if (!data.success) {
            LKFeedback.error(data.message || 'Erro na limpeza');
            return;
        }

        cleanupLogsModalInstance?.hide();
        LKFeedback.success(data.message || `${data.data?.count || 0} logs removidos.`, { toast: true });
        errorLogsCurrentPage = 1;
        loadErrorLogs(1);
    } catch (e) {
        console.error('Erro cleanup:', e);
        LKFeedback.error(getErrorMessage(e, 'Erro ao limpar logs'));
    } finally {
        setButtonLoading(cleanupLogsConfirmBtn, false);
    }
}

function startErrorLogsAutoRefresh() {
    stopErrorLogsAutoRefresh();
    errorLogsAutoRefreshTimer = setInterval(() => loadErrorLogs(), ERROR_LOGS_REFRESH_INTERVAL);
}

function stopErrorLogsAutoRefresh() {
    if (errorLogsAutoRefreshTimer) { clearInterval(errorLogsAutoRefreshTimer); errorLogsAutoRefreshTimer = null; }
}

// ============================================================================
// STATS & CHARTS
// ============================================================================

let usersByDayChart = null;
let userDistributionChart = null;
let subscriptionsByGatewayChart = null;

function loadStats() {
    const refreshBtn = document.querySelector('.btn-refresh-stats i');
    if (refreshBtn) refreshBtn.classList.add('icon-spin');

    apiGet(`${BASE_URL}api/sysadmin/stats`)
        .then(response => {
            if (refreshBtn) refreshBtn.classList.remove('icon-spin');
            if (!response.success) { showStatsError(response.message || 'Erro ao carregar estatísticas'); return; }
            const data = response.data;
            if (!data || !data.overview || !data.charts) { showStatsError('Dados de estatísticas inválidos'); return; }
            updateStatsOverview(data);
            renderCharts(data.charts);
        })
        .catch(err => { if (refreshBtn) refreshBtn.classList.remove('icon-spin'); console.error('Erro ao carregar estatísticas:', err); showStatsError(getErrorMessage(err, 'Erro ao conectar com o servidor')); });
}

function showStatsError(message) {
    ['statProUsers', 'statFreeUsers'].forEach(id => { const el = document.getElementById(id); if (el) el.textContent = 'Erro'; });
    ['statConversionRate', 'statGrowthRate', 'statNewToday', 'statNewWeek', 'statNewMonth'].forEach(id => { const el = document.getElementById(id); if (el) el.textContent = '-'; });
    console.error('Stats Error:', message);
}

function updateStatsOverview(data) {
    document.getElementById('statProUsers').textContent = data.overview.proUsers.toLocaleString('pt-BR');
    document.getElementById('statFreeUsers').textContent = data.overview.freeUsers.toLocaleString('pt-BR');
    document.getElementById('statConversionRate').textContent = data.overview.conversionRate + '%';

    const growthRate = data.newUsers.growthRate;
    const growthEl = document.getElementById('statGrowthRate');
    growthEl.textContent = (growthRate >= 0 ? '+' : '') + growthRate + '%';
    growthEl.classList.toggle('positive', growthRate >= 0);
    growthEl.classList.toggle('negative', growthRate < 0);

    document.getElementById('statNewToday').textContent = data.newUsers.today.toLocaleString('pt-BR');
    document.getElementById('statNewWeek').textContent = data.newUsers.thisWeek.toLocaleString('pt-BR');
    document.getElementById('statNewMonth').textContent = data.newUsers.thisMonth.toLocaleString('pt-BR');
}

function renderCharts(charts) {
    const chartColors = { primary: '#f97316', secondary: '#3b82f6', success: '#10b981', warning: '#f59e0b', danger: '#ef4444', purple: '#8b5cf6', pink: '#ec4899', gray: '#6b7280' };
    const isDarkMode = document.documentElement.getAttribute('data-theme') === 'dark';
    const textColor = isDarkMode ? '#e2e8f0' : '#1e293b';
    const gridColor = isDarkMode ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';
    const themeMode = isDarkMode ? 'dark' : 'light';

    // Line Chart - Users by Day
    const usersByDayEl = document.getElementById('usersByDayChart');
    if (usersByDayEl) {
        if (usersByDayChart) { usersByDayChart.destroy(); usersByDayChart = null; }
        const labels = Object.keys(charts.usersByDay).map(date => new Date(date).toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' }));
        const values = Object.values(charts.usersByDay);

        usersByDayChart = new ApexCharts(usersByDayEl, {
            chart: { type: 'area', height: '100%', toolbar: { show: false }, background: 'transparent', fontFamily: 'Inter, Arial, sans-serif' },
            series: [{ name: 'Novos Usuários', data: values }],
            xaxis: { categories: labels, labels: { style: { colors: textColor }, rotateAlways: false, rotate: -45 }, axisBorder: { show: false }, axisTicks: { show: false } },
            yaxis: { min: 0, forceNiceScale: true, labels: { style: { colors: textColor } } },
            colors: [chartColors.primary],
            stroke: { curve: 'smooth', width: 3 },
            fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05, stops: [0, 100] } },
            markers: { size: 4, colors: [chartColors.primary], strokeColors: '#fff', strokeWidth: 2, hover: { size: 6 } },
            grid: { borderColor: gridColor, strokeDashArray: 4 },
            tooltip: { theme: themeMode },
            legend: { show: false },
            dataLabels: { enabled: false },
            theme: { mode: themeMode },
        });
        usersByDayChart.render();
    }

    // Doughnut Chart - User Distribution
    const userDistEl = document.getElementById('userDistributionChart');
    if (userDistEl) {
        if (userDistributionChart) { userDistributionChart.destroy(); userDistributionChart = null; }
        userDistributionChart = new ApexCharts(userDistEl, {
            chart: { type: 'donut', height: '100%', background: 'transparent', fontFamily: 'Inter, Arial, sans-serif' },
            series: Object.values(charts.userDistribution),
            labels: Object.keys(charts.userDistribution),
            colors: [chartColors.primary, chartColors.gray],
            stroke: { width: 0 },
            plotOptions: { pie: { donut: { size: '60%' }, expandOnClick: true } },
            legend: { position: 'bottom', labels: { colors: textColor }, markers: { shape: 'circle' } },
            dataLabels: { enabled: false },
            tooltip: { theme: themeMode },
            theme: { mode: themeMode },
        });
        userDistributionChart.render();
    }

    // Doughnut Chart - Subscriptions by Gateway
    const gatewayEl = document.getElementById('subscriptionsByGatewayChart');
    if (gatewayEl) {
        if (subscriptionsByGatewayChart) { subscriptionsByGatewayChart.destroy(); subscriptionsByGatewayChart = null; }
        const gatewayLabels = Object.keys(charts.subscriptionsByGateway);
        const gatewayValues = Object.values(charts.subscriptionsByGateway);
        if (gatewayLabels.length === 0) { gatewayLabels.push('Nenhum'); gatewayValues.push(0); }
        const gatewayColors = gatewayLabels.map((_, i) => [chartColors.success, chartColors.secondary, chartColors.purple, chartColors.pink, chartColors.warning][i % 5]);

        subscriptionsByGatewayChart = new ApexCharts(gatewayEl, {
            chart: { type: 'donut', height: '100%', background: 'transparent', fontFamily: 'Inter, Arial, sans-serif' },
            series: gatewayValues,
            labels: gatewayLabels.map(l => l.charAt(0).toUpperCase() + l.slice(1)),
            colors: gatewayColors,
            stroke: { width: 0 },
            plotOptions: { pie: { donut: { size: '60%' }, expandOnClick: true } },
            legend: { position: 'bottom', labels: { colors: textColor }, markers: { shape: 'circle' } },
            dataLabels: { enabled: false },
            tooltip: { theme: themeMode },
            theme: { mode: themeMode },
        });
        subscriptionsByGatewayChart.render();
    }
}

// ============================================================================
// HELPERS
// ============================================================================

function formatDate(dt) {
    const d = new Date(dt);
    return d.toLocaleDateString('pt-BR') + ' ' + d.toLocaleTimeString('pt-BR').slice(0, 5);
}



function truncate(str, len) {
    if (!str) return '';
    return str.length > len ? str.substring(0, len) + '...' : str;
}

function getTimeAgo(dateStr) {
    if (!dateStr) return '–';
    const date = new Date(dateStr);
    const now = new Date();
    const diffMs = now - date;
    const diffMin = Math.floor(diffMs / 60000);
    const diffH = Math.floor(diffMin / 60);
    const diffD = Math.floor(diffH / 24);

    if (diffMin < 1) return 'Agora';
    if (diffMin < 60) return `${diffMin}min atrás`;
    if (diffH < 24) return `${diffH}h atrás`;
    if (diffD < 7) return `${diffD}d atrás`;
    return date.toLocaleDateString('pt-BR');
}

// ============================================================================
// FEEDBACK ADMIN
// ============================================================================

let feedbackCurrentPage = 1;
let feedbackStatsLoaded = false;

async function loadFeedbackStats() {
    try {
        const json = await apiGet(`${BASE_URL}api/sysadmin/feedback/stats`);
        const data = json.data ?? json;

        // NPS
        const nps = data.nps ?? {};
        const scoreEl = document.getElementById('npsScoreValue');
        if (scoreEl) {
            scoreEl.textContent = nps.total > 0 ? nps.score : '--';
            scoreEl.style.color = nps.score >= 50 ? '#10b981' : nps.score >= 0 ? '#f59e0b' : '#ef4444';
        }
        setText('npsPromoters', nps.promoters ?? 0);
        setText('npsPassives', nps.passives ?? 0);
        setText('npsDetractors', nps.detractors ?? 0);

        // Stats by tipo
        const byTipo = data.by_tipo ?? {};
        const tipoMap = {
            acao: { count: 'statFbAcao', avg: 'statFbAcaoAvg' },
            assistente_ia: { count: 'statFbIa', avg: 'statFbIaAvg' },
            nps: { count: 'statFbNps', avg: 'statFbNpsAvg' },
            sugestao: { count: 'statFbSugestao', avg: 'statFbSugestaoAvg' },
        };

        for (const [tipo, ids] of Object.entries(tipoMap)) {
            const stat = byTipo[tipo];
            setText(ids.count, stat?.total ?? 0);
            setText(ids.avg, stat?.avg_rating != null ? `Media: ${parseFloat(stat.avg_rating).toFixed(1)}` : '--');
        }

        if (window.lucide) lucide.createIcons();
    } catch (err) {
        console.error('Erro ao carregar stats de feedback:', err);
    }
}

function setText(id, value) {
    const el = document.getElementById(id);
    if (el) el.textContent = value;
}

async function loadFeedbackList(page = 1) {
    feedbackCurrentPage = page;
    const wrapper = document.getElementById('feedbackTableWrapper');
    if (!wrapper) return;

    wrapper.innerHTML = '<div class="feedback-empty"><i data-lucide="loader-2"></i><p>Carregando feedbacks...</p></div>';
    if (window.lucide) lucide.createIcons({ nodes: [wrapper] });

    try {
        const tipo = document.getElementById('feedbackFilterTipo')?.value || '';
        const perPage = document.getElementById('feedbackPerPage')?.value || '15';

        let url = `${BASE_URL}api/sysadmin/feedback?page=${page}&per_page=${perPage}`;
        if (tipo) url += `&tipo_feedback=${encodeURIComponent(tipo)}`;

        const json = await apiGet(url);
        const data = json.data ?? json;

        renderFeedbackTable(data);
    } catch (err) {
        wrapper.innerHTML = '<div class="feedback-empty"><p>Erro ao carregar feedbacks</p></div>';
        console.error('Erro ao carregar feedbacks:', err);
    }
}

function renderFeedbackTable(data) {
    const wrapper = document.getElementById('feedbackTableWrapper');
    if (!wrapper) return;

    const items = data.items ?? [];

    if (items.length === 0) {
        wrapper.innerHTML = '<div class="feedback-empty"><i data-lucide="message-square"></i><p>Nenhum feedback encontrado</p></div>';
        if (window.lucide) lucide.createIcons({ nodes: [wrapper] });
        return;
    }

    const tipoLabels = {
        acao: 'Micro Feedback',
        assistente_ia: 'Assistente IA',
        nps: 'NPS',
        sugestao: 'Sugestao',
    };

    const rows = items.map(f => {
        const tipoBadge = `<span class="feedback-tipo-badge tipo-${f.tipo_feedback}">${tipoLabels[f.tipo_feedback] || f.tipo_feedback}</span>`;

        let ratingHtml = '-';
        if (f.rating !== null && f.rating !== undefined) {
            if (f.tipo_feedback === 'nps') {
                const cls = f.rating >= 9 ? 'promoter' : f.rating >= 7 ? 'passive' : 'detractor';
                ratingHtml = `<span class="feedback-rating-nps ${cls}">${f.rating}</span>`;
            } else if (f.tipo_feedback === 'sugestao') {
                ratingHtml = `<span class="feedback-rating-stars">${'★'.repeat(f.rating)}${'☆'.repeat(5 - f.rating)}</span>`;
            } else if (f.tipo_feedback === 'acao') {
                ratingHtml = `<span class="feedback-rating-thumbs">${f.rating === 1 ? '👍' : '👎'}</span>`;
            } else if (f.tipo_feedback === 'assistente_ia') {
                ratingHtml = `<span class="feedback-rating-thumbs">${f.rating === 2 ? '👍' : f.rating === 1 ? '😐' : '👎'}</span>`;
            }
        }

        const comment = f.comentario
            ? `<span class="feedback-comment-cell" title="${escHtml(f.comentario)}">${escHtml(f.comentario)}</span>`
            : '<span style="color:var(--color-text-muted,#64748b);">-</span>';

        const date = f.created_at ? new Date(f.created_at).toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: '2-digit', hour: '2-digit', minute: '2-digit' }) : '-';

        return `<tr>
            <td>${tipoBadge}</td>
            <td>${escHtml(f.user_nome ?? '-')}</td>
            <td>${ratingHtml}</td>
            <td>${escHtml(f.contexto ?? '-')}</td>
            <td>${comment}</td>
            <td style="white-space:nowrap;font-size:0.75rem;">${date}</td>
        </tr>`;
    }).join('');

    wrapper.innerHTML = `
        <table class="feedback-table">
            <thead>
                <tr>
                    <th>Tipo</th>
                    <th>Usuario</th>
                    <th>Rating</th>
                    <th>Contexto</th>
                    <th>Comentario</th>
                    <th>Data</th>
                </tr>
            </thead>
            <tbody>${rows}</tbody>
        </table>
        ${renderFeedbackPagination(data.page ?? 1, data.totalPages ?? 1, data.total ?? 0)}
    `;

    if (window.lucide) lucide.createIcons({ nodes: [wrapper] });
}

function renderFeedbackPagination(page, totalPages, total) {
    if (totalPages <= 1) return `<div class="feedback-pagination"><span>${total} feedback(s)</span></div>`;

    let html = '<div class="feedback-pagination">';
    html += `<button ${page <= 1 ? 'disabled' : ''} data-action="feedbackGoToPage" data-page="${page - 1}"><i data-lucide="chevron-left"></i></button>`;

    const start = Math.max(1, page - 2);
    const end = Math.min(totalPages, page + 2);

    for (let i = start; i <= end; i++) {
        html += `<button class="${i === page ? 'active' : ''}" data-action="feedbackGoToPage" data-page="${i}">${i}</button>`;
    }

    html += `<button ${page >= totalPages ? 'disabled' : ''} data-action="feedbackGoToPage" data-page="${page + 1}"><i data-lucide="chevron-right"></i></button>`;
    html += `<span>${total} feedback(s)</span>`;
    html += '</div>';
    return html;
}

function exportFeedback() {
    const tipo = document.getElementById('feedbackFilterTipo')?.value || '';
    let url = `${BASE_URL}api/sysadmin/feedback/export`;
    if (tipo) url += `?tipo_feedback=${encodeURIComponent(tipo)}`;
    window.open(url, '_blank');
}



// ============================================================================
// EVENT DELEGATION (substitui onclick handlers)
// ============================================================================

document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-action]');
    if (!btn) return;

    const action = btn.dataset.action;
    const userId = btn.dataset.userId ? parseInt(btn.dataset.userId) : null;
    const logId = btn.dataset.logId ? parseInt(btn.dataset.logId) : null;
    const page = btn.dataset.page ? parseInt(btn.dataset.page) : null;

    switch (action) {
        case 'viewUser': viewUser(userId); break;
        case 'editUser': editUser(userId); break;
        case 'deleteUser': deleteUser(userId); break;
        case 'goToPage': goToPage(page); break;
        case 'errorLogsGoToPage': loadErrorLogs(page); break;
        case 'toggleLogDetail': toggleLogDetail(logId); break;
        case 'resolveErrorLog': resolveErrorLog(logId); break;
        case 'limparCache': limparCache(); break;
        case 'toggleMaintenance': toggleMaintenance(); break;
        case 'loadStats': loadStats(); break;
        case 'loadErrorLogs': loadErrorLogs(); break;
        case 'openGrantAccessModal': openGrantAccessModal(); break;
        case 'openRevokeAccessModal': openRevokeAccessModal(); break;
        case 'confirmCleanupLogs': confirmCleanupLogs(); break;
        case 'navigateTo': window.location.href = btn.dataset.href; break;
        case 'switchTab': switchTab(btn.dataset.tab); e.preventDefault(); break;
        case 'loadFeedbackStats': loadFeedbackStats(); loadFeedbackList(); break;
        case 'feedbackGoToPage': loadFeedbackList(page); break;
        case 'exportFeedback': exportFeedback(); break;
    }
});

// ============================================================================
// TAB NAVIGATION
// ============================================================================

const VALID_TABS = ['dashboard', 'controle', 'usuarios', 'logs', 'ia', 'feedback'];
const sysadminTabs = document.querySelectorAll('.sysadmin-tab');
const sysadminPanels = document.querySelectorAll('.sysadmin-tab-panel');
let chartsInitialized = false;

function switchTab(tabId) {
    if (!VALID_TABS.includes(tabId)) return;

    sysadminTabs.forEach(t => {
        const isActive = t.dataset.tab === tabId;
        t.classList.toggle('active', isActive);
        t.setAttribute('aria-selected', isActive ? 'true' : 'false');
    });

    sysadminPanels.forEach(p => {
        p.classList.toggle('active', p.id === `panel-${tabId}`);
    });

    // Persist tab preference
    try { localStorage.setItem('sysadmin_tab', tabId); } catch (e) { }
    history.replaceState(null, '', `#${tabId}`);

    // Resize charts when switching to dashboard (ApexCharts + hidden container fix)
    if (tabId === 'dashboard') {
        [usersByDayChart, userDistributionChart, subscriptionsByGatewayChart].forEach(c => {
            if (c) c.render();
        });
    }

    // Lazy-load feedback data on first visit
    if (tabId === 'feedback' && !feedbackStatsLoaded) {
        feedbackStatsLoaded = true;
        loadFeedbackStats();
        loadFeedbackList();
    }
}

// Tab click handlers
sysadminTabs.forEach(tab => {
    tab.addEventListener('click', () => switchTab(tab.dataset.tab));
});

// ============================================================================
// INITIALIZATION
// ============================================================================

// Restore active tab from URL hash or localStorage
(function initTab() {
    const hash = location.hash.replace('#', '');
    let initial = 'dashboard';

    if (hash && VALID_TABS.includes(hash)) {
        initial = hash;
    } else {
        try {
            const stored = localStorage.getItem('sysadmin_tab');
            if (stored && VALID_TABS.includes(stored)) initial = stored;
        } catch (e) { }
    }

    if (initial !== 'dashboard') switchTab(initial);
})();

// User filters form
if (userFiltersForm) {
    userFiltersForm.addEventListener('submit', function (e) {
        e.preventDefault();
        currentPage = 1;
        fetchUsers(currentPage);
    });
}

// Error logs filter form
const errorLogsFiltersForm = document.getElementById('errorLogsFilters');
if (errorLogsFiltersForm) {
    errorLogsFiltersForm.addEventListener('submit', function (e) {
        e.preventDefault();
        errorLogsCurrentPage = 1;
        loadErrorLogs();
    });
}

// Feedback filters form
const feedbackFiltersForm = document.getElementById('feedbackFilters');
if (feedbackFiltersForm) {
    feedbackFiltersForm.addEventListener('submit', function (e) {
        e.preventDefault();
        feedbackCurrentPage = 1;
        loadFeedbackList();
    });
}

if (cleanupLogsModalEl && window.lucide) {
    lucide.createIcons({ nodes: [cleanupLogsModalEl] });
}

if (cleanupLogsModalEl) {
    cleanupLogsModalEl.addEventListener('shown.bs.modal', () => {
        cleanupLogsDaysEl?.focus();
    });
}

cleanupLogsDaysEl?.addEventListener('change', updateCleanupLogsModalState);
cleanupLogsIncludeUnresolvedEl?.addEventListener('change', updateCleanupLogsModalState);
cleanupLogsConfirmBtn?.addEventListener('click', submitCleanupLogs);

// Auto-refresh toggle
const autoRefreshToggle = document.getElementById('errorLogsAutoRefresh');
if (autoRefreshToggle) {
    autoRefreshToggle.addEventListener('change', function () {
        this.checked ? startErrorLogsAutoRefresh() : stopErrorLogsAutoRefresh();
    });
}

// Init
initCustomize();
checkMaintenanceStatus();
fetchUsers(1);
loadErrorLogs(1);
startErrorLogsAutoRefresh();

if (typeof ApexCharts !== 'undefined') {
    loadStats();
} else {
    console.warn('ApexCharts não disponível — verifique carregamento no header.php');
}









