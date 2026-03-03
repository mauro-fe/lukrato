<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/bundles/sysadmin-modern.css.php?v=<?= time() ?>">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/pages/communications.css?v=<?= time() ?>">

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
                        <button type="button" class="btn btn-preview" data-action="updatePreview">
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
                    <button class="btn-refresh" data-action="loadCampaigns" title="Atualizar">
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
                    <button class="btn-page" id="btnPrevPage" data-action="changePage" data-delta="-1">
                        <i data-lucide="chevron-left"></i>
                    </button>
                    <span class="page-info" id="pageInfo">Página 1 de 1</span>
                    <button class="btn-page" id="btnNextPage" data-action="changePage" data-delta="1">
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

