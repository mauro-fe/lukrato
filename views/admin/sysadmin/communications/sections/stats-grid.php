<!-- Stats Cards -->
<div class="stats-grid" data-aos="fade-up" data-aos-delay="100">
    <div class="stat-card">
        <div class="stat-icon campaigns">
            <i data-lucide="send"></i>
        </div>
        <div class="stat-content">
            <h3 class="stat-value" id="statTotalCampaigns">
                <?= number_format($stats['total_campaigns'] ?? 0, 0, ',', '.') ?></h3>
            <p class="stat-label">Campanhas Enviadas</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon notifications">
            <i data-lucide="bell"></i>
        </div>
        <div class="stat-content">
            <h3 class="stat-value" id="statTotalNotifications">
                <?= number_format($stats['total_notifications'] ?? 0, 0, ',', '.') ?></h3>
            <p class="stat-label">Notificações Criadas</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon read-rate">
            <i data-lucide="eye"></i>
        </div>
        <div class="stat-content">
            <h3 class="stat-value" id="statReadRate"><?= number_format($stats['read_rate'] ?? 0, 1, ',', '.') ?>%
            </h3>
            <p class="stat-label">Taxa de Leitura</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon recent">
            <i data-lucide="calendar-days"></i>
        </div>
        <div class="stat-content">
            <h3 class="stat-value" id="statCampaignsMonth">
                <?= number_format($stats['campaigns_last_month'] ?? 0, 0, ',', '.') ?></h3>
            <p class="stat-label">Campanhas (30 dias)</p>
        </div>
    </div>
</div>
