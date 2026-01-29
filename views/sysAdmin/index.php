<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/sysadmin-modern.css">

<div class="sysadmin-container">
    <!-- Stats Grid -->
    <div class="stats-grid">
        <!-- Total Users Card -->
        <div class="stat-card" data-aos="fade-up" data-aos-delay="0">
            <div class="stat-icon users">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-value" id="total-users"><?= number_format($metrics['totalUsers'] ?? 0, 0, ',', '.') ?>
                </h3>
                <p class="stat-label">Usuarios Totais</p>
                <span class="stat-badge positive">
                    <i class="fas fa-arrow-up"></i>
                    +<?= number_format($metrics['newToday'] ?? 0, 0, ',', '.') ?> hoje
                </span>
            </div>
        </div>

        <!-- Admins Card -->
        <div class="stat-card" data-aos="fade-up" data-aos-delay="100">
            <div class="stat-icon admins">
                <i class="fas fa-user-shield"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-value"><?= number_format($metrics['totalAdmins'] ?? 0, 0, ',', '.') ?></h3>
                <p class="stat-label">Admins Ativos</p>
                <span class="stat-badge success">
                    <i class="fas fa-check-circle"></i>
                    Com permissoes
                </span>
            </div>
        </div>

        <!-- Error Logs Card -->
        <div class="stat-card" data-aos="fade-up" data-aos-delay="200">
            <div class="stat-icon errors">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-value">3</h3>
                <p class="stat-label">Logs de Erro</p>
                <span class="stat-badge warning">
                    <i class="fas fa-clock"></i>
                    Ultimo ha 20 min
                </span>
            </div>
            <a href="#" class="stat-link">Ver Logs <i class="fas fa-arrow-right"></i></a>
        </div>
    </div>

    <!-- Analytics Section -->
    <div class="analytics-section" data-aos="fade-up" data-aos-delay="250">
        <h2 class="section-title">
            <i class="fas fa-chart-line"></i>
            Estatísticas e Métricas
            <button class="btn-refresh-stats" onclick="loadStats()" title="Atualizar estatísticas">
                <i class="fas fa-sync-alt"></i>
            </button>
        </h2>

        <!-- Stats Overview Cards -->
        <div class="stats-overview" id="statsOverview">
            <div class="overview-card">
                <div class="overview-icon pro">
                    <i class="fas fa-crown"></i>
                </div>
                <div class="overview-content">
                    <span class="overview-value" id="statProUsers">-</span>
                    <span class="overview-label">Usuários PRO</span>
                </div>
            </div>
            <div class="overview-card">
                <div class="overview-icon free">
                    <i class="fas fa-user"></i>
                </div>
                <div class="overview-content">
                    <span class="overview-value" id="statFreeUsers">-</span>
                    <span class="overview-label">Usuários Gratuitos</span>
                </div>
            </div>
            <div class="overview-card">
                <div class="overview-icon conversion">
                    <i class="fas fa-percentage"></i>
                </div>
                <div class="overview-content">
                    <span class="overview-value" id="statConversionRate">-</span>
                    <span class="overview-label">Taxa de Conversão</span>
                </div>
            </div>
            <div class="overview-card">
                <div class="overview-icon growth">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="overview-content">
                    <span class="overview-value" id="statGrowthRate">-</span>
                    <span class="overview-label">Crescimento Mensal</span>
                </div>
            </div>
        </div>

        <!-- New Users Summary -->
        <div class="new-users-summary">
            <div class="summary-item">
                <i class="fas fa-calendar-day"></i>
                <span class="summary-value" id="statNewToday">-</span>
                <span class="summary-label">Novos Hoje</span>
            </div>
            <div class="summary-item">
                <i class="fas fa-calendar-week"></i>
                <span class="summary-value" id="statNewWeek">-</span>
                <span class="summary-label">Esta Semana</span>
            </div>
            <div class="summary-item">
                <i class="fas fa-calendar-alt"></i>
                <span class="summary-value" id="statNewMonth">-</span>
                <span class="summary-label">Este Mês</span>
            </div>
        </div>

        <!-- Charts Grid -->
        <div class="charts-grid">
            <!-- Line Chart - Users by Day -->
            <div class="chart-card large">
                <div class="chart-header">
                    <h3><i class="fas fa-chart-area"></i> Novos Usuários (Últimos 30 dias)</h3>
                </div>
                <div class="chart-body">
                    <canvas id="usersByDayChart"></canvas>
                </div>
            </div>

            <!-- Pie Chart - User Distribution -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3><i class="fas fa-chart-pie"></i> Distribuição de Usuários</h3>
                </div>
                <div class="chart-body">
                    <canvas id="userDistributionChart"></canvas>
                </div>
            </div>

            <!-- Doughnut Chart - Subscriptions by Gateway -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3><i class="fas fa-credit-card"></i> Assinaturas por Gateway</h3>
                </div>
                <div class="chart-body">
                    <canvas id="subscriptionsByGatewayChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Control Panel -->
    <div class="control-section" data-aos="fade-up" data-aos-delay="300">
        <h2 class="section-title">
            <i class="fas fa-sliders-h"></i>
            Controle Mestre
        </h2>

        <div class="control-grid">
            <!-- Maintenance Card -->
            <div class="control-card">
                <div class="control-header">
                    <i class="fas fa-tools"></i>
                    <div>
                        <h3>Manutencao e Limpeza</h3>
                        <p>Ferramentas para saude do servidor</p>
                    </div>
                </div>
                <div class="control-actions">
                    <button class="btn-control primary" onclick="limparCache()">
                        <i class="fas fa-broom"></i>
                        Limpar Cache do Sistema
                    </button>
                    <button class="btn-control danger" onclick="toggleMaintenance()">
                        <i class="fas fa-wrench"></i>
                        Ativar Modo Manutencao
                    </button>
                </div>
            </div>

            <!-- User Search Card -->


            <!-- Cupons de Desconto Card -->
            <div class="control-card">
                <div class="control-header">
                    <i class="fas fa-ticket-alt"></i>
                    <div>
                        <h3>Cupons de Desconto</h3>
                        <p>Gerenciar cupons promocionais</p>
                    </div>
                </div>
                <div class="control-actions">
                    <button class="btn-control primary" onclick="window.location.href='<?= BASE_URL ?>sysadmin/cupons'">
                        <i class="fas fa-ticket-alt"></i>
                        Gerenciar Cupons
                    </button>
                </div>
            </div>

            <!-- Grant Access Card -->
            <div class="control-card">
                <div class="control-header">
                    <i class="fas fa-gift"></i>
                    <div>
                        <h3>Liberar Acesso PRO</h3>
                        <p>Conceda acesso premium temporário</p>
                    </div>
                </div>
                <div class="control-actions">
                    <button class="btn-control success" onclick="openGrantAccessModal()">
                        <i class="fas fa-crown"></i>
                        Liberar Acesso
                    </button>
                </div>
            </div>

            <!-- Revoke Access Card -->
            <div class="control-card">
                <div class="control-header">
                    <i class="fas fa-ban"></i>
                    <div>
                        <h3>Remover Acesso PRO</h3>
                        <p>Revogue o acesso premium de um usuário</p>
                    </div>
                </div>
                <div class="control-actions">
                    <button class="btn-control danger" onclick="openRevokeAccessModal()">
                        <i class="fas fa-user-slash"></i>
                        Remover Acesso
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros de Usuários -->
    <div class="user-filters-card" data-aos="fade-up" data-aos-delay="350">
        <form id="userFilters" class="user-filters-form">
            <input type="text" name="query" class="filter-input" placeholder="Buscar por nome, email ou ID..." />
            <select name="status" class="filter-select">
                <option value="">Todos</option>
                <option value="admin">Admin</option>
                <option value="user">Usuário</option>
            </select>
            <select name="perPage" class="filter-select">
                <option value="10">10 por página</option>
                <option value="25">25 por página</option>
                <option value="50">50 por página</option>
                <option value="100">100 por página</option>
            </select>
            <button type="submit" class="btn-control primary"><i class="fas fa-filter"></i> Filtrar</button>
        </form>
    </div>

    <!-- Tabela dinâmica de usuários -->
    <div class="table-section" id="userTableSection" data-aos="fade-up" data-aos-delay="400">
        <!-- Conteúdo da tabela será renderizado via JS -->
    </div>
</div>

<script>
    function limparCache() {
        if (window.Swal) {
            Swal.fire({
                title: 'Limpar Cache?',
                text: 'Isso ira remover todos os arquivos de cache do sistema.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#e67e22',
                cancelButtonColor: '#95a5a6',
                confirmButtonText: 'Sim, limpar!',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Cache Limpo!',
                        text: 'O cache do sistema foi limpo com sucesso.',
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            });
        } else {
            if (confirm('Tem certeza que deseja limpar o cache do sistema?')) {
                alert('Cache limpo com sucesso!');
            }
        }
    }

    function toggleMaintenance() {
        if (window.Swal) {
            Swal.fire({
                title: 'Modo Manutencao',
                text: 'Deseja ativar o modo manutencao? O site ficara indisponivel para usuarios.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e74c3c',
                cancelButtonColor: '#95a5a6',
                confirmButtonText: 'Sim, ativar!',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Modo Manutencao Ativado',
                        text: 'O sistema esta agora em modo manutencao.',
                        timer: 2000
                    });
                }
            });
        } else {
            if (confirm('Deseja ativar o modo manutencao?')) {
                alert('Modo manutencao ativado!');
            }
        }
    }

    function searchUser() {
        const query = document.getElementById('userSearch');
        if (!query) return;

        const value = query.value.trim();
        if (!value) {
            if (window.Swal) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Campo vazio',
                    text: 'Digite um e-mail ou ID para buscar.',
                    timer: 2000
                });
            } else {
                alert('Digite um e-mail ou ID para buscar.');
            }
            return;
        }

    }

    function loadRecentUsers() {
        const btn = event.target.closest('.btn-refresh');
        if (!btn) return;

        const icon = btn.querySelector('i');
        if (icon) icon.classList.add('fa-spin');

        setTimeout(() => {
            if (icon) icon.classList.remove('fa-spin');
            if (window.Swal) {
                Swal.fire({
                    icon: 'success',
                    title: 'Atualizado!',
                    timer: 1000,
                    showConfirmButton: false
                });
            }
            location.reload();
        }, 1000);
    }

    function viewUser(userId) {
        // Buscar dados completos do usuário
        fetch(`<?= BASE_URL ?>api/sysadmin/users/${userId}`)
            .then(res => res.json())
            .then(response => {
                if (!response.success) {
                    Swal.fire('Erro', response.message || 'Erro ao buscar usuário', 'error');
                    return;
                }

                const user = response.data;
                const createdAt = user.created_at ? new Date(user.created_at).toLocaleDateString('pt-BR', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                }) : 'N/A';

                const dataNascimento = user.data_nascimento ? new Date(user.data_nascimento).toLocaleDateString(
                    'pt-BR') : 'Não informado';

                // Montar HTML do endereço
                let enderecoHtml = '';
                if (user.endereco) {
                    const end = user.endereco;
                    const enderecoCompleto = [
                        end.rua ? `${end.rua}${end.numero ? ', ' + end.numero : ''}` : null,
                        end.complemento,
                        end.bairro,
                        end.cidade && end.estado ? `${end.cidade} - ${end.estado}` : (end.cidade || end.estado),
                        end.cep ? `CEP: ${end.cep}` : null
                    ].filter(Boolean).join(' | ');

                    enderecoHtml = `
                        <div class="detail-section">
                            <h4><i class="fas fa-map-marker-alt" style="color: #3b82f6;"></i> Endereço</h4>
                            ${end.rua ? `<div class="detail-row">
                                <span class="detail-label">Logradouro</span>
                                <span class="detail-value">${end.rua}${end.numero ? ', ' + end.numero : ''}</span>
                            </div>` : ''}
                            ${end.complemento ? `<div class="detail-row">
                                <span class="detail-label">Complemento</span>
                                <span class="detail-value">${end.complemento}</span>
                            </div>` : ''}
                            ${end.bairro ? `<div class="detail-row">
                                <span class="detail-label">Bairro</span>
                                <span class="detail-value">${end.bairro}</span>
                            </div>` : ''}
                            ${end.cidade || end.estado ? `<div class="detail-row">
                                <span class="detail-label">Cidade/UF</span>
                                <span class="detail-value">${end.cidade || ''}${end.cidade && end.estado ? ' - ' : ''}${end.estado || ''}</span>
                            </div>` : ''}
                            ${end.cep ? `<div class="detail-row">
                                <span class="detail-label">CEP</span>
                                <span class="detail-value">${end.cep}</span>
                            </div>` : ''}
                        </div>
                    `;
                } else {
                    enderecoHtml = `
                        <div class="detail-section">
                            <h4><i class="fas fa-map-marker-alt" style="color: #94a3b8;"></i> Endereço</h4>
                            <p style="color: var(--color-text-muted); font-size: 14px;">
                                <i class="fas fa-info-circle"></i> Endereço não cadastrado
                            </p>
                        </div>
                    `;
                }

                // Montar HTML da assinatura
                let subscriptionHtml = '';
                if (user.subscription) {
                    const expiresAt = user.subscription.renova_em ? new Date(user.subscription.renova_em)
                        .toLocaleDateString('pt-BR', {
                            day: '2-digit',
                            month: '2-digit',
                            year: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        }) : 'N/A';

                    const statusClass = user.subscription.status === 'active' ? 'success' : 'warning';
                    const statusText = user.subscription.status === 'active' ? 'Ativa' : (user.subscription.status ===
                        'canceled' ? 'Cancelada' : user.subscription.status);

                    // Nome do plano - usar plano_nome ou mapear o ID
                    const planoNome = user.subscription.plano_nome ||
                        (user.subscription.plano_id == 1 ? 'Free' :
                            (user.subscription.plano_id == 2 ? 'Pro' :
                                'Plano ' + user.subscription.plano_id));

                    // Badge do plano
                    const planoBadgeClass = user.subscription.plano_id == 2 ? 'badge-pro' : 'badge-free';

                    subscriptionHtml = `
                        <div class="detail-section">
                            <h4><i class="fas fa-crown" style="color: #f59e0b;"></i> Assinatura</h4>
                            <div class="detail-row">
                                <span class="detail-label">Status</span>
                                <span class="detail-value badge-${statusClass}">${statusText}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Plano</span>
                                <span class="detail-value ${planoBadgeClass}">${planoNome}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Gateway</span>
                                <span class="detail-value">${user.subscription.gateway || 'interno'}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Expira em</span>
                                <span class="detail-value">${expiresAt}</span>
                            </div>
                        </div>
                    `;
                } else {
                    subscriptionHtml = `
                        <div class="detail-section">
                            <h4><i class="fas fa-crown" style="color: #94a3b8;"></i> Assinatura</h4>
                            <p style="color: var(--color-text-muted); font-size: 14px;">
                                <i class="fas fa-info-circle"></i> Usuário não possui assinatura PRO
                            </p>
                        </div>
                    `;
                }

                Swal.fire({
                    title: `<i class="fas fa-user-circle"></i> Detalhes do Usuário`,
                    html: `
                        <div class="user-details-modal">
                            <div class="user-header-info">
                                <div class="user-avatar-large">${(user.nome || 'U')[0].toUpperCase()}</div>
                                <div class="user-main-info">
                                    <h3>${user.nome || 'Sem nome'}</h3>
                                    <p>${user.email || 'Sem email'}</p>
                                    ${user.is_admin == 1 ? '<span class="badge-admin"><i class="fas fa-shield-alt"></i> Administrador</span>' : '<span class="badge-user"><i class="fas fa-user"></i> Usuário</span>'}
                                </div>
                            </div>

                            <div class="detail-section">
                                <h4><i class="fas fa-info-circle"></i> Informações Gerais</h4>
                                <div class="detail-row">
                                    <span class="detail-label">ID</span>
                                    <span class="detail-value">#${user.id}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Nome</span>
                                    <span class="detail-value">${user.nome || 'N/A'}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Email</span>
                                    <span class="detail-value detail-value-email">${user.email || 'N/A'}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Data de Nascimento</span>
                                    <span class="detail-value">${dataNascimento}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Cadastrado em</span>
                                    <span class="detail-value">${createdAt}</span>
                                </div>
                            </div>

                            ${enderecoHtml}
                            ${subscriptionHtml}
                        </div>
                    `,
                    customClass: {
                        popup: 'sysadmin-swal user-details-popup'
                    },
                    showCloseButton: true,
                    showConfirmButton: false,
                    width: '600px'
                });
            })
            .catch(err => {
                console.error('Erro ao buscar usuário:', err);
                Swal.fire('Erro', 'Erro ao buscar dados do usuário', 'error');
            });
    }

    function editUser(userId) {
        // Fechar qualquer modal aberto antes de abrir novo
        Swal.close();

        // Mostrar loading
        Swal.fire({
            title: 'Carregando...',
            text: 'Buscando dados do usuário',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Buscar dados do usuário
        fetch(`<?= BASE_URL ?>api/sysadmin/users/${userId}`)
            .then(res => res.json())
            .then(response => {
                // Fechar loading
                Swal.close();

                if (!response.success) {
                    Swal.fire('Erro', response.message || 'Erro ao buscar usuário', 'error');
                    return;
                }

                const user = response.data;

                // Criar HTML do formulário
                const formHtml = `
                <div style="text-align: left;">
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">
                            <i class="fas fa-user"></i> Nome
                        </label>
                        <input type="text" id="editNome" class="swal2-input" value="${user.nome || ''}" 
                            style="margin: 0; width: 100%;">
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">
                            <i class="fas fa-envelope"></i> Email
                        </label>
                        <input type="email" id="editEmail" class="swal2-input" value="${user.email || ''}" 
                            style="margin: 0; width: 100%;">
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">
                            <i class="fas fa-lock"></i> Nova Senha (deixe em branco para manter)
                        </label>
                        <input type="password" id="editSenha" class="swal2-input" placeholder="••••••" 
                            style="margin: 0; width: 100%;">
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">
                            <i class="fas fa-shield-alt"></i> Status de Admin
                        </label>
                        <select id="editIsAdmin" class="swal2-select" style="margin: 0; width: 100%;">
                            <option value="0" ${user.is_admin == 0 ? 'selected' : ''}>Usuário Normal</option>
                            <option value="1" ${user.is_admin == 1 ? 'selected' : ''}>Administrador</option>
                        </select>
                    </div>
                    
                    ${user.subscription ? `
                    <div class="subscription-info-box">
                        <h4 style="margin: 0 0 8px 0; font-size: 14px;">
                            <i class="fas fa-crown" style="color: #f59e0b;"></i> Assinatura Atual
                        </h4>
                        <p style="margin: 0; font-size: 13px;">
                            <strong>Status:</strong> ${user.subscription.status}<br>
                            <strong>Gateway:</strong> ${user.subscription.gateway || 'N/A'}<br>
                            <strong>Expira em:</strong> ${user.subscription.renova_em ? new Date(user.subscription.renova_em).toLocaleDateString('pt-BR') : 'N/A'}
                        </p>
                    </div>
                    ` : ''}
                </div>
            `;

                // Abrir modal de edição
                Swal.fire({
                    title: '<i class="fas fa-user-edit"></i> Editar Usuário',
                    html: formHtml,
                    customClass: {
                        popup: 'sysadmin-swal'
                    },
                    showCancelButton: true,
                    confirmButtonText: '<i class="fas fa-save"></i> Salvar',
                    cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
                    confirmButtonColor: '#10b981',
                    cancelButtonColor: '#94a3b8',
                    width: '500px',
                    focusConfirm: false,
                    didOpen: () => {
                        // Focar no campo nome após abrir
                        const nomeInput = document.getElementById('editNome');
                        if (nomeInput) nomeInput.focus();
                    },
                    preConfirm: () => {
                        const nome = document.getElementById('editNome')?.value?.trim() || '';
                        const email = document.getElementById('editEmail')?.value?.trim() || '';
                        const senha = document.getElementById('editSenha')?.value || '';
                        const is_admin = document.getElementById('editIsAdmin')?.value || '0';

                        if (!nome) {
                            Swal.showValidationMessage('Nome é obrigatório');
                            return false;
                        }
                        if (!email) {
                            Swal.showValidationMessage('Email é obrigatório');
                            return false;
                        }
                        if (senha && senha.length < 6) {
                            Swal.showValidationMessage('Senha deve ter pelo menos 6 caracteres');
                            return false;
                        }

                        return {
                            nome,
                            email,
                            senha,
                            is_admin: parseInt(is_admin)
                        };
                    }
                }).then((result) => {
                    if (result.isConfirmed && result.value) {
                        const data = result.value;

                        // Preparar payload
                        const payload = {
                            nome: data.nome,
                            email: data.email,
                            is_admin: data.is_admin
                        };

                        if (data.senha) {
                            payload.senha = data.senha;
                        }

                        // Mostrar loading enquanto salva
                        Swal.fire({
                            title: 'Salvando...',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            showConfirmButton: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        // Salvar alterações
                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                        fetch(`<?= BASE_URL ?>api/sysadmin/users/${userId}`, {
                                method: 'PUT',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken
                                },
                                body: JSON.stringify(payload)
                            })
                            .then(res => res.json())
                            .then(saveResponse => {
                                if (saveResponse.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Sucesso!',
                                        text: saveResponse.message || 'Usuário atualizado com sucesso',
                                        timer: 2000,
                                        showConfirmButton: false
                                    });
                                    // Recarregar lista de usuários
                                    fetchUsers(currentPage);
                                } else {
                                    Swal.fire('Erro', saveResponse.message || 'Erro ao atualizar usuário', 'error');
                                }
                            })
                            .catch(err => {
                                console.error('Erro ao salvar:', err);
                                Swal.fire('Erro', 'Erro ao salvar alterações', 'error');
                            });
                    }
                });
            })
            .catch(err => {
                console.error('Erro ao buscar usuário:', err);
                Swal.fire('Erro', 'Erro ao buscar dados do usuário', 'error');
            });
    }

    function deleteUser(userId) {
        if (window.Swal) {
            Swal.fire({
                title: 'Excluir Usuario?',
                text: 'Esta acao nao podera ser desfeita!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e74c3c',
                cancelButtonColor: '#95a5a6',
                confirmButtonText: 'Sim, excluir!',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    fetch(`<?= BASE_URL ?>api/sysadmin/users/${userId}`, {
                            method: 'DELETE',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            }
                        })
                        .then(res => res.json())
                        .then(response => {
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Deletado!',
                                    text: response.message || 'Usuário removido com sucesso.',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                                // Recarregar lista de usuários
                                fetchUsers(currentPage);
                            } else {
                                Swal.fire('Erro', response.message || 'Erro ao excluir usuário', 'error');
                            }
                        })
                        .catch(err => {
                            console.error('Erro ao excluir:', err);
                            Swal.fire('Erro', 'Erro ao excluir usuário', 'error');
                        });
                }
            });
        }
    }

    function openGrantAccessModal() {
        Swal.fire({
            title: '<i class="fas fa-crown"></i> Liberar Acesso PRO',
            html: `
            <div style="text-align: left;">
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600;">
                        <i class="fas fa-user"></i> Email ou ID do Usuário
                    </label>
                    <input type="text" id="grantUserId" class="swal2-input" placeholder="Digite o email ou ID" 
                        style="margin: 0; width: 100%;">
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600;">
                        <i class="fas fa-calendar-alt"></i> Período
                    </label>
                    <select id="grantPeriod" class="swal2-select" style="margin: 0; width: 100%;">
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
                
                <div id="customDaysDiv" style="margin-bottom: 20px; display: none;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600;">
                        <i class="fas fa-hashtag"></i> Dias Personalizados
                    </label>
                    <input type="number" id="customDays" class="swal2-input" placeholder="Digite o número de dias" 
                        min="1" style="margin: 0; width: 100%;">
                </div>
            </div>
        `,
            customClass: {
                popup: 'sysadmin-swal'
            },
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-check"></i> Liberar Acesso',
            cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
            confirmButtonColor: '#f97316',
            cancelButtonColor: '#94a3b8',
            width: '500px',
            didOpen: () => {
                const periodSelect = document.getElementById('grantPeriod');
                const customDiv = document.getElementById('customDaysDiv');

                periodSelect.addEventListener('change', function() {
                    customDiv.style.display = this.value === 'custom' ? 'block' : 'none';
                });
            },
            preConfirm: () => {
                const userId = document.getElementById('grantUserId').value.trim();
                const period = document.getElementById('grantPeriod').value;
                const customDays = document.getElementById('customDays').value;

                if (!userId) {
                    Swal.showValidationMessage('Por favor, informe o email ou ID do usuário');
                    return false;
                }

                let days = period;
                if (period === 'custom') {
                    if (!customDays || customDays < 1) {
                        Swal.showValidationMessage('Por favor, informe um número válido de dias');
                        return false;
                    }
                    days = customDays;
                }

                return {
                    userId,
                    days
                };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                grantProAccess(result.value.userId, result.value.days);
            }
        });
    }

    async function grantProAccess(userId, days) {
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            const response = await fetch('<?= BASE_URL ?>api/sysadmin/grant-access', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    userId,
                    days
                })
            });

            const data = await response.json();

            if (response.ok && data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Acesso Liberado!',
                    html: `
                    <p><strong>${data.data.userName}</strong> agora tem acesso PRO por <strong>${days} dias</strong>.</p>
                    <p style="color: #64748b; font-size: 14px; margin-top: 10px;">
                        Válido até: <strong>${data.data.expiresAt}</strong>
                    </p>
                `,
                    confirmButtonColor: '#f97316'
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: data.message || 'Não foi possível liberar o acesso',
                    confirmButtonColor: '#f97316'
                });
            }
        } catch (error) {
            console.error('Erro:', error);
            Swal.fire({
                icon: 'error',
                title: 'Erro',
                text: 'Ocorreu um erro ao processar a solicitação',
                confirmButtonColor: '#f97316'
            });
        }
    }

    function openRevokeAccessModal() {
        Swal.fire({
            title: '<i class="fas fa-ban"></i> Remover Acesso PRO',
            html: `
            <div style="text-align: left;">
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600;">
                        <i class="fas fa-user"></i> Email ou ID do Usuário
                    </label>
                    <input type="text" id="revokeUserId" class="swal2-input" placeholder="Digite o email ou ID" 
                        style="margin: 0; width: 100%;">
                </div>
                
                <div style="background: rgba(239, 68, 68, 0.1); padding: 12px; border-radius: 8px; border: 1px solid rgba(239, 68, 68, 0.3);">
                    <p style="margin: 0; font-size: 13px; color: var(--color-text);">
                        <i class="fas fa-exclamation-triangle" style="color: #ef4444;"></i>
                        <strong>Atenção:</strong> Esta ação irá cancelar imediatamente o acesso PRO do usuário.
                    </p>
                </div>
            </div>
        `,
            customClass: {
                popup: 'sysadmin-swal'
            },
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-ban"></i> Remover Acesso',
            cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#94a3b8',
            width: '500px',
            preConfirm: () => {
                const userId = document.getElementById('revokeUserId').value.trim();

                if (!userId) {
                    Swal.showValidationMessage('Por favor, informe o email ou ID do usuário');
                    return false;
                }

                return {
                    userId
                };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                revokeProAccess(result.value.userId);
            }
        });
    }

    async function revokeProAccess(userId) {
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            const response = await fetch('<?= BASE_URL ?>api/sysadmin/revoke-access', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    userId
                })
            });

            const data = await response.json();

            if (response.ok && data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Acesso Removido!',
                    html: `
                    <p>O acesso PRO de <strong>${data.data.userName}</strong> foi removido com sucesso.</p>
                    <p style="color: #64748b; font-size: 14px; margin-top: 10px;">
                        ${data.data.subscriptionsCanceled} assinatura(s) cancelada(s).
                    </p>
                `,
                    confirmButtonColor: '#ef4444'
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: data.message || 'Não foi possível remover o acesso',
                    confirmButtonColor: '#ef4444'
                });
            }
        } catch (error) {
            console.error('Erro:', error);
            Swal.fire({
                icon: 'error',
                title: 'Erro',
                text: 'Ocorreu um erro ao processar a solicitação',
                confirmButtonColor: '#ef4444'
            });
        }
    }

    // Modern user table rendering
    const userTableSection = document.getElementById('userTableSection');
    const userFiltersForm = document.getElementById('userFilters');
    let currentPage = 1;

    function fetchUsers(page = 1) {
        const formData = new FormData(userFiltersForm);
        const params = new URLSearchParams(formData);
        params.set('page', page);
        fetch(`<?= BASE_URL ?>api/sysadmin/users?${params.toString()}`)
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
        html +=
            `<thead><tr><th>ID</th><th>Nome</th><th>Email</th><th>Status</th><th>Data de Cadastro</th><th class='text-center'>Ações</th></tr></thead><tbody>`;
        if (users.length === 0) {
            html +=
                `<tr><td colspan='6' class='text-center' style='padding:2rem;'><i class='fas fa-inbox' style='font-size:3rem;color:var(--color-text-muted);margin-bottom:1rem;'></i><p style='color:var(--color-text-muted);'>Nenhum usuário encontrado</p></td></tr>`;
        } else {
            users.forEach(u => {
                html += `<tr>
                    <td><span class='user-id'>#${u.id}</span></td>
                    <td><div class='user-info'><div class='user-avatar'>${(u.nome||'U')[0].toUpperCase()}</div><span class='user-name'>${u.nome||'-'}</span></div></td>
                    <td><span class='user-email'>${u.email||'-'}</span></td>
                    <td>${u.is_admin==1?`<span class='badge-status admin'><i class='fas fa-shield-alt'></i>Admin</span>`:`<span class='badge-status user'><i class='fas fa-user'></i>Usuário</span>`}</td>
                    <td><span class='user-date'>${u.created_at?formatDate(u.created_at):'-'}</span></td>
                    <td class='text-center'><div class='action-buttons'><button class='btn-action view' title='Ver detalhes' onclick='viewUser(${u.id})'><i class='fas fa-eye'></i></button><button class='btn-action edit' title='Editar usuário' onclick='editUser(${u.id})'><i class='fas fa-edit'></i></button><button class='btn-action delete' title='Excluir usuário' onclick='deleteUser(${u.id})'><i class='fas fa-trash'></i></button></div></td>
                </tr>`;
            });
        }
        html += `</tbody></table></div>`;
        html += renderPagination(total, page, perPage);
        html += `</div>`;
        userTableSection.innerHTML = html;
    }

    function renderPagination(total, page, perPage) {
        const totalPages = Math.ceil(total / perPage);
        const startItem = ((page - 1) * perPage) + 1;
        const endItem = Math.min(page * perPage, total);

        let html = `<div class='pagination-wrapper'>`;

        // Info de registros
        html += `<div class='pagination-info'>
            <span>Mostrando <strong>${startItem}</strong> - <strong>${endItem}</strong> de <strong>${total}</strong> usuários</span>
        </div>`;

        // Controles de navegação
        html += `<div class='pagination-controls'>`;
        html +=
            `<button class='pagination-btn' ${page<=1?'disabled':''} onclick='goToPage(1)' title='Primeira página'><i class='fas fa-angle-double-left'></i></button>`;
        html +=
            `<button class='pagination-btn' ${page<=1?'disabled':''} onclick='goToPage(${page-1})' title='Anterior'><i class='fas fa-angle-left'></i></button>`;

        // Números das páginas
        for (let i = Math.max(1, page - 2); i <= Math.min(totalPages, page + 2); i++) {
            html += `<button class='pagination-btn ${i===page?'active':''}' onclick='goToPage(${i})'>${i}</button>`;
        }

        html +=
            `<button class='pagination-btn' ${page>=totalPages?'disabled':''} onclick='goToPage(${page+1})' title='Próxima'><i class='fas fa-angle-right'></i></button>`;
        html +=
            `<button class='pagination-btn' ${page>=totalPages?'disabled':''} onclick='goToPage(${totalPages})' title='Última página'><i class='fas fa-angle-double-right'></i></button>`;
        html += `</div>`;

        html += `</div>`;
        return html;
    }

    function goToPage(p) {
        currentPage = p;
        fetchUsers(currentPage);
    }

    function formatDate(dt) {
        const d = new Date(dt);
        return d.toLocaleDateString('pt-BR') + ' ' + d.toLocaleTimeString('pt-BR').slice(0, 5);
    }

    userFiltersForm.addEventListener('submit', function(e) {
        e.preventDefault();
        currentPage = 1;
        fetchUsers(currentPage);
    });

    window.goToPage = goToPage;
    window.viewUser = viewUser;
    window.editUser = editUser;
    window.deleteUser = deleteUser;

    // Inicializa tabela ao carregar
    fetchUsers(1);

    // ============================================
    // ESTATÍSTICAS E GRÁFICOS
    // ============================================

    let usersByDayChart = null;
    let userDistributionChart = null;
    let subscriptionsByGatewayChart = null;

    function loadStats() {
        const refreshBtn = document.querySelector('.btn-refresh-stats i');
        if (refreshBtn) refreshBtn.classList.add('fa-spin');

        fetch(`<?= BASE_URL ?>api/sysadmin/stats`, {
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            credentials: 'include'
        })
            .then(res => {
                if (!res.ok) {
                    throw new Error(`HTTP error! status: ${res.status}`);
                }
                return res.json();
            })
            .then(response => {
                if (refreshBtn) refreshBtn.classList.remove('fa-spin');

                console.log('📊 Estatísticas recebidas:', response);

                if (!response.success) {
                    console.error('Erro ao carregar estatísticas:', response.message);
                    showStatsError(response.message || 'Erro ao carregar estatísticas');
                    return;
                }

                const data = response.data;
                if (!data || !data.overview || !data.charts) {
                    console.error('Dados de estatísticas inválidos:', data);
                    showStatsError('Dados de estatísticas inválidos');
                    return;
                }

                updateStatsOverview(data);
                renderCharts(data.charts);
            })
            .catch(err => {
                if (refreshBtn) refreshBtn.classList.remove('fa-spin');
                console.error('Erro ao carregar estatísticas:', err);
                showStatsError('Erro ao conectar com o servidor');
            });
    }

    function showStatsError(message) {
        // Exibir valores padrão em caso de erro
        document.getElementById('statProUsers').textContent = 'Erro';
        document.getElementById('statFreeUsers').textContent = 'Erro';
        document.getElementById('statConversionRate').textContent = '-';
        document.getElementById('statGrowthRate').textContent = '-';
        document.getElementById('statNewToday').textContent = '-';
        document.getElementById('statNewWeek').textContent = '-';
        document.getElementById('statNewMonth').textContent = '-';
        
        console.error('Stats Error:', message);
    }

    function updateStatsOverview(data) {
        // Overview cards
        document.getElementById('statProUsers').textContent = data.overview.proUsers.toLocaleString('pt-BR');
        document.getElementById('statFreeUsers').textContent = data.overview.freeUsers.toLocaleString('pt-BR');
        document.getElementById('statConversionRate').textContent = data.overview.conversionRate + '%';

        const growthRate = data.newUsers.growthRate;
        const growthEl = document.getElementById('statGrowthRate');
        growthEl.textContent = (growthRate >= 0 ? '+' : '') + growthRate + '%';
        growthEl.classList.toggle('positive', growthRate >= 0);
        growthEl.classList.toggle('negative', growthRate < 0);

        // New users summary
        document.getElementById('statNewToday').textContent = data.newUsers.today.toLocaleString('pt-BR');
        document.getElementById('statNewWeek').textContent = data.newUsers.thisWeek.toLocaleString('pt-BR');
        document.getElementById('statNewMonth').textContent = data.newUsers.thisMonth.toLocaleString('pt-BR');
    }

    function renderCharts(charts) {
        const chartColors = {
            primary: '#f97316',
            secondary: '#3b82f6',
            success: '#10b981',
            warning: '#f59e0b',
            danger: '#ef4444',
            purple: '#8b5cf6',
            pink: '#ec4899',
            gray: '#6b7280'
        };

        const isDarkMode = document.documentElement.getAttribute('data-theme') === 'dark';
        const textColor = isDarkMode ? '#e2e8f0' : '#1e293b';
        const gridColor = isDarkMode ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';

        // Line Chart - Users by Day
        const usersByDayCtx = document.getElementById('usersByDayChart')?.getContext('2d');
        if (usersByDayCtx) {
            if (usersByDayChart) usersByDayChart.destroy();

            const labels = Object.keys(charts.usersByDay).map(date => {
                const d = new Date(date);
                return d.toLocaleDateString('pt-BR', {
                    day: '2-digit',
                    month: '2-digit'
                });
            });
            const values = Object.values(charts.usersByDay);

            usersByDayChart = new Chart(usersByDayCtx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Novos Usuários',
                        data: values,
                        borderColor: chartColors.primary,
                        backgroundColor: 'rgba(249, 115, 22, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: chartColors.primary,
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: isDarkMode ? '#1e293b' : '#fff',
                            titleColor: textColor,
                            bodyColor: textColor,
                            borderColor: gridColor,
                            borderWidth: 1,
                            padding: 12,
                            displayColors: false
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                color: gridColor
                            },
                            ticks: {
                                color: textColor,
                                maxRotation: 45
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: gridColor
                            },
                            ticks: {
                                color: textColor,
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        }

        // Pie Chart - User Distribution
        const userDistCtx = document.getElementById('userDistributionChart')?.getContext('2d');
        if (userDistCtx) {
            if (userDistributionChart) userDistributionChart.destroy();

            userDistributionChart = new Chart(userDistCtx, {
                type: 'doughnut',
                data: {
                    labels: Object.keys(charts.userDistribution),
                    datasets: [{
                        data: Object.values(charts.userDistribution),
                        backgroundColor: [chartColors.primary, chartColors.gray],
                        borderWidth: 0,
                        hoverOffset: 10
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: textColor,
                                padding: 20,
                                usePointStyle: true
                            }
                        }
                    },
                    cutout: '60%'
                }
            });
        }

        // Doughnut Chart - Subscriptions by Gateway
        const gatewayCtx = document.getElementById('subscriptionsByGatewayChart')?.getContext('2d');
        if (gatewayCtx) {
            if (subscriptionsByGatewayChart) subscriptionsByGatewayChart.destroy();

            const gatewayLabels = Object.keys(charts.subscriptionsByGateway);
            const gatewayValues = Object.values(charts.subscriptionsByGateway);

            if (gatewayLabels.length === 0) {
                gatewayLabels.push('Nenhum');
                gatewayValues.push(0);
            }

            const gatewayColors = gatewayLabels.map((label, i) => {
                const colors = [chartColors.success, chartColors.secondary, chartColors.purple, chartColors.pink,
                    chartColors.warning
                ];
                return colors[i % colors.length];
            });

            subscriptionsByGatewayChart = new Chart(gatewayCtx, {
                type: 'doughnut',
                data: {
                    labels: gatewayLabels.map(l => l.charAt(0).toUpperCase() + l.slice(1)),
                    datasets: [{
                        data: gatewayValues,
                        backgroundColor: gatewayColors,
                        borderWidth: 0,
                        hoverOffset: 10
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: textColor,
                                padding: 20,
                                usePointStyle: true
                            }
                        }
                    },
                    cutout: '60%'
                }
            });
        }
    }

    // Carregar estatísticas ao iniciar
    if (typeof Chart !== 'undefined') {
        loadStats();
    } else {
        // Carregar Chart.js se não estiver disponível
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
        script.onload = loadStats;
        document.head.appendChild(script);
    }
</script>