<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/sysadmin-modern.css">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/communications.css?v=<?= time() ?>">

<div class="sysadmin-container communications-page">
    <!-- Header Section -->
    <div class="page-header" data-aos="fade-down">
        <div class="header-content">
            <h1 class="page-title">
                <i data-lucide="megaphone"></i>
                Comunicações
            </h1>
            <p class="page-subtitle">Envie mensagens e notificações segmentadas para seus usuários</p>
        </div>
        <a href="<?= BASE_URL ?>sysadmin" class="btn-back">
            <i data-lucide="arrow-left"></i>
            Voltar ao Painel
        </a>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid" data-aos="fade-up" data-aos-delay="100">
        <div class="stat-card">
            <div class="stat-icon campaigns">
                <i data-lucide="send"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-value" id="statTotalCampaigns"><?= number_format($stats['total_campaigns'] ?? 0, 0, ',', '.') ?></h3>
                <p class="stat-label">Campanhas Enviadas</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon notifications">
                <i data-lucide="bell"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-value" id="statTotalNotifications"><?= number_format($stats['total_notifications'] ?? 0, 0, ',', '.') ?></h3>
                <p class="stat-label">Notificações Criadas</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon read-rate">
                <i data-lucide="eye"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-value" id="statReadRate"><?= number_format($stats['read_rate'] ?? 0, 1, ',', '.') ?>%</h3>
                <p class="stat-label">Taxa de Leitura</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon recent">
                <i data-lucide="calendar-days"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-value" id="statCampaignsMonth"><?= number_format($stats['campaigns_last_month'] ?? 0, 0, ',', '.') ?></h3>
                <p class="stat-label">Campanhas (30 dias)</p>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="content-grid">
        <!-- Left: Create Campaign Form -->
        <div class="form-section" data-aos="fade-right" data-aos-delay="200">
            <div class="section-card">
                <div class="section-header">
                    <h2><i data-lucide="circle-plus"></i> Nova Campanha</h2>
                    <span class="preview-count" id="previewCount">
                        <i data-lucide="users"></i>
                        <span id="recipientCount">-</span> usuários
                    </span>
                </div>

                <form id="campaignForm" class="campaign-form">
                    <!-- Tipo da Campanha -->
                    <div class="form-group">
                        <label for="campaignType">
                            <i data-lucide="tag"></i> Tipo da Mensagem
                        </label>
                        <select id="campaignType" name="type" class="form-control" required>
                            <?php foreach ($typeOptions as $value => $label): ?>
                                <option value="<?= $value ?>" <?= $value === 'promo' ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Título -->
                    <div class="form-group">
                        <label for="campaignTitle">
                            <i data-lucide="heading"></i> Título
                        </label>
                        <input type="text" id="campaignTitle" name="title" class="form-control"
                            placeholder="Ex: Você está aproveitando o máximo do Lukrato?"
                            maxlength="255" required>
                        <div class="char-count"><span id="titleCount">0</span>/255</div>
                    </div>

                    <!-- Mensagem -->
                    <div class="form-group">
                        <label for="campaignMessage">
                            <i data-lucide="align-left"></i> Mensagem
                        </label>
                        <textarea id="campaignMessage" name="message" class="form-control" rows="5"
                            placeholder="Escreva aqui sua mensagem para os usuários..." required></textarea>
                    </div>

                    <!-- CTA (Link) -->
                    <div class="form-group">
                        <label for="campaignLink">
                            <i data-lucide="link"></i> Link do CTA (opcional)
                        </label>
                        <div class="input-group">
                            <input type="url" id="campaignLink" name="link" class="form-control"
                                placeholder="https://lukrato.com/billing">
                            <input type="text" id="campaignLinkText" name="link_text" class="form-control link-text"
                                placeholder="Texto do botão">
                        </div>
                    </div>

                    <!-- Segmentação -->
                    <div class="form-section-title">
                        <i data-lucide="filter"></i> Segmentação
                    </div>

                    <div class="filters-grid">
                        <!-- Plano -->
                        <div class="form-group">
                            <label for="filterPlan">
                                <i data-lucide="crown"></i> Plano
                            </label>
                            <select id="filterPlan" name="filters[plan]" class="form-control filter-input">
                                <?php foreach ($planOptions as $value => $label): ?>
                                    <option value="<?= $value ?>"><?= htmlspecialchars($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Status -->
                        <div class="form-group">
                            <label for="filterStatus">
                                <i data-lucide="user-check"></i> Status
                            </label>
                            <select id="filterStatus" name="filters[status]" class="form-control filter-input">
                                <?php foreach ($statusOptions as $value => $label): ?>
                                    <option value="<?= $value ?>"><?= htmlspecialchars($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Dias Inativos -->
                        <div class="form-group">
                            <label for="filterDaysInactive">
                                <i data-lucide="calendar-x"></i> Inatividade
                            </label>
                            <select id="filterDaysInactive" name="filters[days_inactive]" class="form-control filter-input">
                                <?php foreach ($inactiveDaysOptions as $value => $label): ?>
                                    <option value="<?= $value ?? '' ?>"><?= htmlspecialchars($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Canais -->
                    <div class="form-section-title">
                        <i data-lucide="radio-tower"></i> Canais de Envio
                    </div>

                    <div class="channels-grid">
                        <label class="channel-option">
                            <input type="checkbox" name="send_notification" id="sendNotification" checked>
                            <span class="channel-box">
                                <i data-lucide="bell"></i>
                                <span>Notificação Interna</span>
                            </span>
                        </label>

                        <label class="channel-option">
                            <input type="checkbox" name="send_email" id="sendEmail">
                            <span class="channel-box">
                                <i data-lucide="mail"></i>
                                <span>E-mail</span>
                            </span>
                        </label>
                    </div>

                    <!-- Botão de Envio -->
                    <div class="form-actions">
                        <button type="button" class="btn btn-preview" onclick="updatePreview()">
                            <i data-lucide="eye"></i> Atualizar Preview
                        </button>
                        <button type="submit" class="btn btn-primary btn-send" id="btnSend">
                            <i data-lucide="send"></i> Enviar Campanha
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Right: History -->
        <div class="history-section" data-aos="fade-left" data-aos-delay="300">
            <div class="section-card">
                <div class="section-header">
                    <h2><i data-lucide="history"></i> Histórico de Campanhas</h2>
                    <button class="btn-refresh" onclick="loadCampaigns()" title="Atualizar">
                        <i data-lucide="refresh-cw"></i>
                    </button>
                </div>

                <div class="campaigns-list" id="campaignsList">
                    <div class="loading-state">
                        <i data-lucide="loader-2" class="icon-spin"></i>
                        <span>Carregando campanhas...</span>
                    </div>
                </div>

                <!-- Pagination -->
                <div class="pagination-container" id="paginationContainer" style="display: none;">
                    <button class="btn-page" id="btnPrevPage" onclick="changePage(-1)">
                        <i data-lucide="chevron-left"></i>
                    </button>
                    <span class="page-info" id="pageInfo">Página 1 de 1</span>
                    <button class="btn-page" id="btnNextPage" onclick="changePage(1)">
                        <i data-lucide="chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Detalhes da Campanha -->
<div class="modal fade" id="campaignDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i data-lucide="info"></i>
                    Detalhes da Campanha
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body" id="campaignDetailBody">
                <!-- Conteúdo carregado via JS -->
            </div>
        </div>
    </div>
</div>

<script>
    // ================================================
    // COMMUNICATIONS PAGE JS
    // ================================================

    // FA → Lucide icon mapping for dynamic icons
    const faToLucide = {
        'fa-bullhorn': 'megaphone', 'fa-bell': 'bell', 'fa-paper-plane': 'send',
        'fa-envelope': 'mail', 'fa-crown': 'crown', 'fa-star': 'star',
        'fa-gift': 'gift', 'fa-rocket': 'rocket', 'fa-tag': 'tag',
        'fa-info-circle': 'info', 'fa-exclamation-triangle': 'triangle-alert',
        'fa-check-circle': 'circle-check', 'fa-users': 'users', 'fa-chart-line': 'line-chart'
    };
    function lucideIcon(faClass) {
        return faToLucide[faClass] || faClass.replace('fa-', '');
    }

    const BASE = window.LK?.getBase() || '<?= rtrim(BASE_URL, "/") . "/" ?>';
    let currentPage = 1;
    let totalPages = 1;
    let debounceTimer = null;

    // Inicialização
    document.addEventListener('DOMContentLoaded', () => {
        loadCampaigns();
        setupFormListeners();
        updatePreview();
    });

    // Setup listeners do formulário
    function setupFormListeners() {
        // Contador de caracteres do título
        const titleInput = document.getElementById('campaignTitle');
        const titleCount = document.getElementById('titleCount');
        titleInput?.addEventListener('input', () => {
            titleCount.textContent = titleInput.value.length;
        });

        // Debounce nos filtros para atualizar preview
        document.querySelectorAll('.filter-input').forEach(el => {
            el.addEventListener('change', () => {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(updatePreview, 300);
            });
        });

        // Form submit
        document.getElementById('campaignForm')?.addEventListener('submit', handleFormSubmit);
    }

    // Atualizar preview (contar destinatários)
    async function updatePreview() {
        const previewCount = document.getElementById('recipientCount');
        previewCount.textContent = '...';

        try {
            const params = new URLSearchParams({
                plan: document.getElementById('filterPlan').value,
                status: document.getElementById('filterStatus').value,
                days_inactive: document.getElementById('filterDaysInactive').value || '',
            });

            const response = await fetch(`${BASE}api/campaigns/preview?${params}`, {
                headers: {
                    'Accept': 'application/json',
                },
                credentials: 'same-origin'
            });

            const data = await response.json();

            if (data.success) {
                previewCount.textContent = data.data.count.toLocaleString('pt-BR');
            } else {
                previewCount.textContent = '?';
            }
        } catch (error) {
            console.error('Erro ao carregar preview:', error);
            previewCount.textContent = '?';
        }
    }

    // Carregar campanhas
    async function loadCampaigns(page = 1) {
        const list = document.getElementById('campaignsList');
        list.innerHTML = `
        <div class="loading-state">
            <i data-lucide="loader-2" class="icon-spin"></i>
            <span>Carregando campanhas...</span>
        </div>
    `;

        try {
            const response = await fetch(`${BASE}api/campaigns?page=${page}&per_page=10`, {
                headers: {
                    'Accept': 'application/json',
                },
                credentials: 'same-origin'
            });

            const data = await response.json();

            if (data.success) {
                renderCampaigns(data.data.campaigns);
                updatePagination(data.data.pagination);
                currentPage = page;
            } else {
                list.innerHTML = `
                <div class="empty-state">
                    <i data-lucide="circle-alert"></i>
                    <span>${data.message || 'Erro ao carregar campanhas'}</span>
                </div>
            `;
            }
        } catch (error) {
            console.error('Erro ao carregar campanhas:', error);
            list.innerHTML = `
            <div class="empty-state">
                <i data-lucide="circle-alert"></i>
                <span>Erro ao carregar campanhas</span>
            </div>
        `;
        }
    }

    // Renderizar lista de campanhas
    function renderCampaigns(campaigns) {
        const list = document.getElementById('campaignsList');

        if (!campaigns || campaigns.length === 0) {
            list.innerHTML = `
            <div class="empty-state">
                <i data-lucide="inbox"></i>
                <span>Nenhuma campanha enviada ainda</span>
            </div>
        `;
            return;
        }

        list.innerHTML = campaigns.map(campaign => `
        <div class="campaign-item" onclick="showCampaignDetail(${campaign.id})">
            <div class="campaign-icon" style="background-color: ${campaign.color}20; color: ${campaign.color}">
                <i data-lucide="${lucideIcon(campaign.icon)}"></i>
            </div>
            <div class="campaign-info">
                <h4 class="campaign-title">${escapeHtml(campaign.title)}</h4>
                <div class="campaign-meta">
                    <span><i data-lucide="users"></i> ${campaign.total_recipients}</span>
                    <span><i data-lucide="eye"></i> ${campaign.read_rate}%</span>
                    <span><i data-lucide="calendar"></i> ${campaign.created_at}</span>
                </div>
                <div class="campaign-tags">
                    <span class="tag">${campaign.filters_description}</span>
                    <span class="tag">${campaign.channels_description}</span>
                </div>
            </div>
            <div class="campaign-status" style="background-color: ${campaign.status_badge.color}">
                ${campaign.status_badge.label}
            </div>
        </div>
    `).join('');
    }

    // Atualizar paginação
    function updatePagination(pagination) {
        const container = document.getElementById('paginationContainer');
        const pageInfo = document.getElementById('pageInfo');
        const btnPrev = document.getElementById('btnPrevPage');
        const btnNext = document.getElementById('btnNextPage');

        totalPages = pagination.total_pages;

        if (totalPages <= 1) {
            container.style.display = 'none';
            return;
        }

        container.style.display = 'flex';
        pageInfo.textContent = `Página ${pagination.current_page} de ${totalPages}`;
        btnPrev.disabled = pagination.current_page <= 1;
        btnNext.disabled = pagination.current_page >= totalPages;
    }

    // Mudar página
    function changePage(delta) {
        const newPage = currentPage + delta;
        if (newPage >= 1 && newPage <= totalPages) {
            loadCampaigns(newPage);
        }
    }

    // Mostrar detalhes da campanha
    async function showCampaignDetail(id) {
        const modal = new bootstrap.Modal(document.getElementById('campaignDetailModal'));
        const body = document.getElementById('campaignDetailBody');

        body.innerHTML = `
        <div class="text-center py-4">
            <i data-lucide="loader-2" class="icon-spin"></i>
        </div>
    `;
        modal.show();

        try {
            const response = await fetch(`${BASE}api/campaigns/${id}`, {
                headers: {
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            });

            const data = await response.json();

            if (data.success) {
                const c = data.data;
                body.innerHTML = `
                <div class="campaign-detail">
                    <div class="detail-header" style="border-left: 4px solid ${c.color}">
                        <i data-lucide="${lucideIcon(c.icon)}" style="color: ${c.color}"></i>
                        <div>
                            <h4>${escapeHtml(c.title)}</h4>
                            <span class="detail-creator">Por ${escapeHtml(c.creator.nome)} em ${c.created_at}</span>
                        </div>
                    </div>

                    <div class="detail-message">
                        <strong>Mensagem:</strong>
                        <p>${escapeHtml(c.message).replace(/\n/g, '<br>')}</p>
                        ${c.link ? `<a href="${escapeHtml(c.link)}" target="_blank" class="detail-cta">${escapeHtml(c.link_text || 'Ver link')} <i data-lucide="external-link"></i></a>` : ''}
                    </div>

                    <div class="detail-stats">
                        <div class="stat">
                            <span class="stat-label">Destinatários</span>
                            <span class="stat-value">${c.total_recipients}</span>
                        </div>
                        <div class="stat">
                            <span class="stat-label">Notificações Lidas</span>
                            <span class="stat-value">${c.notifications_read} (${c.read_rate}%)</span>
                        </div>
                        ${c.send_email ? `
                        <div class="stat">
                            <span class="stat-label">E-mails Enviados</span>
                            <span class="stat-value">${c.emails_sent}</span>
                        </div>
                        <div class="stat">
                            <span class="stat-label">E-mails Falharam</span>
                            <span class="stat-value">${c.emails_failed}</span>
                        </div>
                        ` : ''}
                    </div>

                    <div class="detail-meta">
                        <span><i data-lucide="filter"></i> ${c.filters_description}</span>
                        <span><i data-lucide="radio-tower"></i> ${c.channels_description}</span>
                        <span class="status-badge" style="background-color: ${c.status_badge.color}">${c.status_badge.label}</span>
                    </div>
                </div>
            `;
            } else {
                body.innerHTML = `<div class="text-danger">Erro ao carregar detalhes</div>`;
            }
        } catch (error) {
            console.error('Erro:', error);
            body.innerHTML = `<div class="text-danger">Erro ao carregar detalhes</div>`;
        }
    }

    // Enviar campanha
    async function handleFormSubmit(e) {
        e.preventDefault();

        const btn = document.getElementById('btnSend');
        const originalText = btn.innerHTML;

        // Validações
        const title = document.getElementById('campaignTitle').value.trim();
        const message = document.getElementById('campaignMessage').value.trim();
        const sendNotification = document.getElementById('sendNotification').checked;
        const sendEmail = document.getElementById('sendEmail').checked;

        if (!title || !message) {
            Swal.fire({
                icon: 'warning',
                title: 'Campos obrigatórios',
                text: 'Preencha o título e a mensagem da campanha.'
            });
            return;
        }

        if (!sendNotification && !sendEmail) {
            Swal.fire({
                icon: 'warning',
                title: 'Selecione um canal',
                text: 'Escolha pelo menos um canal de envio (Notificação ou E-mail).'
            });
            return;
        }

        // Confirmar envio
        const recipientCount = document.getElementById('recipientCount').textContent;
        const result = await Swal.fire({
            icon: 'question',
            title: 'Confirmar envio?',
            html: `
            <p>Você está prestes a enviar uma campanha para <strong>${recipientCount} usuários</strong>.</p>
            <p class="text-muted">Canais: ${sendNotification ? 'Notificação' : ''} ${sendNotification && sendEmail ? '+' : ''} ${sendEmail ? 'E-mail' : ''}</p>
        `,
            showCancelButton: true,
            confirmButtonText: 'Sim, enviar!',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#10b981'
        });

        if (!result.isConfirmed) return;

        // Enviar
        btn.disabled = true;
        btn.innerHTML = '<i data-lucide="loader-2" class="icon-spin"></i> Enviando...';
        try {
            const payload = {
                title: title,
                message: message,
                type: document.getElementById('campaignType').value,
                link: document.getElementById('campaignLink').value || null,
                link_text: document.getElementById('campaignLinkText').value || null,
                send_notification: sendNotification,
                send_email: sendEmail,
                filters: {
                    plan: document.getElementById('filterPlan').value,
                    status: document.getElementById('filterStatus').value,
                    days_inactive: document.getElementById('filterDaysInactive').value || null
                }
            };

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

            const response = await fetch(`${BASE}api/campaigns`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                credentials: 'same-origin',
                body: JSON.stringify(payload)
            });

            const data = await response.json();

            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Campanha enviada!',
                    html: `
                    <p><strong>${data.data.total_recipients}</strong> usuários receberão sua mensagem.</p>
                    ${data.data.emails_sent > 0 ? `<p>E-mails enviados: ${data.data.emails_sent}</p>` : ''}
                    ${data.data.emails_failed > 0 ? `<p class="text-warning">E-mails com falha: ${data.data.emails_failed}</p>` : ''}
                `,
                    timer: 5000
                });

                // Limpar formulário
                document.getElementById('campaignForm').reset();
                document.getElementById('titleCount').textContent = '0';
                document.getElementById('sendNotification').checked = true;

                // Atualizar lista
                loadCampaigns();
                updatePreview();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro ao enviar',
                    text: data.message || 'Ocorreu um erro ao enviar a campanha.'
                });
            }
        } catch (error) {
            console.error('Erro:', error);
            Swal.fire({
                icon: 'error',
                title: 'Erro ao enviar',
                text: 'Ocorreu um erro de conexão. Tente novamente.'
            });
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    }

    // Helper: escape HTML
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
</script>