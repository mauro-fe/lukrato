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
 * - Stats charts (Chart.js)
 * ============================================================================
 */

const BASE_URL = (window.LK?.getBase?.() || '/');

// ============================================================================
// CACHE MANAGEMENT
// ============================================================================

function limparCache() {
    if (window.LKFeedback) {
        LKFeedback.confirm('Isso irá remover todos os arquivos de cache do sistema.', {
            title: 'Limpar Cache?',
            icon: 'question',
            confirmButtonText: 'Sim, limpar!',
            cancelButtonText: 'Cancelar'
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
                    const res = await fetch(`${BASE_URL}api/sysadmin/clear-cache`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        credentials: 'include'
                    });
                    const data = await res.json();
                    if (data.success) {
                        LKFeedback.success(data.message || 'Cache limpo com sucesso.', { toast: true });
                    } else {
                        LKFeedback.error(data.message || 'Erro ao limpar cache.');
                    }
                } catch (error) {
                    console.error('Erro ao limpar cache:', error);
                    LKFeedback.error('Erro de conexão ao limpar cache.');
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
        const res = await fetch(`${BASE_URL}api/sysadmin/maintenance`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();
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

    const doToggle = async (reason, minutes) => {
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
                LKFeedback.error(e.message);
            } else {
                alert('Erro: ' + e.message);
            }
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
    fetch(`${BASE_URL}api/sysadmin/users?${params.toString()}`)
        .then(res => res.json())
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
                <td><div class='user-info'><div class='user-avatar'>${escapeHtml((u.nome || 'U')[0].toUpperCase())}</div><span class='user-name'>${escapeHtml(u.nome || '-')}</span></div></td>
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
    fetch(`${BASE_URL}api/sysadmin/users/${userId}`)
        .then(res => res.json())
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
                            <div class="user-avatar-large">${(user.nome || 'U')[0].toUpperCase()}</div>
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
            LKFeedback.error('Erro ao buscar dados do usuário');
        });
}

function editUser(userId) {
    LKFeedback.hideLoading();
    LKFeedback.loading('Buscando dados do usuário');

    fetch(`${BASE_URL}api/sysadmin/users/${userId}`)
        .then(res => res.json())
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

                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    fetch(`${BASE_URL}api/sysadmin/users/${userId}`, {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                        body: JSON.stringify(payload)
                    })
                        .then(res => res.json())
                        .then(saveResponse => {
                            if (saveResponse.success) {
                                LKFeedback.success(saveResponse.message || 'Usuário atualizado com sucesso', { toast: true });
                                fetchUsers(currentPage);
                            } else {
                                LKFeedback.error(saveResponse.message || 'Erro ao atualizar usuário');
                            }
                        })
                        .catch(err => { console.error('Erro ao salvar:', err); LKFeedback.error('Erro ao salvar alterações'); });
                }
            });
        })
        .catch(err => { console.error('Erro ao buscar usuário:', err); LKFeedback.error('Erro ao buscar dados do usuário'); });
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
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            fetch(`${BASE_URL}api/sysadmin/users/${userId}`, {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken }
            })
                .then(res => res.json())
                .then(response => {
                    if (response.success) {
                        LKFeedback.success(response.message || 'Usuário removido com sucesso.', { toast: true });
                        fetchUsers(currentPage);
                    } else {
                        LKFeedback.error(response.message || 'Erro ao excluir usuário');
                    }
                })
                .catch(err => { console.error('Erro ao excluir:', err); LKFeedback.error('Erro ao excluir usuário'); });
        }
    });
}

// ============================================================================
// GRANT / REVOKE PRO ACCESS
// ============================================================================

function openGrantAccessModal() {
    Swal.fire({
        title: '<i data-lucide="crown"></i> Liberar Acesso PRO',
        html: `
            <div class="swal-form-layout">
                <div class="swal-form-group">
                    <label><i data-lucide="user"></i> Email ou ID do Usuário</label>
                    <input type="text" id="grantUserId" class="swal2-input" placeholder="Digite o email ou ID">
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

            if (!userId) { Swal.showValidationMessage('Por favor, informe o email ou ID do usuário'); return false; }
            let days = period;
            if (period === 'custom') {
                if (!customDays || customDays < 1) { Swal.showValidationMessage('Por favor, informe um número válido de dias'); return false; }
                days = customDays;
            }
            return { userId, days };
        }
    }).then((result) => {
        if (result.isConfirmed) grantProAccess(result.value.userId, result.value.days);
    });
}

async function grantProAccess(userId, days) {
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const response = await fetch(`${BASE_URL}api/sysadmin/grant-access`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            credentials: 'same-origin',
            body: JSON.stringify({ userId, days })
        });
        const data = await response.json();

        if (response.ok && data.success) {
            Swal.fire({
                icon: 'success', title: 'Acesso Liberado!',
                html: `<p><strong>${data.data.userName}</strong> agora tem acesso PRO por <strong>${days} dias</strong>.</p><p class="text-muted text-sm">Válido até: <strong>${data.data.expiresAt}</strong></p>`,
                confirmButtonColor: '#f97316'
            }).then(() => location.reload());
        } else {
            LKFeedback.error(data.message || 'Não foi possível liberar o acesso');
        }
    } catch (error) {
        console.error('Erro:', error);
        LKFeedback.error('Ocorreu um erro ao processar a solicitação');
    }
}

function openRevokeAccessModal() {
    Swal.fire({
        title: '<i data-lucide="ban"></i> Remover Acesso PRO',
        html: `
            <div class="swal-form-layout">
                <div class="swal-form-group">
                    <label><i data-lucide="user"></i> Email ou ID do Usuário</label>
                    <input type="text" id="revokeUserId" class="swal2-input" placeholder="Digite o email ou ID">
                </div>
                <div class="swal-alert-box danger">
                    <p><i data-lucide="triangle-alert"></i> <strong>Atenção:</strong> Esta ação irá cancelar imediatamente o acesso PRO do usuário.</p>
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
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const response = await fetch(`${BASE_URL}api/sysadmin/revoke-access`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            credentials: 'same-origin',
            body: JSON.stringify({ userId })
        });
        const data = await response.json();

        if (response.ok && data.success) {
            Swal.fire({
                icon: 'success', title: 'Acesso Removido!',
                html: `<p>O acesso PRO de <strong>${data.data.userName}</strong> foi removido com sucesso.</p><p class="text-muted text-sm">${data.data.subscriptionsCanceled} assinatura(s) cancelada(s).</p>`,
                confirmButtonColor: '#ef4444'
            }).then(() => location.reload());
        } else {
            LKFeedback.error(data.message || 'Não foi possível remover o acesso');
        }
    } catch (error) {
        console.error('Erro:', error);
        LKFeedback.error('Ocorreu um erro ao processar a solicitação');
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
        const res = await fetch(`${BASE_URL}api/sysadmin/error-logs/summary?hours=24`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();
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

        const [logsRes] = await Promise.all([
            fetch(`${BASE_URL}api/sysadmin/error-logs?${params.toString()}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } }),
            loadErrorLogsSummary()
        ]);

        const logsData = await logsRes.json();
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
        LKFeedback.error('Erro ao resolver log');
    }
}

function confirmCleanupLogs() {
    Swal.fire({
        title: 'Limpar Logs Antigos?',
        html: `<p class="text-muted mb-3">Remove logs <strong>resolvidos</strong> com mais de X dias.</p>
            <select id="cleanupDays" class="swal2-select" style="width:100%;">
                <option value="7">Mais de 7 dias</option><option value="15">Mais de 15 dias</option>
                <option value="30" selected>Mais de 30 dias</option><option value="60">Mais de 60 dias</option>
                <option value="90">Mais de 90 dias</option>
            </select>`,
        icon: 'warning', showCancelButton: true, confirmButtonColor: '#e74c3c', cancelButtonColor: '#95a5a6',
        confirmButtonText: 'Sim, limpar!', cancelButtonText: 'Cancelar',
        preConfirm: () => document.getElementById('cleanupDays').value
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                const data = await window.CsrfManager.fetchJson(`${BASE_URL}api/sysadmin/error-logs/cleanup`, {
                    method: 'DELETE', body: JSON.stringify({ days: parseInt(result.value) })
                });
                if (data.success) {
                    LKFeedback.success(data.message || `${data.data?.deleted || 0} logs removidos.`, { toast: true });
                    loadErrorLogs();
                } else {
                    LKFeedback.error(data.message || 'Erro na limpeza');
                }
            } catch (e) { console.error('Erro cleanup:', e); LKFeedback.error('Erro ao limpar logs'); }
        }
    });
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

    fetch(`${BASE_URL}api/sysadmin/stats`, {
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || '' },
        credentials: 'include'
    })
        .then(res => { if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`); return res.json(); })
        .then(response => {
            if (refreshBtn) refreshBtn.classList.remove('icon-spin');
            if (!response.success) { showStatsError(response.message || 'Erro ao carregar estatísticas'); return; }
            const data = response.data;
            if (!data || !data.overview || !data.charts) { showStatsError('Dados de estatísticas inválidos'); return; }
            updateStatsOverview(data);
            renderCharts(data.charts);
        })
        .catch(err => { if (refreshBtn) refreshBtn.classList.remove('icon-spin'); console.error('Erro ao carregar estatísticas:', err); showStatsError('Erro ao conectar com o servidor'); });
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

    // Line Chart - Users by Day
    const usersByDayCtx = document.getElementById('usersByDayChart')?.getContext('2d');
    if (usersByDayCtx) {
        if (usersByDayChart) usersByDayChart.destroy();
        const labels = Object.keys(charts.usersByDay).map(date => new Date(date).toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' }));
        const values = Object.values(charts.usersByDay);

        usersByDayChart = new Chart(usersByDayCtx, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    label: 'Novos Usuários', data: values, borderColor: chartColors.primary,
                    backgroundColor: 'rgba(249, 115, 22, 0.1)', borderWidth: 3, fill: true, tension: 0.4,
                    pointBackgroundColor: chartColors.primary, pointBorderColor: '#fff', pointBorderWidth: 2, pointRadius: 4, pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { backgroundColor: isDarkMode ? '#1e293b' : '#fff', titleColor: textColor, bodyColor: textColor, borderColor: gridColor, borderWidth: 1, padding: 12, displayColors: false } },
                scales: { x: { grid: { color: gridColor }, ticks: { color: textColor, maxRotation: 45 } }, y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: textColor, stepSize: 1 } } }
            }
        });
    }

    // Doughnut Chart - User Distribution
    const userDistCtx = document.getElementById('userDistributionChart')?.getContext('2d');
    if (userDistCtx) {
        if (userDistributionChart) userDistributionChart.destroy();
        userDistributionChart = new Chart(userDistCtx, {
            type: 'doughnut',
            data: { labels: Object.keys(charts.userDistribution), datasets: [{ data: Object.values(charts.userDistribution), backgroundColor: [chartColors.primary, chartColors.gray], borderWidth: 0, hoverOffset: 10 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { color: textColor, padding: 20, usePointStyle: true } } }, cutout: '60%' }
        });
    }

    // Doughnut Chart - Subscriptions by Gateway
    const gatewayCtx = document.getElementById('subscriptionsByGatewayChart')?.getContext('2d');
    if (gatewayCtx) {
        if (subscriptionsByGatewayChart) subscriptionsByGatewayChart.destroy();
        const gatewayLabels = Object.keys(charts.subscriptionsByGateway);
        const gatewayValues = Object.values(charts.subscriptionsByGateway);
        if (gatewayLabels.length === 0) { gatewayLabels.push('Nenhum'); gatewayValues.push(0); }
        const gatewayColors = gatewayLabels.map((_, i) => [chartColors.success, chartColors.secondary, chartColors.purple, chartColors.pink, chartColors.warning][i % 5]);

        subscriptionsByGatewayChart = new Chart(gatewayCtx, {
            type: 'doughnut',
            data: { labels: gatewayLabels.map(l => l.charAt(0).toUpperCase() + l.slice(1)), datasets: [{ data: gatewayValues, backgroundColor: gatewayColors, borderWidth: 0, hoverOffset: 10 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { color: textColor, padding: 20, usePointStyle: true } } }, cutout: '60%' }
        });
    }
}

// ============================================================================
// HELPERS
// ============================================================================

function formatDate(dt) {
    const d = new Date(dt);
    return d.toLocaleDateString('pt-BR') + ' ' + d.toLocaleTimeString('pt-BR').slice(0, 5);
}

function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
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
    }
});

// ============================================================================
// TAB NAVIGATION
// ============================================================================

const VALID_TABS = ['dashboard', 'controle', 'usuarios', 'logs'];
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

    // Resize charts when switching to dashboard (Chart.js + hidden canvas fix)
    if (tabId === 'dashboard') {
        [usersByDayChart, userDistributionChart, subscriptionsByGatewayChart].forEach(c => {
            if (c) c.resize();
        });
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

// Auto-refresh toggle
const autoRefreshToggle = document.getElementById('errorLogsAutoRefresh');
if (autoRefreshToggle) {
    autoRefreshToggle.addEventListener('change', function () {
        this.checked ? startErrorLogsAutoRefresh() : stopErrorLogsAutoRefresh();
    });
}

// Init
checkMaintenanceStatus();
fetchUsers(1);
loadErrorLogs(1);
startErrorLogsAutoRefresh();

if (typeof Chart !== 'undefined') {
    loadStats();
} else {
    console.warn('Chart.js não disponível — verifique carregamento no header.php');
}
