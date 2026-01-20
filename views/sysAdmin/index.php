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
            <div class="control-card">
                <div class="control-header">
                    <i class="fas fa-search"></i>
                    <div>
                        <h3>Buscar Usuario</h3>
                        <p>Edite, promova ou bloqueie qualquer pessoa</p>
                    </div>
                </div>
                <div class="control-actions">
                    <div class="search-box">
                        <input type="text" id="userSearch" placeholder="Digite e-mail ou ID do usuario..."
                            class="search-input">
                        <button class="btn-control primary" onclick="searchUser()">
                            <i class="fas fa-search"></i>
                            Buscar
                        </button>
                    </div>
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

        console.log('Buscando:', value);
        // Implementar logica de busca aqui
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

    function editUser(userId) {
        console.log('Editando usuario:', userId);
        // Implementar logica de edicao
        if (window.Swal) {
            Swal.fire({
                icon: 'info',
                title: 'Em desenvolvimento',
                text: 'Funcionalidade de edicao em breve.',
                timer: 2000
            });
        }
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
                    console.log('Deletando usuario:', userId);
                    Swal.fire('Deletado!', 'Usuario removido.', 'success');
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

            console.log('🔍 Response status:', response.status);
            console.log('🔍 Response ok:', response.ok);
            console.log('🔍 Data received:', data);

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
        html += `<thead><tr><th>ID</th><th>Nome</th><th>Email</th><th>Status</th><th>Data de Cadastro</th><th class='text-center'>Ações</th></tr></thead><tbody>`;
        if (users.length === 0) {
            html += `<tr><td colspan='6' class='text-center' style='padding:2rem;'><i class='fas fa-inbox' style='font-size:3rem;color:var(--color-text-muted);margin-bottom:1rem;'></i><p style='color:var(--color-text-muted);'>Nenhum usuário encontrado</p></td></tr>`;
        } else {
            users.forEach(u => {
                html += `<tr>
                    <td><span class='user-id'>#${u.id}</span></td>
                    <td><div class='user-info'><div class='user-avatar'>${(u.nome||'U')[0].toUpperCase()}</div><span class='user-name'>${u.nome||'-'}</span></div></td>
                    <td><span class='user-email'>${u.email||'-'}</span></td>
                    <td>${u.is_admin==1?`<span class='badge-status admin'><i class='fas fa-shield-alt'></i>Admin</span>`:`<span class='badge-status user'><i class='fas fa-user'></i>Usuário</span>`}</td>
                    <td><span class='user-date'>${u.created_at?formatDate(u.created_at):'-'}</span></td>
                    <td class='text-center'><div class='action-buttons'><button class='btn-action edit' title='Editar usuário' onclick='editUser(${u.id})'><i class='fas fa-edit'></i></button><button class='btn-action delete' title='Excluir usuário' onclick='deleteUser(${u.id})'><i class='fas fa-trash'></i></button></div></td>
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
        html += `<button class='pagination-btn' ${page<=1?'disabled':''} onclick='goToPage(1)' title='Primeira página'><i class='fas fa-angle-double-left'></i></button>`;
        html += `<button class='pagination-btn' ${page<=1?'disabled':''} onclick='goToPage(${page-1})' title='Anterior'><i class='fas fa-angle-left'></i></button>`;

        // Números das páginas
        for (let i = Math.max(1, page - 2); i <= Math.min(totalPages, page + 2); i++) {
            html += `<button class='pagination-btn ${i===page?'active':''}' onclick='goToPage(${i})'>${i}</button>`;
        }

        html += `<button class='pagination-btn' ${page>=totalPages?'disabled':''} onclick='goToPage(${page+1})' title='Próxima'><i class='fas fa-angle-right'></i></button>`;
        html += `<button class='pagination-btn' ${page>=totalPages?'disabled':''} onclick='goToPage(${totalPages})' title='Última página'><i class='fas fa-angle-double-right'></i></button>`;
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
    window.editUser = editUser;
    window.deleteUser = deleteUser;

    // Inicializa tabela ao carregar
    fetchUsers(1);
</script>